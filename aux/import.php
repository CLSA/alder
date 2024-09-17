#!/usr/bin/php
<?php

/**
 * Populates the interview/exam/image tables based on file lists and opal data.
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

ini_set( 'display_errors', '1' );
error_reporting( E_ALL | E_STRICT );
ini_set( 'date.timezone', 'US/Eastern' );

// if we are in the aux/ directory then back out
if( preg_match( '#/aux$#', getcwd() ) ) chdir( '..' );

if(
  3 > $argc ||
  !in_array( $argv[1], ['generate', 'read', 'import'] ) ||
  ( 'generate' == $argv[1] && 4 != $argc ) ||
  ( 'read' == $argv[1] && 3 != $argc ) ||
  ( 'import' == $argv[1] && 3 != $argc )
)
{
  print(
    "To generate a list of all image files: generate <phase> <filename>\n".
    "  <phase> The phase the image is from (1-indexed)\n".
    "  <filename> CSV file to write image file details to\n".
    "\n".
    "To import image data: read <filename>\n".
    "  <filename> CSV file with no header and the following rows:\n".
    "    phase: The phase the image is from (1-indexed)\n".
    "    scan type: The type of scan (carotid_intima, dxa or retinal)\n".
    "    uid: The X000000-based participant UID\n".
    "    filename: The name of the file\n".
    "    link: If this is a symlink, the name of the file pointed to by the link\n".
    "\n".
    "To import interview/exam data from Opal: import <filename>\n".
    "  <filename> CSV file downloaded from Opal (filename must contain the exam name (or interview) and phase)\n"
  );
  exit( 1 );
}

class import
{
  function fatal_error( $msg, $line )
  {
    printf( "Error on line %s:\n%s\n", $line, $msg );
    $this->db->rollback();
    die();
  }

  /**
   * Reads the framework and application settings
   */
  public function read_settings()
  {
    // include the initialization settings
    global $SETTINGS;
    require_once 'settings.ini.php';
    require_once 'settings.local.ini.php';
    require_once $SETTINGS['path']['CENOZO'].'/src/initial.class.php';
    $initial = new \cenozo\initial( true );
    $this->settings = $initial->get_settings();
    $this->cenozo_database_name =
      $this->settings['db']['database_prefix'].$this->settings['general']['framework_name'];
  }

  public function connect_database()
  {
    $server = $this->settings['db']['server'];
    $username = $this->settings['db']['username'];
    $password = $this->settings['db']['password'];
    $name = $this->settings['db']['database_prefix'] . $this->settings['general']['instance_name'];
    $this->db = new \mysqli( $server, $username, $password, $name );
    if( $this->db->connect_error ) $this->fatal_error( $this->db->connect_error, __LINE__ );
    $this->db->set_charset( 'utf8' );
    $this->db->begin_transaction();
  }

  public function disconnect_database()
  {
    $this->db->commit();
    $this->db->close();
  }

  public function query( $sql, $line )
  {
    $this->db->query( $sql ) || $this->fatal_error( sprintf( "\nSQL ERROR:\n%s\n\nQUERY:\n%s", $this->db->error, $sql ), $line );
  }

  public function generate_image_file( $phase, $filename )
  {
    printf( "Searching for phase %d carotid_intima files\n", $phase );
    exec( sprintf(
      'find /usr/local/mount/alder/%d/carotid_intima '.
        '-type f,l '.
        '\( -name "still*" -o -name "STILL*" \) '.
        '-not -empty -printf "%%p\t%%l\n" | '.
      'sed -e \'s#'.
        '.*/\([0-9]\+\)/carotid_intima/\([^/]\+\)/\([^\t]\+\)\t\(.*\)#'.
        '"\1","carotid_intima","\2","\3","\4"#\' | '.
      'sed -e \'s#,""#,NULL#\' > %s',
      $phase,
      $filename
    ) );

    printf( "Searching for phase %d dxa files\n", $phase );
    exec( sprintf(
      'find /usr/local/mount/alder/%d/dxa -type f,l '.
        '\( '.
          '-name "dxa_forearm*.dcm" -o '.
          '-name "dxa_hip*.dcm" -o '.
          '-name "dxa_lateral.dcm" -o '.
          '-name "dxa_wbody_bca.dcm" '.
          '-o -name "dxa_spine.dcm" '.
        '\) '.
        '-not -empty -printf "%%p\t%%l\n" | '.
      'sed -e \'s#.*/\([0-9]\+\)/dxa/\([^/]\+\)/\([^\t]\+\)\t\(.*\)#"\1","dxa","\2","\3","\4"#\' | '.
      'sed -e \'s#,""#,NULL#\' >> %s',
      $phase,
      $filename
    ) );

    printf( "Searching for phase %d retinal files\n", $phase );
    exec( sprintf(
      'find /usr/local/mount/alder/%d/retinal -type f,l -name "retinal*.jpeg" -not -empty -printf "%%p\t%%l\n" | '.
      'sed -e \'s#.*/\([0-9]\+\)/retinal/\([^/]\+\)/\([^\t]\+\)\t\(.*\)#"\1","retinal","\2","\3","\4"#\' | '.
      'sed -e \'s#,""#,NULL#\' >> %s',
      $phase,
      $filename
    ) );
  }

  public function import_image_file( $filename )
  {
    printf( "Importing image data found in \"%s\"\n", $filename );

    // create a temporary table to load image data into
    $this->query(
      'CREATE TEMPORARY TABLE temp_file ( '.
        'id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, '.
        'phase INT(10) UNSIGNED NOT NULL, '.
        'type VARCHAR(45) NOT NULL, '.
        'uid CHAR(7) NOT NULL, '.
        'filename VARCHAR(45) NOT NULL, '.
        'link VARCHAR(45) NULL DEFAULT NULL, '.
        'PRIMARY KEY (id) '.
      ')',
      __LINE__
    );

    $this->query(
      sprintf(
        'LOAD DATA LOCAL INFILE "%s" '.
        'INTO TABLE temp_file '.
        'FIELDS TERMINATED BY "," '.
        'ENCLOSED BY \'"\' '.
        'LINES TERMINATED BY "\n" '.
        '(phase, type, uid, filename, link)',
        $filename
      ),
      __LINE__
    );

    $this->query(
      'CREATE TEMPORARY TABLE temp_image '.
      'SELECT id, phase, type, uid, filename AS name '.
      'FROM temp_file '.
      'WHERE link IS NULL',
      __LINE__
    );
    $this->query( 'ALTER TABLE temp_image ADD INDEX dk_phase_type_uid_name (phase, type, uid, name)', __LINE__ );

    $this->query(
      'CREATE TEMPORARY TABLE temp_link '.
      'SELECT id, phase, type, uid, link AS name, filename '.
      'FROM temp_file '.
      'WHERE link IS NOT NULL',
      __LINE__
    );
    $this->query( 'ALTER TABLE temp_link ADD INDEX dk_phase_type_uid_name (phase, type, uid, name)', __LINE__ );

    // remove all links which do not have a referring file (it must have filesize 0 if it's missing)
    printf( "Removing links pointing to empty files\n" );
    $this->query(
      'CREATE TEMPORARY TABLE delete_record '.
      'SELECT temp_link.id '.
      'FROM temp_link '.
      'LEFT JOIN temp_image USING (phase, type, uid, name) '.
      'WHERE temp_image.id IS NULL',
      __LINE__
    );
    $this->query( 'ALTER TABLE delete_record ADD PRIMARY KEY (id)', __LINE__ );
    $this->query( 'DELETE FROM temp_link WHERE id IN ( SELECT id FROM delete_record )', __LINE__ );
    $this->query( 'TRUNCATE delete_record', __LINE__ );

    // remove all files that are represented by links
    printf( "Removing files pointed to by links\n" );
    $this->query(
      'INSERT INTO delete_record '.
      'SELECT DISTINCT temp_image.id '.
      'FROM temp_link '.
      'JOIN temp_image USING (phase, type, uid, name) '.
      'WHERE temp_link.name IS NOT NULL',
      __LINE__
    );
    $this->query( 'DELETE FROM temp_image WHERE id IN ( SELECT id FROM delete_record )', __LINE__ );
    $this->query( 'DROP TABLE delete_record', __LINE__ );

    $this->query( 'TRUNCATE temp_file', __LINE__ );
    $this->query( 'INSERT INTO temp_file SELECT id, phase, type, uid, name, NULL FROM temp_image', __LINE__ );
    $this->query( 'INSERT INTO temp_file SELECT id, phase, type, uid, name, filename FROM temp_link', __LINE__ );

    // create all missing interview records
    printf( "Creating missing interview records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO interview (participant_id, study_phase_id) '.
        'SELECT DISTINCT participant.id, study_phase.id '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" ',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    // create all missing exam records and images
    printf( "Creating missing carotid_intima exam records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO exam (interview_id, scan_type_id) '.
        'SELECT DISTINCT interview.id, scan_type.id '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type '.
          'ON temp_file.type = scan_type.name '.
          'AND temp_file.link LIKE CONCAT("%%", scan_type.side, "%%") '.
        'WHERE temp_file.type = "carotid_intima"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    printf( "Creating missing carotid_intima image records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO image (exam_id, filename) '.
        'SELECT exam.id, temp_file.link '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type '.
          'ON temp_file.type = scan_type.name '.
          'AND temp_file.link LIKE CONCAT("%%", scan_type.side, "%%") '.
        'JOIN exam '.
          'ON interview.id = exam.interview_id '.
          'AND scan_type.id = exam.scan_type_id '.
        'WHERE temp_file.type = "carotid_intima"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    // create all missing exam and image records
    printf( "Creating missing dxa exam records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO exam (interview_id, scan_type_id) '.
        'SELECT DISTINCT interview.id, scan_type.id '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type ON IFNULL(temp_file.link, temp_file.filename) LIKE '.
          'CONCAT( '.
            '"%%", '.
            'CONCAT_WS( "_", scan_type.name, IF( scan_type.side = "none", NULL, scan_type.side ) ), '.
            '"%%" '.
          ') '.
        'JOIN modality ON scan_type.modality_id = modality.id AND modality.name = "dxa" '.
        'WHERE temp_file.type = "dxa"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    printf( "Creating missing dxa image records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO image (exam_id, filename) '.
        'SELECT exam.id, IFNULL(temp_file.link, temp_file.filename) '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type ON IFNULL(temp_file.link, temp_file.filename) LIKE '.
          'CONCAT( '.
            '"%%", '.
            'CONCAT_WS( "_", scan_type.name, IF( scan_type.side = "none", NULL, scan_type.side ) ), '.
            '"%%" '.
          ') '.
        'JOIN modality ON scan_type.modality_id = modality.id AND modality.name = "dxa" '.
        'JOIN exam '.
          'ON interview.id = exam.interview_id '.
          'AND scan_type.id = exam.scan_type_id '.
        'WHERE temp_file.type = "dxa"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    // create all missing exam and image records
    printf( "Creating missing retinal exam records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO exam (interview_id, scan_type_id) '.
        'SELECT DISTINCT interview.id, scan_type.id '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type '.
          'ON temp_file.type = scan_type.name '.
          'AND temp_file.filename LIKE CONCAT("%%", scan_type.side, "%%") '.
        'WHERE temp_file.type = "retinal"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );

    printf( "Creating missing retinal image records\n" );
    $this->query(
      sprintf(
        'INSERT IGNORE INTO image (exam_id, filename) '.
        'SELECT DISTINCT exam.id, temp_file.filename '.
        'FROM temp_file '.
        'JOIN %s.participant USING (uid) '.
        'JOIN %s.study_phase ON temp_file.phase = study_phase.rank '.
        'JOIN %s.study ON study_phase.study_id = study.id AND study.name = "clsa" '.
        'JOIN interview '.
          'ON participant.id = interview.participant_id '.
          'AND study_phase.id = interview.study_phase_id '.
        'JOIN scan_type '.
          'ON temp_file.type = scan_type.name '.
          'AND temp_file.filename LIKE CONCAT("%%", scan_type.side, "%%") '.
        'JOIN exam '.
          'ON interview.id = exam.interview_id '.
          'AND scan_type.id = exam.scan_type_id '.
        'WHERE temp_file.type = "retinal"',
        $this->cenozo_database_name,
        $this->cenozo_database_name,
        $this->cenozo_database_name
      ),
      __LINE__
    );
  }

  public function import_opal_file( $filename )
  {
    // get the type and phase of the file from the filename
    $preg = sprintf(
      '/(%s)_([0-9])/',
      implode(
        '|',
        [
          'interview',
          'carotid_intima',
          'forearm',
          'hip',
          'lateral',
          'retinal',
          'spine',
          'wbody'
        ]
      )
    );

    $matches = [];
    if( !preg_match( $preg, $filename, $matches ) )
    {
      printf( "Error while importing file \"%s\", type and phase not found in filename.\n", $filename );
      die();
    }
    $datatype = $matches[1];
    $phase = $matches[2];

    // parse the CSV file into header and row data
    $file = file_get_contents( $filename );
    $file_lines = explode( "\n", $file );
    sort( $file_lines );
    $header = [];
    $rows = [];
    foreach( $file_lines as $line )
    {
      $data = str_getcsv( $line );
      if( !$data[0] ) continue; // ignore empty lines
      else if( 'entity_id' == $data[0] )
      {
        // parse the header
        foreach( $data as $index => $column ) $header[$column] = $index;
        continue;
      }
      else
      {
        $rows[] = $data;
      }
    }

    // now determine which type of file this is based on the header
    printf( "Importing phase %d %s data found in \"%s\"\n", $phase, $datatype, $filename );
    if( 'interview' == $datatype )
    {
      // build a lookup list of sites
      $result = $this->db->query( sprintf(
        'SELECT id, REPLACE( name, " DCS", "" ) AS name '.
        'FROM %s.site '.
        'WHERE name LIKE "%% DCS"',
        $this->cenozo_database_name
      ) );

      if( false === $result ) $this->fatal_error( $this->db->error, __LINE__ );

      $site_list = [];
      while( $row = $result->fetch_assoc() ) $site_list[$row['name']] = $row['id'];
      $result->free();

      // add site name aliases
      $site_list['SimonFraser'] = $site_list['Simon Fraser'];
      $site_list['Memorial University'] = $site_list['Memorial'];
      $site_list['University of Manitoba'] = $site_list['Manitoba'];
      $site_list['University of Victoria'] = $site_list['Victoria'];
      $site_list['BritishColumbia'] = $site_list['University of BC'];
      $site_list['British Columbia'] = $site_list['University of BC'];
      $site_list['UniversityofBC'] = $site_list['University of BC'];
      $site_list['McMaster'] = $site_list['Hamilton'];

      foreach( $rows as $row_index => $row )
      {
        $uid = $row[$header['entity_id']];
        $token = $row[$header['token']];
        $start_datetime = preg_replace( '/-[0-9]+$/', '', $row[$header['start_datetime']] );
        $end_datetime = preg_replace( '/-[0-9]+$/', '', $row[$header['end_datetime']] );
        $site = preg_replace( '/ DCS$/', '', $row[$header['site']] );
        $site_id = $site_list[$site];

        $this->query(
          sprintf(
            'UPDATE interview '.
            'JOIN %s.participant ON interview.participant_id = participant.id '.
            'JOIN %s.study_phase ON interview.study_phase_id = study_phase.id '.
            'SET site_id = %d, '.
                'token = "%s", '.
                'start_datetime = CONVERT_TZ("%s", "Canada/Eastern", "UTC"), '.
                'end_datetime = CONVERT_TZ("%s", "Canada/Eastern", "UTC") '.
            'WHERE participant.uid = "%s" '.
            'AND study_phase.rank = %d',
            $this->cenozo_database_name,
            $this->cenozo_database_name,
            $site_id,
            $token,
            $start_datetime,
            $end_datetime,
            $uid,
            $phase
          ),
          __LINE__
        );
      }
    }
    else
    {
      foreach( $rows as $row_index => $row )
      {
        $uid = $row[$header['entity_id']];
        $exam_list = [];
        foreach( ['user', 'user_1', 'user_2', 'user_3', 'user_4', 'user_5', 'user_6'] as $user_column )
        {
          if( array_key_exists( $user_column, $header ) )
          {
            $datetime_column = preg_replace( '/user/', 'datetime', $user_column );
            $datetime = NULL;
            if( 0 < strlen( $row[$header[$datetime_column]] ) )
            {
              $dt_obj = new DateTime( $row[$header[$datetime_column]] );
              $dt_obj->setTimezone( new DateTimeZone( 'UTC' ) );
              $datetime = $dt_obj->format( 'Y-m-d H:i:s' );
            }
            $side_column = preg_replace( '/user/', 'side', $user_column );
            $exam_list[] = [
              'user' => 0 < strlen( $row[$header[$user_column]] ) ? $row[$header[$user_column]] : NULL,
              'datetime' => $datetime,
              'side' =>
                array_key_exists( $side_column, $header ) ?
                strtolower( $row[$header[$side_column]] ) :
                NULL,
            ];
          }
        }

        // Create the exam records
        foreach( $exam_list as $exam )
        {
          $this->query(
            sprintf(
              'UPDATE exam '.
              'JOIN scan_type ON exam.scan_type_id = scan_type.id '.
              'JOIN interview ON exam.interview_id = interview.id '.
              'JOIN %s.participant ON interview.participant_id = participant.id '.
              'JOIN %s.study_phase ON interview.study_phase_id = study_phase.id AND study_phase.rank = %d '.
              'SET exam.interviewer = %s, exam.datetime = %s '.
              'WHERE participant.uid = "%s" '.
              'AND scan_type.name = "%s" %s',
              $this->cenozo_database_name,
              $this->cenozo_database_name,
              $phase,
              is_null( $exam['user'] ) ? 'NULL' : sprintf( '"%s"', $exam['user'] ),
              is_null( $exam['datetime'] ) ? 'NULL' : sprintf( '"%s"', $exam['datetime'] ),
              $uid,
              $datatype,
              is_null( $exam['side'] ) ? '' : sprintf( 'AND scan_type.side = "%s"', $exam['side'] )
            ),
            __LINE__
          );
        }
      }
    }
  }
}

$import = new import();
$import->read_settings();
$import->connect_database();

if( 'generate' == $argv[1] ) $import->generate_image_file( $argv[2], $argv[3] );
else if( 'read' == $argv[1] ) $import->import_image_file( $argv[2] );
else if( 'import' == $argv[1] ) $import->import_opal_file( $argv[2] );

$import->disconnect_database();
