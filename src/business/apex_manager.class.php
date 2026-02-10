<?php
/**
 * apex_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\business;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Manages communication with Apex workstations
 */
class apex_manager extends \cenozo\base_object
{
  /**
   * Constructor.
   * 
   * @param database\apex_host $db_apex_host
   * @access public
   */
  public function __construct( $db_apex_host )
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $this->db_apex_host = $db_apex_host;
    $this->keyfile = $setting_manager->get_setting( 'apex', 'keyfile' );
    $this->password = $setting_manager->get_setting( 'apex', 'db_password' );
    $this->timeout = $setting_manager->get_setting( 'apex', 'timeout' );
    $this->dgate_in_path = $setting_manager->get_setting( 'apex', 'dgate_in' );
    $this->dgate_out_path = $setting_manager->get_setting( 'apex', 'dgate_out' );
    $this->incoming_path = $setting_manager->get_setting( 'apex', 'incoming' );
    $this->outgoing_path = $setting_manager->get_setting( 'apex', 'outgoing' );
    $this->qdr_data_path = $setting_manager->get_setting( 'apex', 'qdr_data' );
    $this->tries = $setting_manager->get_setting( 'apex', 'tries' );
  }

  /**
   * Destructor
   * @access public
   */
  public function __destruct()
  {
    if( !is_null( $this->db ) && false !== $this->db )
    {
      odbc_close( $this->db );
      $this->db = NULL;
    }
  }

  /**
   * Determines which services are running on Apex
   * 
   * @return array
   * @access public
   */
  public function get_status()
  {
    $responses = ['DICOM In' => false, 'DICOM Out' => false, 'DICOM Apex' => false, 'QDR' => false];

    try
    {
      // check if Conquest IN is online
      $response = $this->ssh( sprintf( '%s\dgate64.exe -v --echo:CONQUESTSRV1', $this->dgate_in_path ) );
      $responses['DICOM In'] = 1 === preg_match( '/ is UP/', $response['output'] );

      // check if Conquest OUT is online
      $response = $this->ssh( sprintf( '%s\dgate64.exe -v --echo:CONQUESTSRV2', $this->dgate_out_path ) );
      $responses['DICOM Out'] = 1 === preg_match( '/ is UP/', $response['output'] );

      // check if apex is online
      $response = $this->ssh( sprintf( '%s\dgate64.exe -v --echo:DEXA', $this->dgate_in_path ) );
      $responses['DICOM Apex'] = 1 === preg_match( '/ is UP/', $response['output'] );

      // check if qdr is online
      $response = $this->ssh( 'tasklist /FI "IMAGENAME eq qdr.exe" /FO LIST' );
      $responses['QDR'] = 1 === preg_match( '/qdr.exe/', $response['output'] );
    }
    catch( \cenozo\exception\runtime $e )
    {
      // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
      if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
    }

    return $responses;
  }

  /**
   * Deletes all DICOM images on the Apex host
   * 
   * @return string Any error, or NULL if the operation is successful
   * @access public
   */
  public function delete_all_patients()
  {
    $response = NULL;

    try
    {
      // remove all patients from Apex
      $response = $this->delete_patient( 'apex' );

      // convert an error with no description
      if( false === $response ) $response = 'Unable to delete patient records from Apex database.';
    }
    catch( \cenozo\exception\runtime $e )
    {
      // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
      if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
      $response = 'No response from Apex server when trying to delete patient records from Apex database';
    }

    return $response;
  }

  /**
   * Returns whether or not the provided file has been uploaded to the Apex server
   * @return boolean
   */
  public function check_for_scan( $filename )
  {
    $data = util::parse_dxa_filename( $filename );

    // check if the file is already on the server
    $select = lib::create( 'database\select' );
    $select->from( 'dbo.ScanAnalysis' );
    $select->add_column( 'COUNT(*)', NULL, false );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'PATIENT_KEY', '=', $data['patient_id'] );
    $modifier->where( 'SCANID', '=', $data['scan_id'] );
    return 0 < $this->query_one( sprintf( "%s %s", $select->get_sql(), $modifier->get_sql() ) );
  }

  /**
   * Uploads DICOM images to the Apex host
   * 
   * @param $db_apex_analysis The analysis to upload files for (current and base paired file if needed)
   * @param boolean $replace Whether to overwrite any existing scans on the Apex server
   * @return [object]
   * @access public
   */
  public function upload_files( $db_apex_analysis, $replace = false )
  {
    $db_exam = $db_apex_analysis->get_apex_review()->get_exam();
    $db_interview = $db_exam->get_interview();
    $db_participant = $db_interview->get_participant();
    $dob = $db_participant->date_of_birth;

    // start by checking if the necessary servers are online
    $dicom_is_online = false;
    $qdr_online = false;

    try
    {
      $response = $this->ssh( sprintf( '%s\dgate64.exe -v --echo:CONQUESTSRV1', $this->dgate_in_path ) );
      $dicom_is_online = 1 === preg_match( '/ is UP/', $response['output'] );

      $response = $this->ssh( 'tasklist /FI "IMAGENAME eq qdr.exe" /FO LIST' );
      $qdr_online = 1 === preg_match( '/qdr.exe/', $response['output'] );
    }
    catch( \cenozo\exception\runtime $e )
    {
      // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
      if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
    }

    $result_list = [];

    $new_patient_id = NULL;
    $first_image_success = NULL;
    $delete_patient = $replace;
    $image_list = $db_apex_analysis->get_images_for_apex();
    foreach( $image_list as $image )
    {
      if( static::$debug )
      {
        log::info( sprintf(
          'Uploading %s %s %s%s',
          $image['uid'],
          $image['phase'],
          $image['type_side'],
          $image['reanalysed'] ? ' (reanalysed)' : ''
        ) );
      }

      // only proceed if replacing existing scans or the file isn't already on the server
      if( !$replace && $image['uploaded'] )
      {
        if( static::$debug ) log::info( 'Scan already uploaded, skipping' );
        continue;
      }

      $file_string = $image['reanalysed'] ? 'reanalysed file' : 'file';
      $result = ['file' => $image['filename'], 'error' => NULL];

      // if the first image failed then don't bother
      if( false === $first_image_success )
      {
        $result['error'] = 'Not attempted';
        $result_list[] = $result;
        break;
      }

      // set the patient ID based on uid, side and type and determine the temp filename from it
      $new_patient_id = sprintf( '%s_%s', $image['uid'], $image['short_type_string'] );
      $temp_filename = sprintf( '%s/%s.dcm', TEMP_PATH, $new_patient_id );

      // set the scan ID based on uid, phase, side, type, and whether it was reanalysed
      $new_scan_id = sprintf( '%s%s%s', $image['uid'], $image['phase_string'], $image['short_type_string'] );

      if( $delete_patient )
      {
        try
        {
          // when replacing files delete the patient before proceeding
          $this->delete_patient( 'apex', $new_patient_id );
          $delete_patient = false; // only ever do this once
        }
        catch( \cenozo\exception\runtime $e )
        {
          // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
          if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

          if( is_null( $first_image_success ) ) $first_image_success = false;
          $result['error'] = 'No response from Apex when trying to reset patient before upload';
          $result_list[] = $result;
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $result['error'] ) );
          break;
        }
      }

      // first do basic checks
      if( !file_exists( $image['filename'] ) )
      {
        if( is_null( $first_image_success ) ) $first_image_success = false;
        $result['error'] = sprintf( '%s not found in data vault', ucwords( $file_string, '' ) );
        $result_list[] = $result;
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $result['error'] ) );
        break;
      }

      if( !$dicom_is_online || !$qdr_online )
      {
        if( is_null( $first_image_success ) ) $first_image_success = false;
        $result['error'] = sprintf(
          'Service(s) on %s are offline',
          $this->db_apex_host->db_address
        );
        $result_list[] = $result;
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $result['error'] ) );
        break;
      }

      // try the following multiple times
      $error = NULL;
      for( $try = 0; $try < $this->tries; $try++ )
      {
        if( static::$debug ) log::info( sprintf( 'Attempt #%d', $try+1 ) );
        // make sure to clear out any errors from a previous try
        $error = NULL;

        // create a temporary copy of the dicom file and prepare it for apex
        copy( $image['filename'], $temp_filename );

        // fetching the ID sometimes takes a few tries
        $matches = NULL;
        for( $i = 0; $i < 5; $i++ )
        {
          $response = $this->get_patient_id( $temp_filename );
          if( preg_match( '/\[([^[]+)\]/', $response['output'], $matches ) ) break;
          sleep( 1 );
        }

        if( is_null( $matches ) || 2 > count( $matches ) )
        {
          unlink( $temp_filename );
          $error = sprintf( 'Unable to determine DICOM PatientID tag in %s', $file_string );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        $old_patient_id = $matches[1];

        $response = $this->set_patient_id( $temp_filename, $new_patient_id );
        if( 0 != $response['exitcode'] && 0 < strlen( $response['output'] ) )
        {
          unlink( $temp_filename );
          $error = sprintf( 'Failed to modify DICOM tags in %s', $file_string );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        try
        {
          $response = $this->scp_to_apex( $temp_filename, sprintf( '%s\incoming', $this->incoming_path ) );
          unlink( $temp_filename ); // error or not, we're now done with the temporary file
          if( 0 != $response['exitcode'] )
          {
            $error = sprintf( 'Failed to copy %s to Apex host', $file_string );
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }
        }
        catch( \cenozo\exception\runtime $e )
        {
          // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
          if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

          $error = sprintf( 'No response from Apex server when trying to copy %s', $file_string );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        // wait up to 15 seconds for the file to register in the DICOM server
        $file_registered = false;
        $no_response = false;
        for( $i = 1; $i <= 15; $i++ )
        {
          sleep(1);
          try
          {
            $response = $this->ssh( sprintf( 'dir %s\%s', $this->incoming_path, $new_patient_id ) );
            $no_response = false;
            if( 0 == $response['exitcode'] )
            {
              $file_registered = true;
              break;
            }
          }
          catch( \cenozo\exception\runtime $e )
          {
            // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
            if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
            $no_response = true;
          }
        }
        if( !$file_registered )
        {
          // try deleting the file before registering the error
          try { $this->delete_patient( 'in', $new_patient_id ); } catch( \cenozo\exception\runtime $e ) {}
          $error = sprintf(
            $no_response ?
            'No response from Apex when trying to register %s in DICOM server' :
            'Failed to register %s in DICOM server',
            $file_string
          );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        try
        {
          // move file to Apex DICOM server
          $this->ssh( sprintf(
            '%s\dgate64.exe -v --movepatient:CONQUESTSRV1,DEXA,%s',
            $this->dgate_in_path,
            $new_patient_id
          ) );
        }
        catch( \cenozo\exception\runtime $e )
        {
          // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
          if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

          $error = sprintf( 'No response from Apex server when trying to move %s into Apex', $file_string );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        // try removing files from the DICOM IN server (whether the move patient command works or not)
        try { $this->delete_patient( 'in', $new_patient_id ); } catch( \cenozo\exception\runtime $e ) {}

        $select = lib::create( 'database\select' );
        $select->from( 'dbo.PATIENT' );
        $select->add_column( 'PATIENT_KEY', NULL, false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'IDENTIFIER1', '=', $old_patient_id );
        $patient_key = $this->query_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
        if( is_null( $patient_key ) || 0 == $patient_key )
        {
          $error = sprintf( 'Failed to move %s into Apex', $file_string );
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        // only update the patient record if there isn't already one with the new patient ID
        $working_patient_id = $old_patient_id;
        $select = lib::create( 'database\select' );
        $select->from( 'dbo.PATIENT' );
        $select->add_column( 'COUNT(*)', NULL, false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'PATIENT_KEY', '=', $new_patient_id );
        if( 0 == $this->query_one( sprintf( "%s %s", $select->get_sql(), $modifier->get_sql() ) ) )
        {
          // Only update body columns if the data is available
          // Note that these values will only exist if the hip exam was performed at the DCS
          $body_columns = '';
          if( !is_null( $db_interview->height ) )
            $body_columns .= sprintf( 'HEIGHT = %0.1f, ', $db_interview->height );
          if( !is_null( $db_interview->weight ) )
            $body_columns .= sprintf( 'WEIGHT = %0.1f, ', $db_interview->weight );
          if( !is_null( $db_interview->body_mass_index ) )
            $body_columns .= sprintf( 'BMI = %0.1f, ', $db_interview->body_mass_index );

          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'IDENTIFIER1', '=', $old_patient_id );
          $query_response = $this->query_execute( sprintf(
            "UPDATE dbo.PATIENT SET ".
              "PATIENT_KEY = '%s', ".
              "IDENTIFIER1 = '%s', ".
              "FIRST_NAME = '%s', ".
              "LAST_NAME = '%s', ".
              "SEX = '%s', ".
              "BIRTHDATE = '%s', ".
              "ADJUSTED_AGE = %0.4f, ".
              "%s".
              "MID_INITIAL = NULL, ".
              "PAT_COMMENT = NULL, ".
              "REF_PHYSICIAN = NULL, ".
              "STUDY = NULL, ".
              "ADDRESS1 = NULL, ".
              "ADDRESS2 = NULL, ".
              "CITY = NULL, ".
              "STATE = NULL, ".
              "POSTAL = NULL, ".
              "COUNTRY = NULL ".
              "%s",
            $new_patient_id,
            $new_patient_id,
            $image['type_string'],
            $image['uid'],
            'male' == $db_participant->sex ? 'M' : 'F',
            $dob->format( 'Y-m-d' ),
            ($db_exam->datetime->getTimestamp() - $dob->getTimestamp()) / (60 * 60 * 24 * 365.25),
            $body_columns,
            $modifier->get_sql()
          ) );

          if( false === $query_response || is_string( $query_response ) )
          {
            // remove the scan from Apex, if we can
            if( false !== $query_response )
              try { $this->delete_patient( 'apex', $old_patient_id ); } catch( \cenozo\exception\runtime $e ) {}
            $error = is_string( $query_response ) ? $query_response : 'Unable to update Apex patient record';
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }

          $working_patient_id = $new_patient_id;
          $patient_key = $new_patient_id;
          $modify_patient_record = false;
        }

        // get the current scan id in case something goes wrong and we need to delete the scan
        $select = lib::create( 'database\select' );
        $select->from( 'dbo.ScanAnalysis' );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'PATIENT_KEY', '=', $patient_key );
        $select->add_column( 'SCANID', NULL, false );
        $old_scan_id = $this->query_one( $select->get_sql() );

        // to link the scan to the new patient record modify patient key and scanid columns in ScanAnalysis
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'PATIENT_KEY', '=', $patient_key );
        $query_response = $this->query_execute( sprintf(
          "UPDATE dbo.ScanAnalysis ".
          "SET %s SCANID = '%s' %s",
          $patient_key != $new_patient_id ? sprintf( "PATIENT_KEY = '%s',", $new_patient_id ) : '',
          $new_scan_id,
          $modifier->get_sql()
        ) );

        if( false === $query_response || is_string( $query_response ) )
        {
          // something went wrong, so clean up before reporting the error
          try { $this->delete_scan( $old_scan_id ); } catch( \cenozo\exception\runtime $e ) {}
          $error = 'Unable to update Apex ScanAnalysis table';
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        if( $working_patient_id == $old_patient_id )
        {
          // delete the patient record since we have transferred its scans to the base patient
          try { $this->delete_patient( 'apex', $working_patient_id ); } catch( \cenozo\exception\runtime $e ) {}
        }

        // finally, make sure the PFILE exists
        $select = lib::create( 'database\select' );
        $select->from( 'dbo.ScanAnalysis' );
        $select->add_column( 'PFILE_NAME', NULL, false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'PATIENT_KEY', '=', $new_patient_id );
        $modifier->where( 'SCANID', '=', $new_scan_id );
        $pfile_name = $this->query_one( sprintf( "%s %s", $select->get_sql(), $modifier->get_sql() ) );

        try
        {
          $response = $this->ssh( sprintf( 'dir %s\%s', $this->qdr_data_path, $pfile_name ) );
          if( 0 != $response['exitcode'] )
          {
            try { $this->delete_scan( $new_scan_id ); } catch( \cenozo\exception\runtime $e ) {}
            $error = sprintf( 'P-file is missing in %s', $file_string );
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }

          $db_apex_analysis->pfile_name = $pfile_name;
          $db_apex_analysis->save();
        }
        catch( \cenozo\exception\runtime $e )
        {
          // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
          if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
          $error = 'No response from Apex server when checking for P-file';
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }

        // if we get here the transfer was successful so we can stop
        if( static::$debug ) log::info( 'Scan successfully uploaded' );
        break;
      }

      if( is_null( $first_image_success ) ) $first_image_success = is_null( $error );
      $result['error'] = $error;
      $result_list[] = $result;
    }

    // clean up if any errors occurred
    if( !is_null( $new_patient_id ) )
    {
      foreach( $result_list as $result )
      {
        if( !is_null( $result['error'] ) )
        {
          try { $this->delete_patient( 'apex', $new_patient_id ); } catch( \cenozo\exception\runtime $e ) {}
          break;
        }
      }
    }

    return $result_list;
  }

  /**
   * Downloads re-analysed image and data from the Apex host
   * 
   * @param $db_apex_analysis The analysis to download files for
   * @return boolean
   * @access public
   */
  public function download_files( $db_apex_analysis )
  {
    // get analysis metadata from Apex database and store it in the analysis data column
    $db_exam = $db_apex_analysis->get_apex_review()->get_exam();
    $db_interview = $db_exam->get_interview();

    // get the current analysis image only
    $image = $db_apex_analysis->get_images_for_apex( true );

    if( static::$debug )
    {
      log::info( sprintf(
        'Downloading %s %s %s',
        $image['uid'],
        $image['phase'],
        $image['type_side']
      ) );
    }

    $error = NULL;
    for( $try = 0; $try < $this->tries; $try++ )
    {
      if( static::$debug ) log::info( sprintf( 'Attempt #%d', $try+1 ) );
      // make sure to clear out any errors from a previous try
      $error = NULL;

      // determine the scan_date and serial_number of the image
      $select = lib::create( 'database\select' );
      $select->from( 'dbo.ScanAnalysis' );
      $select->add_column( "REPLACE(CAST(SCAN_DATE AS DATE), '-', '')", NULL, false );
      $select->add_column( 'SERIAL_NUMBER', NULL, false );
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'PATIENT_KEY', '=', $image['patient_id'] );
      $modifier->where( 'SCANID', '=', $image['scan_id'] );
      $row = $this->query_row( sprintf( "%s %s", $select->get_sql(), $modifier->get_sql() ) );
      if( is_null( $row ) )
      {
        $error = 'Unable to read analysis data from Apex database.';
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
        break; // do not try again
      }
      $scan_date = $row[''];
      $serial_number = $row['SERIAL_NUMBER'];
      $scan_glob = sprintf( '%s_%s_%s_*.dcm', $image['patient_id'], $scan_date, $serial_number );

      try
      {
        $response = $this->scp_from_apex(
          sprintf(
            '%s\%s\%s\%s',
            $this->outgoing_path,
            str_replace( '_', '\\', $image['type_side'] ),
            $image['patient_id'],
            $scan_glob
           ),
          TEMP_PATH
        );
        if( 0 != $response['exitcode'] )
        {
          $error = sprintf(
            'Unable to download DICOM files from Apex.  '.
            'Please make sure you have exported scan %s (scan date "%s") using the Apex Report feature.',
            $image['patient_id'],
            $scan_date
          );

          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          continue; // try again
        }
      }
      catch( \cenozo\exception\runtime $e )
      {
        // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
        if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

        $error = 'No response from Apex server when trying to download DICOM files from Apex.';
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
        continue; // try again
      }

      $file_list = glob( sprintf( '%s/%s', TEMP_PATH, $scan_glob ) );
      if( 0 == count( $file_list ) )
      {
        $error = 'DICOM files not found on Apex server.';
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
        continue; // try again
      }

      $base_supplementary_filename = sprintf(
        '%s/%d/dxa/%s/dxa_%s',
        SUPPLEMENTARY_PATH,
        $image['phase']['rank'],
        $image['uid'],
        $image['type_side']
      );

      // transfer DICOM files to supplementary directory
      foreach( $file_list as $file )
      {
        $supplementary_filename = sprintf( '%s.reanalysed.dcm', $base_supplementary_filename );
        if( !( is_writable( dirname( $supplementary_filename ) ) && rename( $file, $supplementary_filename ) ) )
        {
          $error = 'Unable to transfer re-analysed file to Data Vault.';
          break;
        }
      }

      if( !is_null( $error ) )
      {
        if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
        continue; // try again
      }

      $table_name_list = [];
      $column_name_list = [];

      if( 'forearm' == $image['type'] )
      {
        $table_name_list = ['Forearm'];
        $column_name_list = [
          'arm_length',
          'physician_comment',
          'r_13_area','r_13_bmc','r_13_bmd',
          'r_mid_area','r_mid_bmc','r_mid_bmd',
          'r_ud_area','r_ud_bmc','r_ud_bmd',
          'roi_height','roi_type','roi_width',
          'rtot_area','rtot_bmc','rtot_bmd',
          'ru13tot_area','ru13tot_bmc','ru13tot_bmd',
          'rumidtot_area','rumidtot_bmc','rumidtot_bmd',
          'rutot_bmc','rutot_bmd',
          'ruudtot_area','ruudtot_bmc','ruudtot_bmd',
          'u_13_area','u_13_bmc','u_13_bmd',
          'u_mid_area','u_mid_bmc','u_mid_bmd',
          'u_ud_area','u_ud_bmc','u_ud_bmd',
          'utot_area','utot_bmc','utot_bmd'
        ];
      }
      else if( 'hip' == $image['type'] )
      {
        $table_name_list = ['Hip','HipHSA'];
        $column_name_list = [
          'axis_length',
          'fs_act','fs_bmd','fs_br','fs_cmp','fs_csa','fs_csmi','fs_ed','fs_pcd','fs_sect_mod','fs_width',
          'htot_area','htot_bmc','htot_bmd',
          'inter_area','inter_bmc','inter_bmd',
          'it_act','it_bmd','it_br','it_cmp','it_csa','it_csmi','it_ed','it_pcd','it_sect_mod','it_width',
          'neck_area','neck_bmc','neck_bmd',
          'nn_act','nn_bmd','nn_br','nn_cmp','nn_csa','nn_csmi','nn_ed','nn_pcd','nn_sect_mod','nn_width',
          'physician_comment',
          'roi_height','roi_type','roi_width',
          'shaft_neck_angle',
          'troch_area','troch_bmc','troch_bmd',
          'wards_area','wards_bmc','wards_bmd'
        ];
      }
      else if( 'spine' == $image['type'] )
      {
        $table_name_list = ['Spine'];
        $column_name_list = [
          'l1_area','l1_bmc','l1_bmd','l1_included',
          'l2_area','l2_bmc','l2_bmd','l2_included',
          'l3_area','l3_bmc','l3_bmd','l3_included',
          'l4_area','l4_bmc','l4_bmd','l4_included',
          'no_regions',
          'physician_comment',
          'roi_height','roi_type','roi_width',
          'starting_region',
          'std_tot_bmd',
          'tot_area','tot_bmc','tot_bmd'
        ];
      }
      else if( 'wbody' == $image['type'] )
      {
        $table_name_list = [
          'Wbody',
          'WbodyComposition',
          'AndroidGynoidComposition',
          'ObesityIndices',
          'SubRegionBone',
          'SubRegionComposition'
        ];

        $column_name_list = [
          'android_fat','android_gynoid_ratio','android_lean','android_percent_fat',
          'appendage_lean_mass_height_2',
          'body_mass_index',
          'brain_fat','vfat_mass','fat_mass_height_squared','fat_std',
          'global_area','global_bmc','global_bmd','global_fat','global_lean','global_mass','global_pfat',
          'gynoid_fat','gynoid_lean','gynoid_percent_fat',
          'head_area','head_bmc','head_bmd','head_fat','head_lean','head_mass','head_pfat',
          'l_leg_fat','l_leg_lean','l_leg_mass','l_leg_pfat',
          'l_s_area','l_s_bmc','l_s_bmd',
          'larm_area','larm_bmc','larm_bmd','larm_fat','larm_lean','larm_mass','larm_pfat',
          'lean_mass_height_squared','lean_std',
          'lleg_area','lleg_bmc','lleg_bmd',
          'lrib_area','lrib_bmc','lrib_bmd',
          'net_avg_area','net_avg_bmc','net_avg_bmd','net_avg_fat','net_avg_lean','net_avg_mass','net_avg_pfat',
          'no_regions',
          'pelv_area','pelv_bmc','pelv_bmd',
          'physician_comment',
          'r_leg_fat','r_leg_lean','r_leg_mass','r_leg_pfat',
          'rarm_area','rarm_bmc','rarm_bmd','rarm_fat','rarm_lean','rarm_mass','rarm_pfat',
          'reg10_area','reg10_bmc','reg10_bmd','reg10_fat','reg10_lean','reg10_mass','reg10_name','reg10_pfat',
          'reg11_area','reg11_bmc','reg11_bmd','reg11_fat','reg11_lean','reg11_mass','reg11_name','reg11_pfat',
          'reg12_area','reg12_bmc','reg12_bmd','reg12_fat','reg12_lean','reg12_mass','reg12_name','reg12_pfat',
          'reg13_area','reg13_bmc','reg13_bmd','reg13_fat','reg13_lean','reg13_mass','reg13_name','reg13_pfat',
          'reg14_area','reg14_bmc','reg14_bmd','reg14_fat','reg14_lean','reg14_mass','reg14_name','reg14_pfat',
          'reg1_area','reg1_bmc','reg1_bmd','reg1_fat','reg1_lean','reg1_mass','reg1_name','reg1_pfat',
          'reg2_area','reg2_bmc','reg2_bmd','reg2_fat','reg2_lean','reg2_mass','reg2_name','reg2_pfat',
          'reg3_area','reg3_bmc','reg3_bmd','reg3_fat','reg3_lean','reg3_mass','reg3_name','reg3_pfat',
          'reg4_area','reg4_bmc','reg4_bmd','reg4_fat','reg4_lean','reg4_mass','reg4_name','reg4_pfat',
          'reg5_area','reg5_bmc','reg5_bmd','reg5_fat','reg5_lean','reg5_mass','reg5_name','reg5_pfat',
          'reg6_area','reg6_bmc','reg6_bmd','reg6_fat','reg6_lean','reg6_mass','reg6_name','reg6_pfat',
          'reg7_area','reg7_bmc','reg7_bmd','reg7_fat','reg7_lean','reg7_mass','reg7_name','reg7_pfat',
          'reg8_area','reg8_bmc','reg8_bmd','reg8_fat','reg8_lean','reg8_mass','reg8_name','reg8_pfat',
          'reg9_area','reg9_bmc','reg9_bmd','reg9_fat','reg9_lean','reg9_mass','reg9_name','reg9_pfat',
          'rleg_area','rleg_bmc','rleg_bmd',
          'rrib_area','rrib_bmc','rrib_bmd',
          'subtot_area','subtot_bmc','subtot_bmd','subtot_fat','subtot_lean','subtot_mass','subtot_pfat',
          't_s_area','t_s_bmc','t_s_bmd',
          'tissue_analysis_method',
          'total_fat_mass','total_lean_mass','total_percent_fat',
          'trunk_fat','trunk_lean','trunk_limb_fat_mass_ratio','trunk_mass','trunk_pfat',
          'water_lbm',
          'wbtot_area','wbtot_bmc','wbtot_bmd','wbtot_fat','wbtot_lean','wbtot_mass','wbtot_pfat'
        ];
      }

      if( 0 < count( $table_name_list ) )
      {
        // add columns needed by the tz reference
        $column_name_list = array_merge(
          ['scan_date', 'sex', 'birthdate', 'ethnicity', 'height', 'weight'],
          $column_name_list
        );

        $select = lib::create( 'database\select' );
        $select->from( 'dbo.Patient' );
        $select->add_column( '*', NULL, false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->join( 'dbo.ScanAnalysis', 'dbo.Patient.PATIENT_KEY', 'dbo.ScanAnalysis.PATIENT_KEY' );
        $modifier->where( 'IDENTIFIER1', '=', $image['identifier'] );

        // join to all data tables
        foreach( $table_name_list as $table_name )
          $modifier->join( $table_name, 'dbo.ScanAnalysis.SCANID', sprintf( 'dbo.%s.SCANID', $table_name ) );

        $row = $this->query_row( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
        if( is_null( $row ) )
        {
          $error = 'Unable to read analysis metadata from Apex database.';
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          break; // do not try again
        }

        // create an object containing all columns
        $height = NULL;
        $weight = NULL;
        $apex_data = [];
        foreach( $column_name_list as $column_name )
        {
          // row column names are all in upper case
          $row_column_name = strtoupper( $column_name );

          if( !array_key_exists( $row_column_name, $row ) )
          {
            $error = sprintf(
              'Column "%s" missing while reading analysis data from Apex database.',
              $row_column_name
            );
            break;
          }

          if( 'height' == $column_name ) $height = $row[$row_column_name];
          else if( 'weight' == $column_name ) $weight = $row[$row_column_name];
          else $apex_data[$column_name] = $row[$row_column_name];
        }

        if( !is_null( $error ) )
        {
          if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
          break; // do not try again
        }

        // calculate T and Z scores
        $tz_reference = lib::create( 'business\tz_reference' );
        $score_data = $tz_reference->compute_tz_scores( $image['type'], $image['side'], $apex_data );
        $apex_data = array_merge( $apex_data, $score_data );

        $db_apex_analysis->data = util::json_encode( $apex_data );
        $db_apex_analysis->save();

        if(
          'hip' == $image['type'] &&
          0 < $height &&
          0 < $weight &&
          !is_null( $db_interview->previous_fracture ) &&
          !is_null( $db_interview->parent_hip_fracture ) &&
          !is_null( $db_interview->current_smoker ) &&
          !is_null( $db_interview->glucocorticoid ) &&
          !is_null( $db_interview->rheumatoid_arthritis ) &&
          !is_null( $db_interview->secondary_osteoporosis ) &&
          !is_null( $db_interview->alcohol )
        ) {
          // calculate the frax score

          // start by creating the input.txt files needed by the blackbox.exe program hosted on the Apex server
          $input_filename = sprintf( '%s/input.%s.txt', TEMP_PATH, $db_apex_analysis->id );
          $input_values = [
            't', // type 't' or 'z'
            19, // countryCode
            util::get_interval( $apex_data['scan_date'], $apex_data['birthdate'] )->y, // age int
            'M' == $apex_data['sex'] ? 0 : 1, // sex int 0 = male, 1 = female
            $weight / ( $height/100 * $height/100 ), // bmi double
            $db_interview->previous_fracture ? '0' : '1',
            $db_interview->parent_hip_fracture ? '0' : '1',
            $db_interview->current_smoker ? '0' : '1',
            $db_interview->glucocorticoid ? '0' : '1',
            $db_interview->rheumatoid_arthritis ? '0' : '1',
            $db_interview->secondary_osteoporosis ? '0' : '1',
            $db_interview->alcohol ? '0' : '1',
            $apex_data['neck_t'], // hip scan neck_t score
          ];
          $input = implode( ',', $input_values );
          file_put_contents( $input_filename, $input, LOCK_EX );

          // upload the input file to the apex server and run blackbox.exe
          try
          {
            $response = $this->scp_to_apex( $input_filename, sprintf( '%s\input.txt', $this->qdr_data_path ) );
            unlink( $input_filename );
            if( 0 != $response['exitcode'] )
            {
              $error = 'Failed to copy frax input.txt file to Apex host.';
              if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
              continue; // try again
            }
          }
          catch( \cenozo\exception\runtime $e )
          {
            // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
            if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

            $error = 'No response from Apex server when trying to download copy frax input.txt to Apex.';
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }

          $response = NULL;
          try
          {
            $response = $this->ssh( sprintf( '%s\blackbox.exe', $this->qdr_data_path ) );
            if( 0 != $response['exitcode'] || $response['output'] )
            {
              $error = sprintf(
                'Failed to run frax calculator%s.',
                $response['output'] ? sprintf( ' (%s)', $response['output'] ) : ''
              );
            }
          }
          catch( \cenozo\exception\runtime $e )
          {
            // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
            if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;
            $error = 'No response from Apex server';
          }

          if( !is_null( $error ) )
          {
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }

          // get the 4 frax values from the output.txt file and clean up
          try
          {
            $response = $this->ssh( sprintf( 'more %s\output.txt', $this->qdr_data_path ) );
            $parts = explode( ',', trim( $response['output'] ) );
            if( 17 != count( $parts ) )
            {
              $error = 'FRAX calculator returned unexepcted result.';
            }
            else if( '_' == $parts[13] || '_' == $parts[14] || '_' == $parts[15] || '_' == $parts[16] )
            {
              $error = 'FRAX calculator was unable to generate risk scores.';
            }

            if( !is_null( $error ) )
            {
              if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
              break; // do not try again
            }

            $apex_data['osteoporotic_fracture_risk'] = $parts[13];
            $apex_data['hip_fracture_risk'] = $parts[14];
            $apex_data['osteoporotic_fracture_risk_bmd'] = $parts[15];
            $apex_data['hip_fracture_risk_bmd'] = $parts[16];

            $this->ssh( sprintf(
              'del /s /q %s\input.txt %s\output.txt',
              $this->qdr_data_path,
              $this->qdr_data_path
            ) );
          }
          catch( \cenozo\exception\runtime $e )
          {
            // ignore the errors thrown by exec_timeout (they mean the server isn't responding)
            if( !preg_match( '/command timeout/', $e->get_raw_message() ) ) throw $e;

            $error = 'No response from Apex when calculating FRAX scores';
            if( static::$debug ) log::info( sprintf( 'ERROR: %s', $error ) );
            continue; // try again
          }
        }

        $db_apex_analysis->data = util::json_encode( $apex_data );
        $db_apex_analysis->save();
      }

      // if we get here the transfer was successful so we can stop
      if( static::$debug ) log::info( 'Scan successfully downloaded' );

      // delete the downloaded scan files from Apex
      $this->delete_patient( sprintf( 'out_%s', $image['type_side'] ), $image['patient_id'] );

      break;
    }

    return is_null( $error ) ? true : $error;
  }

  /**
   * Deletes a scan from Apex
   * 
   * @param string $scan_id The scanid in the ScanAnalysis and other supporting tables
   * @return string The response from the server
   * @access private
   */
  private function delete_scan( $scan_id = NULL )
  {
    $select = lib::create( 'database\select' );
    $select->from( 'dbo.ScanAnalysis' );
    $select->add_column( 'PFILE_NAME', NULL, false );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'SCANID', '=', $scan_id );

    foreach( $this->query_col( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $pfile_name )
    {
      $pfile_glob = preg_replace( '/\.P(..)$/', '.[Pr]\1', $pfile_name );
      $this->ssh( sprintf( 'del /s /q %s\%s', $this->qdr_data_path, $pfile_glob ) );
    }

    return $this->query_execute( sprintf( 'DELETE FROM dbo.ScanAnalysis %s', $modifier->get_sql() ) );
  }

  /**
   * Deletes patient files for DICOM IN, DICOM Out or Apex
   * 
   * @param string $type Either "in", "out", or "apex"
   * @param string $identifier The patient identifier (if null then all patients will be deleted)
   * @return string The response from the server
   * @access private
   */
  private function delete_patient( $type, $identifier = NULL )
  {
    $matches = NULL;
    if( 'in' == $type )
    {
      return $this->ssh( sprintf(
        '%s\dgate64.exe -v --deletepatient:%s',
        $this->dgate_in_path,
        is_null( $identifier ) ? '*' : $identifier
      ) );
    }
    else if( preg_match( '/^out_(.+)/', $type, $matches ) )
    {
      // deleting outgoing patients involves deleting a directory (as defined by the identifier)
      $response = $this->ssh( sprintf(
        'del /s /q %s\%s\%s',
        $this->outgoing_path,
        str_replace( '_', '\\', $matches[1] ),
        is_null( $identifier ) ? '*' : $identifier
      ) );

      if( $response )
      {
        $response = $this->ssh( sprintf(
          'rmdir %s\%s\%s',
          $this->outgoing_path,
          str_replace( '_', '\\', $matches[1] ),
          is_null( $identifier ) ? '*' : $identifier
        ) );
      }

      return $response;
    }
    else if ( 'apex' == $type )
    {
      // delete P and R files
      if( is_null( $identifier ) )
      {
        $this->ssh( sprintf( 'del /s /q %s\*.P*', $this->qdr_data_path ) );
        $this->ssh( sprintf( 'del /s /q %s\*.r*', $this->qdr_data_path ) );
      }
      else
      {
        $select = lib::create( 'database\select' );
        $select->from( 'dbo.Patient' );
        $select->add_column( 'PFILE_NAME', NULL, false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->join( 'dbo.ScanAnalysis', 'dbo.Patient.PATIENT_KEY', 'dbo.ScanAnalysis.PATIENT_KEY' );
        $modifier->where( 'IDENTIFIER1', '=', $identifier );

        foreach( $this->query_col( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $pfile_name )
        {
          $pfile_glob = preg_replace( '/\.P(..)$/', '.[Pr]\1', $pfile_name );
          $this->ssh( sprintf( 'del /s /q %s\%s', $this->qdr_data_path, $pfile_glob ) );
        }
      }

      $modifier = lib::create( 'database\modifier' );
      IF( !is_null( $identifier ) ) $modifier->where( 'IDENTIFIER1', '=', $identifier );
      return $this->query_execute( sprintf( 'DELETE FROM dbo.PATIENT %s', $modifier->get_sql() ) );
    }

    return NULL;
  }

  /**
   * Sends ssh commands to the Apex server
   * 
   * @param string $command The command to send
   * @return string The response from the server
   * @access private
   */
  private function ssh( $command )
  {
    $address_parts = explode( ':', $this->db_apex_host->ssh_address );
    $ssh_address = $address_parts[0];
    $ssh_port = array_key_exists( 1, $address_parts ) ? $address_parts[1] : NULL;

    $ssh_command = sprintf(
      'ssh -i %s %s %s@%s "%s"',
      $this->keyfile,
      is_null( $ssh_port ) ? '' : sprintf( '-p%d', $ssh_port ),
      $this->db_apex_host->ssh_username,
      $ssh_address,
      preg_replace( '/"/', '\\"', $command )
    );
    return util::exec_timeout( $ssh_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_to_apex( $glob, $destination )
  {
    $address_parts = explode( ':', $this->db_apex_host->ssh_address );
    $ssh_address = $address_parts[0];
    $ssh_port = array_key_exists( 1, $address_parts ) ? $address_parts[1] : NULL;

    $scp_command = sprintf(
      'scp -i %s %s %s %s@%s:%s',
      $this->keyfile,
      is_null( $ssh_port ) ? '' : sprintf( '-P%d', $ssh_port ),
      $glob,
      $this->db_apex_host->ssh_username,
      $ssh_address,
      // replace backslashes with two backslashes
      preg_replace( '#\\\#', '\\\\\\', $destination )
    );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_from_apex( $glob, $destination )
  {
    $address_parts = explode( ':', $this->db_apex_host->ssh_address );
    $ssh_address = $address_parts[0];
    $ssh_port = array_key_exists( 1, $address_parts ) ? $address_parts[1] : NULL;

    $scp_command = sprintf(
      'scp -i %s %s -r %s@%s:%s %s',
      $this->keyfile,
      is_null( $ssh_port ) ? '' : sprintf( '-P%d', $ssh_port ),
      $this->db_apex_host->ssh_username,
      $ssh_address,
      // replace backslashes with two backslashes
      preg_replace( '#\\\#', '\\\\\\', $glob ),
      $destination
    );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  private function get_patient_id( $filename )
  {
    $command = sprintf(
      'dcmdump --load-short --print-short --search "0010,0020" %s',
      $filename
    );
    return util::exec_timeout( $command, $this->timeout );
  }

  private function set_patient_id( $filename, $patient_id )
  {
    $command = sprintf(
      'dcmodify -nb -nrc -imt -ma "(0010,0020)=%s" %s',
      $patient_id,
      $filename
    );
    return util::exec_timeout( $command, $this->timeout );
  }

  /**
   * Sends queries to the Apex MSSQL database
   * 
   * @access private
   */
  private function query_execute( $sql )
  {
    if( is_null( $this->db ) )
    {
      $this->db = odbc_connect(
        $this->db_apex_host->db_address,
        $this->db_apex_host->db_username,
        $this->password
      );
    }

    // convert all double quotes to single quotes for MSSQL
    $sql = str_replace( '"', "'", $sql );
    if( false === $this->db ) return 'Failed to connect to Apex database.';
    return odbc_exec( $this->db, $sql );
  }

  /**
   * Convenience method that returns all rows of a query as an array of associative arrays and frees the result
   * @param string $sql
   * @return array (NULL if there was an error)
   */
  private function query_all( $sql )
  {
    $query_response = $this->query_execute( $sql );
    $rows = [];
    while( odbc_fetch_row( $query_response ) )
    {
      $num_fields = odbc_num_fields( $query_response );
      if( 0 < $num_fields )
      {
        for( $i = 1; $i <= $num_fields; $i++ )
        {
          $field = odbc_field_name( $query_response, $i );
          $row[$field] = odbc_result( $query_response, $field );
        }
        $rows[] = $row;
      }
    }
    odbc_free_result( $query_response );

    return $rows;
  }

  /**
   * Convenience method that returns the first row of a query as an associative array and frees the result
   * @param string $sql
   * @return array (NULL if there was an error)
   */
  private function query_row( $sql )
  {
    $query_response = $this->query_execute( $sql );
    $success = odbc_fetch_row( $query_response );
    $row = NULL;
    if( $success )
    {
      $row = [];
      for( $i = 1; $i <= odbc_num_fields( $query_response ); $i++ )
      {
        $field = odbc_field_name( $query_response, $i );
        $row[$field] = odbc_result( $query_response, $field );
      }
    }
    odbc_free_result( $query_response );

    return $row;
  }

  /**
   * Conveience method that returns a the first column of a query as an array
   * @param string $sql
   * @return array (NULL if there was an error)
   */
  private function query_col( $sql )
  {
    $query_response = $this->query_execute( $sql );
    $col = [];
    while( odbc_fetch_row( $query_response ) )
    {
      $num_fields = odbc_num_fields( $query_response );
      if( 0 < $num_fields )
      {
        $field = odbc_field_name( $query_response, 1 );
        $col[] = odbc_result( $query_response, $field );
      }
    }
    odbc_free_result( $query_response );

    return $col;
  }

  /**
   * Convenience method that returns the first value of the first row of a query and frees the result
   * @param string $sql
   * @return string (NULL if there was an error)
   */
  private function query_one( $sql )
  {
    $query_response = $this->query_execute( $sql );
    $success = odbc_fetch_row( $query_response );
    $value = $success ? odbc_result( $query_response, 1 ) : NULL;
    odbc_free_result( $query_response );
    return $value;
  }

  /**
   * Convenience method that returns all rows of a query as an array of associative arrays and frees the result
   * @param string $sql
   * @return array (NULL if there was an error)
   */
  private function query_rows( $sql )
  {
    $query_response = $this->query_execute( $sql );
    $rows = [];
    while( odbc_fetch_row( $query_response ) )
    {
      $num_fields = odbc_num_fields( $query_response );
      if( 0 < $num_fields )
      {
        for( $i = 1; $i <= $num_fields; $i++ )
        {
          $field = odbc_field_name( $query_response, $i );
          $row[$field] = odbc_result( $query_response, $field );
        }
        $rows[] = $row;
      }
    }
    odbc_free_result( $query_response );

    return $rows;
  }

  /**
   * A connection to the Apex MSSQL database (created on demand)
   * @var resource
   * @access private
   */
  private $db = NULL;

  /**
   * The database record of the apex host being connected to
   * @var database\apex_host
   * @access private
   */
  private $db_apex_host = NULL;

  /**
   * The path to the SSH keyfile used to connect to the Apex host
   * @var string
   * @access private
   */
  private $keyfile = NULL;

  /**
   * The Apex MSSQL password
   * @var string
   * @access private
   */
  private $password = NULL;

  /**
   * How many seconds to wait for a response from SSH, SCP and DICOM commands
   * @var integer
   * @access private
   */
  private $timeout = 5;

  /**
   * The path of the Conquest IN server's dgate64.exe file
   * @var string
   * @access private
   */
  private $dgate_in_path = NULL;

  /**
   * The path of the Conquest OUT server's dgate64.exe file
   * @var string
   * @access private
   */
  private $dgate_out_path = NULL;

  /**
   * The location of Apex's incoming directory
   * @var string
   * @access private
   */
  private $incoming_path = NULL;

  /**
   * The location of Apex's outgoing directory
   * @var string
   * @access private
   */
  private $outgoing_path = NULL;

  /**
   * The location of Apex's QDR/data directory
   * @var string
   * @access private
   */
  private $qdr_data_path = NULL;

  /**
   * The number of times Alder will try to upload a scan before failing
   * @var integer
   * @access private
   */
  private $tries = NULL;

  /**
   * Whether to print debug statements to the log
   * @var boolean
   * @access public
   * @static
   */
  public static $debug = false;
}
