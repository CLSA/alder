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

      $result = [
        'file' => $file,
        'error' => NULL,
      ];

      // check that the file exists
      if( !file_exists( $filename ) )
      {
        $result['error'] = 'File not found in data vault.';
        $result_list[] = $result;
        continue;
      }

      // set the dicom's ID based on uid, side, type, number and whether it was reanalysed
      $new_patient_id = sprintf(
        '%s_%d_%s%s%s%s',
        $data['uid'],
        $data['phase']['rank'],
        !is_null( $data['side'] ) ? substr( $data['side'], 0, 1 ) : '',
        $data['type'],
        is_null( $data['number'] ) ? '' : $data['number'],
        $data['reanalysed'] ? '_r' : ''
      );

      // create a temporary copy of the dicom file and prepare it for apex
      $temp_filename = sprintf( '%s/%s.dcm', TEMP_PATH, $new_patient_id );
      copy( $filename, $temp_filename );

      $response = $this->get_patient_id( $temp_filename );
      if( 0 != $response['exitcode'] )
      {
        $result['error'] = 'Unable to determine DICOM PatientID tag.';
        $result_list[] = $result;
        continue;
      }
      $matches = [];
      if( !preg_match( '/\[([^[]+)\]/', $response['output'], $matches ) )
      {
        $result['error'] = 'File is missing PatientID tag.';
        $result_list[] = $result;
        continue;
      }
      $old_patient_id = $matches[1];

      $response = $this->set_patient_id( $temp_filename, $new_patient_id );
      if( 0 != $response['exitcode'] )
      {
        $result['error'] = 'Failed to modify DICOM tags.';
        $result_list[] = $result;
        continue;
      }

      $response = $this->scp( $temp_filename, 'E:\incoming\incoming' );
      unlink( $temp_filename );
      if( 0 != $response['exitcode'] )
      {
        $result['error'] = 'Failed to copy file to host.';
        $result_list[] = $result;
        continue;
      }


      // wait up to 10 seconds for the file to register in the DICOM server
      $file_registered = false;
      for( $i = 1; $i <= 10; $i++ )
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
        $result['error'] = 'Failed to register file in DICOM server.';
        $result_list[] = $result;

        // try deleting the file
        $this->ssh( sprintf( 'c:\dicomserverIN\dgate64.exe -v --deletepatient:%s', $new_patient_id ) );

        continue;
      }

      // move file to Apex DICOM server
      $response = $this->ssh(
        sprintf(
          'c:\dicomserverIN\dgate64.exe -v --movepatient:CONQUESTSRV1,DEXA,%s',
          $new_patient_id
        )
      );

      // remove files from the DICOM IN server (whether the move patient command works or not)
      $this->ssh( sprintf( 'c:\dicomserverIN\dgate64.exe -v --deletepatient:%s', $new_patient_id ) );

      if( '0' != $response['output'] )
      {
        $result['error'] = 'Failed to move file into Apex DICOM server.';
        $result_list[] = $result;
        continue;
      }

      // modify name and identifier in the Apex database
      $response = $this->query( sprintf(
        "UPDATE dbo.PATIENT ".
        "SET FIRST_NAME = '%s', LAST_NAME = '%s', IDENTIFIER1 = '%s' ".
        "WHERE IDENTIFIER1 = '%s'",
        sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' ),
        is_null( $data['side'] ) ? $data['type'] : sprintf( '%s %s', $data['side'], $data['type'] ),
        $data['uid'],
        $old_patient_id
      ) );

      if( false === $response || is_string( $response ) )
      {
        $result['error'] = is_string( $response ) ? $response : 'Unable to update Apex patient record.';
        $result_list[] = $result;

        // remove the scan from Apex, if we can
        if( false !== $response )
        {
          $this->query( "DELETE FROM dbo.PATIENT WHERE IDENTIFIER = '%s'", $old_patient_id );
        }

        continue;
      }

      $result_list[] = $result;
    }

    return $result_list;
  }

  /**
   * TODO: document
   */
  public function delete_files()
  {
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
    return util::exec_timeout( $ssh_command, $this->timeout );
  }

  /**
   * Copies files to the Apex server
   * 
   * @access private
   */
  private function scp( $file, $destination )
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
   */
  private $db = NULL;
}
