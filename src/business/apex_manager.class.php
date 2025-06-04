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
   * Returns whether or not the provided file has been uploaded to the Apex server
   * @return boolean
   */
  public function check_for_scan( $filename )
  {
    $data = util::parse_dxa_filename( $filename );
    $phase_string = sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' );
    $short_type_string = strtoupper(
      is_null( $data['side'] ) ? $data['type'][0] : $data['type'][0].$data['side'][0]
    );

    // the patient ID is based on uid, side and type
    $patient_id = sprintf( '%s_%s', $data['uid'], $short_type_string );

    // the scan ID is based on uid, phase, side, type, and whether it was reanalysed
    $scan_id = sprintf( '%s%s%s', $data['uid'], $phase_string, $short_type_string );

    // check if the file is already on the server
    $select = lib::create( 'database\select' );
    $select->from( 'dbo.ScanAnalysis' );
    $select->add_column( 'COUNT(*)', NULL, false );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'PATIENT_KEY', '=', $patient_id );
    $modifier->where( 'SCANID', '=', $scan_id );
    return 0 < $this->query_one( sprintf( "%s %s", $select->get_sql(), $modifier->get_sql() ) );
  }

  /**
   * Uploads DICOM images to the Apex host
   * 
   * @param $db_apex_analysis The analysis to upload files for (current and base paired file if needed)
   * @return [object]
   * @access public
   */
  public function upload_files( $db_apex_analysis )
  {
    // start by checking if the necessary servers are online
    $response = $this->ssh( sprintf( '%s\dgate64.exe -v --echo:CONQUESTSRV1', $this->dgate_in_path ) );
    $dicom_in_online = 1 === preg_match( '/ is UP/', $response['output'] );

    $response = $this->ssh( 'tasklist /FI "IMAGENAME eq qdr.exe" /FO LIST' );
    $qdr_online = 1 === preg_match( '/qdr.exe/', $response['output'] );

    $result_list = [];
    $modify_patient_record = true;

    $image_list = $db_apex_analysis->get_images_for_apex( $this->db_apex_host );
    foreach( $image_list as $image )
    {
      $result = ['file' => $image['filename'], 'error' => NULL];
      $data = util::parse_dxa_filename( $result['file'] );

      // add base paths to relative filenames
      $filename = $result['file'];
      if( $image['reanalysed'] )
      {
        if( 0 === preg_match( sprintf( '#%s#', SUPPLEMENTARY_PATH ), $filename ) )
          $filename = sprintf( '%s%s', SUPPLEMENTARY_PATH, $filename );
      }
      else
      {
        if( 0 === preg_match( sprintf( '#%s#', IMAGES_PATH ), $filename ) )
          $filename = sprintf( '%s%s', IMAGES_PATH, $filename );
      }

      $phase_string = sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' );
      $type_string = is_null( $data['side'] ) ?
        $data['type'] : sprintf( '%s (%s)', $data['type'], $data['side'] );
      $short_type_string = strtoupper(
        is_null( $data['side'] ) ? $data['type'][0] : $data['type'][0].$data['side'][0]
      );

      // set the patient ID based on uid, side and type
      $new_patient_id = sprintf( '%s_%s', $data['uid'], $short_type_string );

      // set the scan ID based on uid, phase, side, type, and whether it was reanalysed
      $new_scan_id = sprintf( '%s%s%s', $data['uid'], $phase_string, $short_type_string );

      try
      {
        if( $this->check_for_scan( $result['file'] ) )
        {
          // a modified patient record already exists
          $modify_patient_record = false;
        }
        else // only proceed if the file isn't already on the server
        {
          // check that the file exists
          if( !file_exists( $filename ) ) throw new \Exception( 'File not found in data vault' );

          if( !$dicom_in_online || !$qdr_online )
            throw new \Exception( sprintf( 'Service(s) on %s are offline', $this->db_apex_host->db_address ) );

          // create a temporary copy of the dicom file and prepare it for apex
          $temp_filename = sprintf( '%s/%s.dcm', TEMP_PATH, $new_patient_id );
          if( self::$debug ) log::debug( sprintf( 'cp %s %s', $filename, $temp_filename ) );
          copy( $filename, $temp_filename );

          $response = $this->get_patient_id( $temp_filename );
          if( 0 != $response['exitcode'] ) throw new \Exception( 'Unable to determine DICOM PatientID tag' );

          $matches = [];
          if( !preg_match( '/\[([^[]+)\]/', $response['output'], $matches ) )
            throw new \Exception( 'File is missing PatientID tag' );
          $old_patient_id = $matches[1];

          $response = $this->set_patient_id( $temp_filename, $new_patient_id );
          if( 0 != $response['exitcode'] ) throw new \Exception( 'Failed to modify DICOM tags' );

          $response = $this->scp_file_to_apex( $temp_filename, sprintf( '%s\incoming', $this->incoming_path ) );
          if( self::$debug ) log::debug( sprintf( 'rm %s', $temp_filename ) );
          unlink( $temp_filename );
          if( 0 != $response['exitcode'] )
            throw new \Exception( sprintf( 'Failed to copy file to %s', $this->db_apex_host->db_address ) );

          // wait up to 15 seconds for the file to register in the DICOM server
          $file_registered = false;
          for( $i = 1; $i <= 15; $i++ )
          {
            sleep(1);
            $response = $this->ssh( sprintf( 'dir %s\%s', $this->incoming_path, $new_patient_id ) );
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
            throw new \Exception( 'Failed to register file in DICOM server' );
          }

          // move file to Apex DICOM server
          $response = $this->ssh(
            sprintf(
              '%s\dgate64.exe -v --movepatient:CONQUESTSRV1,DEXA,%s',
              $this->dgate_in_path,
              $new_patient_id
            )
          );

          // remove files from the DICOM IN server (whether the move patient command works or not)
          $this->delete_patient( 'in', $new_patient_id );

          $select = lib::create( 'database\select' );
          $select->from( 'dbo.PATIENT' );
          $select->add_column( 'PATIENT_KEY', NULL, false );
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'IDENTIFIER1', '=', $old_patient_id );
          $patient_key = $this->query_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
          if( is_null( $patient_key ) || 0 == $patient_key )
            throw new \Exception( 'Failed to move file into Apex' );

          // modify name and identifier in the Apex database (for the first image only)
          $working_patient_id = $old_patient_id;
          if( $modify_patient_record )
          {
            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'IDENTIFIER1', '=', $old_patient_id );
            $query_response = $this->query_execute( sprintf(
              "UPDATE dbo.PATIENT ".
              "SET PATIENT_KEY = '%s', IDENTIFIER1 = '%s', FIRST_NAME = '%s', LAST_NAME = '%s' %s",
              $new_patient_id,
              $new_patient_id,
              $type_string,
              $data['uid'],
              $modifier->get_sql()
            ) );

            if( false === $query_response || is_string( $query_response ) )
            {
              // remove the scan from Apex, if we can
              if( false !== $query_response ) $this->delete_patient( 'apex', $old_patient_id );
              throw new \Exception(
                is_string( $query_response ) ? $query_response : 'Unable to update Apex patient record'
              );
            }

            $working_patient_id = $new_patient_id;
            $patient_key = $new_patient_id;
            $modify_patient_record = false;
          }

          // modify name and identifier in all associated analysis tables
          $table_name_list = ['ScanAnalysis', ucwords( $data['type'] )];
          if( 'hip' == $data['type'] ) $table_name_list[] = 'HipHSA';
          else if( 'wbody' == $data['type'] )
          {
            $table_name_list = array_merge( $table_name_list, [
              'WbodyComposition',
              'SubRegionBone',
              'SubRegionComposition',
              'ObesityIndices',
              'AndroidGynoidComposition'
            ] );
          }

          $table_error_list = [];
          foreach( $table_name_list as $table )
          {
            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'PATIENT_KEY', '=', $patient_key );
            $query_response = $this->query_execute( sprintf(
              "UPDATE dbo.%s ".
              "SET %s SCANID = '%s' %s",
              $table,
              $patient_key != $new_patient_id ? sprintf( "PATIENT_KEY = '%s',", $new_patient_id ) : '',
              $new_scan_id,
              $modifier->get_sql()
            ) );

            if( false === $query_response || is_string( $query_response ) ) $table_error_list[] = $table;
          }

          if( 0 < count( $table_error_list ) )
          {
            // something went wrong, so clean up before reporting the error
            $this->delete_patient( 'apex', $working_patient_id );

            throw new \Exception(
              sprintf(
                'Unable to update Apex %s table%s',
                implode( ', ', $table_error_list ),
                1 == count( $table_error_list ) ? '' : 's'
              )
            );
          }
          else if( $working_patient_id == $old_patient_id )
          {
            // delete the patient record since we have transferred its scans to the base patient
            $this->delete_patient( 'apex', $working_patient_id );
          }
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
    $db_scan_type = $db_exam->get_scan_type();
    $db_interview = $db_exam->get_interview();
    $db_study_phase = $db_interview->get_study_phase();
    $db_participant = $db_interview->get_participant();

    // get the current analysis image only
    // (don't pass the apex host as we don't need to know if the un-analysed scan is present)
    $image = $db_apex_analysis->get_images_for_apex( NULL, true );
    $data = util::parse_dxa_filename( $image['filename'] );

    $short_type_string = strtoupper(
      is_null( $data['side'] ) ? $data['type'][0] : $data['type'][0].$data['side'][0]
    );
    $short_identifier = sprintf( '%s_%s', $data['uid'], $short_type_string );
    $long_identifier = sprintf( '%s\%s\%s', $data['type'], $data['side'], $short_identifier );

    $response = $this->scp_dir_from_apex( sprintf( '%s\%s', $this->outgoing_path, $long_identifier ), TEMP_PATH );
    if( 0 != $response['exitcode'] ) return 'Unable to download re-analysed scan from Apex.';

    if( self::$debug ) log::debug( sprintf( '%s/%s/*', TEMP_PATH, $short_identifier ) );
    $file_list = glob( sprintf( '%s/%s/*', TEMP_PATH, $short_identifier ) );
    if( 1 != count( $file_list ) ) return 'Unable to download re-analysed scan from Apex.';

    $scan_type = $db_scan_type->name;
    if( 'none' != $scan_type ) $scan_type .= sprintf( '_%s', $db_scan_type->side );
    $supplementary_filename = sprintf(
      '%s/%d/dxa/%s/dxa_%s.reanalysed.dcm',
      SUPPLEMENTARY_PATH,
      $db_study_phase->rank,
      $db_participant->uid,
      $scan_type
    );

    // transfer file to supplementary directory
    $transferred_to_supplementary = false;
    if( self::$debug )
    {
      if( self::$debug ) log::debug( sprintf( 'cp %s %s', $file_list[0], $supplementary_filename ) );
    }
    else
    {
      $transferred_to_supplementary = (
        is_writable( dirname( $supplementary_filename ) ) &&
        copy( $file_list[0], $supplementary_filename )
      );

      //if( !$transferred_to_supplementary ) return 'Unable to transfer re-analysed file to Data Vault.';
    }

    $db_apex_analysis->download_datetime = util::get_datetime_object();
    $db_apex_analysis->save();

    $table_name_list = [];
    $column_name_list = [];

    if( 'forearm' == $db_scan_type->name )
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
    else if( 'hip' == $db_scan_type->name )
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
    else if( 'spine' == $db_scan_type->name )
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
    else if( 'wbody' == $db_scan_type->name )
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
        'brain_fat','fat_mass','fat_mass_height_squared','fat_std',
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
      $modifier->where( 'IDENTIFIER1', '=', $short_identifier );

      // join to all data tables
      foreach( $table_name_list as $table_name )
        $modifier->join( $table_name, 'dbo.ScanAnalysis.SCANID', sprintf( 'dbo.%s.SCANID', $table_name ) );

      $row = $this->query_row( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
      if( is_null( $row ) ) return 'Unable to read analysis data from Apex database.';

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
          return sprintf(
            'Column "%s" missing while reading analysis data from Apex database.',
            $row_column_name
          );
        }

        if( 'height' == $column_name ) $height = $row[$row_column_name];
        else if( 'weight' == $column_name ) $weight = $row[$row_column_name];
        else $apex_data[$column_name] = $row[$row_column_name];
      }

      // calculate T and Z scores
      $tz_reference = lib::create( 'business\tz_reference' );
      $tz_reference::$debug = self::$debug;
      $score_data = $tz_reference->compute_tz_scores( $db_scan_type->name, $db_scan_type->side, $apex_data );
      $apex_data = array_merge( $apex_data, $score_data );

      $db_apex_analysis->data = util::json_encode( $apex_data );
      $db_apex_analysis->save();

      if(
        'hip' == $db_scan_type->name &&
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
        if( self::$debug ) log::debug( sprintf( 'writing FRAX input "%s" to file %s', $input, $input_filename ) );
        file_put_contents( $input_filename, $input, LOCK_EX );

        // upload the input file to the apex server and run blackbox.exe
        $response = $this->scp_file_to_apex( $input_filename, sprintf( '%s\input.txt', $this->qdr_data_path ) );
        if( self::$debug ) log::debug( sprintf( 'rm %s', $input_filename ) );
        unlink( $input_filename );
        if( 0 != $response['exitcode'] )
        {
          return sprintf( 'Failed to copy frax input.txt file to %s.', $this->db_apex_host->db_address );
        }

        $response = $this->ssh( sprintf( '%s\blackbox.exe', $this->qdr_data_path ) );
        if( 0 != $response['exitcode'] || $response['output'] )
        {
          return sprintf(
            'Failed to run frax calculator%s.',
            $response['output'] ? sprintf( ' (%s)', $response['output'] ) : ''
          );
        }

        // get the 4 frax values from the output.txt file and clean up
        $response = $this->ssh( sprintf( 'more %s\output.txt', $this->qdr_data_path ) );
        if( self::$debug ) log::debug( sprintf( 'raw frax response: %s', $response['output'] ) );
        $parts = explode( ',', trim( $response['output'] ) );
        if( 17 != count( $parts ) ) return 'FRAX calculator returned unexepcted result.';
        if( '_' == $parts[13] || '_' == $parts[14] || '_' == $parts[15] || '_' == $parts[16] )
          return 'FRAX calculator was unable to generate risk scores.';

        $apex_data['osteoporotic_fracture_risk'] = $parts[13];
        $apex_data['hip_fracture_risk'] = $parts[14];
        $apex_data['osteoporotic_fracture_risk_bmd'] = $parts[15];
        $apex_data['hip_fracture_risk_bmd'] = $parts[16];

        $db_apex_analysis->data = util::json_encode( $apex_data );
        $db_apex_analysis->save();

        $this->ssh( sprintf(
          'del /s /q %s\input.txt %s\output.txt',
          $this->qdr_data_path,
          $this->qdr_data_path
        ) );
      }
    }

    // clean up the exported "report" file on the Apex server (if the transfer was successful)
    if( $transferred_to_supplementary ) $this->delete_patient( 'out', $long_identifier );

    return true;
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
        '%s\dgate64.exe -v --deletepatient:%s',
        $this->dgate_in_path,
        is_null( $identifier ) ? '*' : $identifier
      ) );
    }
    else if ( 'out' == $type )
    {
      // deleting outgoing patients involves delecting a directory only (as defined by the identifier)
      $response = $this->ssh( sprintf(
        'del /s /q %s\%s',
        $this->outgoing_path,
        is_null( $identifier ) ? '*' : $identifier
      ) );

      if( $response )
      {
        $response = $this->ssh( sprintf(
          'rmdir %s\%s',
          $this->outgoing_path,
          is_null( $identifier ) ? '*' : $identifier
        ) );
      }

      return $response;
    }
    else if ( 'apex' == $type )
    {
      // delete P and R files
      $select = lib::create( 'database\select' );
      $select->from( 'dbo.Patient' );
      $select->add_column( 'PFILE_NAME', NULL, false );
      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'dbo.ScanAnalysis', 'dbo.Patient.PATIENT_KEY', 'dbo.ScanAnalysis.PATIENT_KEY' );
      if( !is_null( $identifier ) ) $modifier->where( 'IDENTIFIER1', '=', $identifier );

      $pfile_list = [];
      $pfile_list = $this->query_col( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );

      foreach( $pfile_list as $pfile )
      {
        $glob = preg_replace( '/\.P[0-9]*/', '.*', $pfile );
        $this->ssh( sprintf( 'del /s /q %s\%s', $glob, $this->qdr_data_path ) );
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
    if( self::$debug ) log::debug( $ssh_command );
    return util::exec_timeout( $ssh_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_file_to_apex( $file, $destination )
  {
    $address_parts = explode( ':', $this->db_apex_host->ssh_address );
    $ssh_address = $address_parts[0];
    $ssh_port = array_key_exists( 1, $address_parts ) ? $address_parts[1] : NULL;

    $scp_command = sprintf(
      'scp -i %s %s %s %s@%s:%s',
      $this->keyfile,
      is_null( $ssh_port ) ? '' : sprintf( '-P%d', $ssh_port ),
      $file,
      $this->db_apex_host->ssh_username,
      $ssh_address,
      // replace backslashes with two backslashes
      preg_replace( '#\\\#', '\\\\\\', $destination )
    );
    if( self::$debug ) log::debug( $scp_command );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp_dir_from_apex( $dir, $destination )
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
      preg_replace( '#\\\#', '\\\\\\', $dir ),
      $destination
    );
    if( self::$debug ) log::debug( $scp_command );
    return util::exec_timeout( $scp_command, $this->timeout );
  }

  private function get_patient_id( $filename )
  {
    $command = sprintf(
      'dcmdump --load-short --print-short --search "0010,0020" %s',
      $filename
    );
    if( self::$debug ) log::debug( $command );
    return util::exec_timeout( $command, $this->timeout );
  }

  private function set_patient_id( $filename, $patient_id )
  {
    $command = sprintf(
      'dcmodify -nb -nrc -imt -ma "(0010,0020)=%s" %s',
      $patient_id,
      $filename
    );
    if( self::$debug ) log::debug( $command );
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

    // note that SQL debugging does not include replacing double quotes with single quotes
    if( self::$debug ) log::debug( $sql );

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
  public static $debug = false;
}
