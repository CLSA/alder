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
    $responses = [];
    // check if Conquest IN is online
    $response = $this->ssh( 'c:\dicomserverIN\dgate64.exe -v --echo:CONQUESTSRV1' );
    $responses['DICOM In'] = 1 === preg_match( '/ is UP/', $response['output'] );

    // check if Conquest OUT is online
    $response = $this->ssh( 'c:\dicomserverOUT\dgate64.exe -v --echo:CONQUESTSRV2' );
    $responses['DICOM Out'] = 1 === preg_match( '/ is UP/', $response['output'] );

    // check if apex is online
    $response = $this->ssh( 'c:\dicomserverIN\dgate64.exe -v --echo:DEXA' );
    $responses['DICOM Apex'] = 1 === preg_match( '/ is UP/', $response['output'] );

    // check if qdr is online
    $response = $this->ssh( 'tasklist /FI "IMAGENAME eq qdr.exe" /FO LIST' );
    $responses['QDR'] = 1 === preg_match( '/qdr.exe/', $response['output'] );

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
    // remove all patients from Apex
    $response = $this->delete_patient( 'apex' );

    // convert an error with no description
    if( false === $response ) $response = 'Unable to delete patient records from Apex database.';
    return is_string( $response ) ? $response : NULL;
  }

  /**
   * Uploads DICOM images to the Apex host
   * 
   * @param [string] $file_list
   * @return [object]
   * @access public
   */
  public function upload_files( $file_list )
  {
    $result_list = [];
    foreach( $file_list as $file )
    {
      $data = util::parse_dxa_filename( $file );
      $filename = $file;

      // add base paths to relative filenames
      if( preg_match( '/reanalysed/', $filename ) )
      {
        if( 0 === preg_match( sprintf( '#%s#', SUPPLEMENTARY_PATH ), $filename ) )
          $filename = sprintf( '%s/%s', SUPPLEMENTARY_PATH, $filename );
      }
      else
      {
        if( 0 === preg_match( sprintf( '#%s#', IMAGES_PATH ), $filename ) )
          $filename = sprintf( '%s/%s', IMAGES_PATH, $filename );
      }

      $result = ['file' => $file, 'error' => NULL];

      $phase_string = sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' );
      $type_string = is_null( $data['side'] ) ?
        $data['type'] : sprintf( '%s (%s)', $data['type'], $data['side'] );
      $short_type_string = strtoupper(
        is_null( $data['side'] ) ? $data['type'][0] : $data['type'][0].$data['side'][0]
      );

      // set the dicom's ID based on uid, side, type, and whether it was reanalysed
      $new_patient_id = sprintf( '%s_%s_%s', $data['uid'], $phase_string, $short_type_string );

      try
      {
        // check if the file is already on the server
        $response = $this->query( sprintf(
          "SELECT COUNT(*) FROM dbo.PATIENT WHERE IDENTIFIER1 = '%s'",
          $new_patient_id
        ) );

        if( !odbc_fetch_row( $response ) ) throw new \Exception( 'Unable to query Apex MSSQL database.' );
        if( 1 == odbc_result( $response, 1 ) ) throw new \Exception( 'File already exists on Apex workstation.' );

        // check that the file exists
        if( !file_exists( $filename ) ) throw new \Exception( 'File not found in data vault.' );

        // create a temporary copy of the dicom file and prepare it for apex
        $temp_filename = sprintf( '%s/%s.dcm', TEMP_PATH, $new_patient_id );
        if( $this->debug ) log::debug( sprintf( 'cp %s %s', $filename, $temp_filename ) );
        copy( $filename, $temp_filename );

        $response = $this->get_patient_id( $temp_filename );
        if( 0 != $response['exitcode'] ) throw new \Exception( 'Unable to determine DICOM PatientID tag.' );

        $matches = [];
        if( !preg_match( '/\[([^[]+)\]/', $response['output'], $matches ) )
          throw new \Exception( 'File is missing PatientID tag.' );
        $old_patient_id = $matches[1];

        $response = $this->set_patient_id( $temp_filename, $new_patient_id );
        if( 0 != $response['exitcode'] ) throw new \Exception( 'Failed to modify DICOM tags.' );

        $response = $this->scp_file_to_apex( $temp_filename, 'E:\incoming\incoming' );
        if( $this->debug ) log::debug( sprintf( 'rm %s', $temp_filename ) );
        unlink( $temp_filename );
        if( 0 != $response['exitcode'] ) throw new \Exception( 'Failed to copy file to host.' );

        // wait up to 15 seconds for the file to register in the DICOM server
        $file_registered = false;
        for( $i = 1; $i <= 15; $i++ )
        {
          sleep(1);
          $response = $this->ssh( sprintf( 'dir E:\incoming\%s', $new_patient_id ) );
          if( 0 == $response['exitcode'] )
          {
            $file_registered = true;
            break;
          }
        }
        if( !$file_registered )
        {
          // try deleting the file
          $this->delete_patient( 'in', $new_patient_id );
          throw new \Exception( 'Failed to register file in DICOM server.' );
        }

        // move file to Apex DICOM server
        $response = $this->ssh(
          sprintf(
            'c:\dicomserverIN\dgate64.exe -v --movepatient:CONQUESTSRV1,DEXA,%s',
            $new_patient_id
          )
        );

        // remove files from the DICOM IN server (whether the move patient command works or not)
        $this->delete_patient( 'in', $new_patient_id );

        $response = $this->query( sprintf(
          "SELECT COUNT(*) FROM dbo.PATIENT WHERE IDENTIFIER1 = '%s'",
          $old_patient_id
        ) );

        if( !odbc_fetch_row( $response ) || 0 == odbc_result( $response, 1 ) )
          throw new \Exception( 'Failed to move file into Apex DICOM server.' );

        // modify name and identifier in the Apex database
        $response = $this->query( sprintf(
          "UPDATE dbo.PATIENT ".
          "SET PATIENT_KEY = '%s', IDENTIFIER1 = '%s', FIRST_NAME = '%s', LAST_NAME = '%s %s' ".
          "WHERE IDENTIFIER1 = '%s'",
          $new_patient_id,
          $new_patient_id,
          $type_string,
          $data['uid'],
          $phase_string,
          $old_patient_id
        ) );

        if( false === $response || is_string( $response ) )
        {
          // remove the scan from Apex, if we can
          if( false !== $response ) $this->delete_patient( 'apex', $old_patient_id );
          throw new \Exception( is_string( $response ) ? $response : 'Unable to update Apex patient record.' );
        }

        // modify name and identifier in all associated analysis tables
        $table_list = ['ScanAnalysis', ucwords( $data['type'] )];
        if( 'hip' == $data['type'] ) $table_list[] = 'HipHSA';
        else if( 'wbody' == $data['type'] )
        {
          $table_list = array_merge( $table_list, [
            'WbodyComposition',
            'SubRegionBone',
            'SubRegionComposition',
            'ObesityIndices',
            'AndroidGynoidComposition'
          ] );
        }

        $table_error_list = [];
        foreach( $table_list as $table )
        {
          $response = $this->query( sprintf(
            "UPDATE dbo.%s ".
            "SET SCANID = '%s' ".
            "WHERE PATIENT_KEY = '%s'",
            $table,
            $new_patient_id,
            $new_patient_id
          ) );

          if( false === $response || is_string( $response ) ) $table_error_list[] = $table;
        }

        if( 0 < count( $table_error_list ) )
        {
          $this->delete_patient( 'apex', $new_patient_id );

          throw new \Exception(
            sprintf(
              'Unable to update Apex %s table%s.',
              implode( ', ', $table_error_list ),
              1 == count( $table_error_list ) ? '' : 's'
            )
          );
        }
      }
      catch( \Exception $e )
      {
        $result['error'] = $e->getMessage();
      }

      $result_list[] = $result;
    }

    return $result_list;
  }

  /**
   * Uploads DICOM images to the Apex host
   * 
   * @param [string] $file_list
   * @return [object]
   * @access public
   */
  public function download_files( $file_list )
  {
    $result_list = [];
    foreach( $file_list as $file )
    {
      $data = util::parse_dxa_filename( $file );
      $filename = $file;

      // add base paths to relative filenames
      if( preg_match( '/reanalysed/', $filename ) )
      {
        if( 0 === preg_match( sprintf( '#%s#', SUPPLEMENTARY_PATH ), $filename ) )
          $filename = sprintf( '%s/%s', SUPPLEMENTARY_PATH, $filename );
      }
      else
      {
        if( 0 === preg_match( sprintf( '#%s#', IMAGES_PATH ), $filename ) )
          $filename = sprintf( '%s/%s', IMAGES_PATH, $filename );
      }

      $result = ['file' => $file, 'error' => NULL];

      $phase_string = sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' );
      $type_string = is_null( $data['side'] ) ?
        $data['type'] : sprintf( '%s (%s)', $data['type'], $data['side'] );
      $short_type_string = strtoupper(
        is_null( $data['side'] ) ? $data['type'][0] : $data['type'][0].$data['side'][0]
      );

      // the directory containing re-analysed images will be the same as the new patient ID used when uploading
      $new_patient_id = sprintf( '%s_%s_%s', $data['uid'], $phase_string, $short_type_string );

      $response = $this->scp_dir_from_apex(
        sprintf( 'E:\outgoing\%s\%s\%s', $data['type'], $data['side'], $new_patient_id ),
        TEMP_PATH
      );
      if( 0 != $response['exitcode'] ) $result['error'] = 'Failed to copy directory from host.';

      // TODO: get analysis metadata from Apex database

      $result_list[] = $result;
    }

    return $result_list;
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
    if( 'in' == $type )
    {
      return $this->ssh( sprintf(
        'c:\dicomserverIN\dgate64.exe -v --deletepatient:%s',
        is_null( $identifier ) ? '*' : $identifier
      ) );
    }
    else if ( 'out' == $type )
    {
      return $this->ssh( sprintf(
        'c:\dicomserverOUT\dgate64.exe -v --deletepatient:%s',
        is_null( $identifier ) ? '*' : $identifier
      ) );
    }
    else if ( 'apex' == $type )
    {
      $sql = 'DELETE FROM dbo.PATIENT';
      IF( !is_null( $identifier ) ) $sql .= sprintf( " WHERE IDENTIFIER1 = '%s'", $identifier );
      return $this->query( $sql );
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
    $ssh_command = sprintf(
      'ssh -i %s %s@%s "%s"',
      $this->keyfile,
      $this->db_apex_host->ssh_username,
      $this->db_apex_host->ssh_address,
      preg_replace( '/"/', '\\"', $command )
    );
    if( $this->debug ) log::debug( $ssh_command );
    return util::exec_timeout( $ssh_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_file_to_apex( $file, $destination )
  {
    $scp_command = sprintf(
      'scp -i %s %s %s@%s:%s',
      $this->keyfile,
      $file,
      $this->db_apex_host->ssh_username,
      $this->db_apex_host->ssh_address,
      // replace backslashes with two backslashes
      preg_replace( '#\\\#', '\\\\\\', $destination )
    );
    if( $this->debug ) log::debug( $scp_command );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_dir_from_apex( $dir, $destination )
  {
    $scp_command = sprintf(
      'scp -i %s -r %s@%s:%s %s',
      $this->keyfile,
      $this->db_apex_host->ssh_username,
      $this->db_apex_host->ssh_address,
      // replace backslashes with two backslashes
      preg_replace( '#\\\#', '\\\\\\', $dir ),
      $destination
    );
    if( $this->debug ) log::debug( $scp_command );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  private function get_patient_id( $filename )
  {
    $command = sprintf(
      'dcmdump --load-short --print-short --search "0010,0020" %s',
      $filename
    );
    if( $this->debug ) log::debug( $command );
    return util::exec_timeout( $command, $this->timeout );
  }

  private function set_patient_id( $filename, $patient_id )
  {
    $command = sprintf(
      'dcmodify -nb -nrc -imt -ma "(0010,0020)=%s" %s',
      $patient_id,
      $filename
    );
    if( $this->debug ) log::debug( $command );
    return util::exec_timeout( $command, $this->timeout );
  }

  /**
   * Sends queries to the Apex MSSQL database
   * 
   * @access private
   */
  private function query( $sql )
  {
    if( is_null( $this->db ) )
    {
      $this->db = odbc_connect(
        $this->db_apex_host->db_address,
        $this->db_apex_host->db_username,
        $this->password
      );
    }

    if( false === $this->db ) return 'Failed to connect to Apex database.';
    if( $this->debug ) log::debug( $sql );
    return odbc_exec( $this->db, $sql );
  }

  /**
   * The number of seconds to wait before giving up on connecting to the apex_host
   * @var integer
   * @access private
   */
  private $timeout = 5;

  /**
   * A connection to the Apex MSSQL database (created on demand)
   * @var resource
   * @access private
   */
  private $db = NULL;

  /**
   * When set to true the manager will print all commands to the log
   * @var boolean
   * @access private
   */
  private $debug = false;
}
