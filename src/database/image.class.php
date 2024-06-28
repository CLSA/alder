<?php
/**
 * image.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @fileimage
 */

namespace alder\database;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * image: record
 */
class image extends \cenozo\database\record
{
  /**
   * Adds any image files found in the data path which are newer than the last time this method was called
   * 
   * @access public
   */
  public static function update_image_file_list()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $study_phase_class_name = lib::get_class_name( 'database\study_phase' );
    $scan_type_class_name = lib::get_class_name( 'database\scan_type' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $last_sync_file = $setting_manager->get_setting( 'general', 'last_sync_file' );
    $result = 0;

    // get a list of all study phases
    $study_phase_sel = lib::create( 'database\select' );
    $study_phase_sel->add_column( 'rank' );
    $study_phase_sel->add_column( 'name' );
    $study_phase_mod = lib::create( 'database\modifier' );
    $study_phase_mod->order( 'rank' );
    $study_phase_list = [];
    foreach( $study_phase_class_name::select( $study_phase_sel, $study_phase_mod ) as $study_phase )
      $study_phase_list[$study_phase['rank']] = $study_phase['name'];

    // get a list of all scan types
    $scan_type_sel = lib::create( 'database\select' );
    $scan_type_sel->add_column( 'id' );
    $scan_type_sel->add_column( 'name' );
    $scan_type_mod = lib::create( 'database\modifier' );
    $scan_type_mod->order( 'name' );
    $scan_type_list = [];
    foreach( $scan_type_class_name::select( $scan_type_sel, $scan_type_mod ) as $scan_type )
      $scan_type_list[$scan_type['name']] = $scan_type['id'];

    // create a temporary table to put raw data into
    static::db()->execute(
      'CREATE TEMPORARY TABLE temp_image ( '.
        'id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, '.
        'study_phase_id INT(10) UNSIGNED NOT NULL, '.
        'participant_id INT(10) UNSIGNED NOT NULL, '.
        'scan_type_id INT(10) UNSIGNED NOT NULL, '.
        'stage_name VARCHAR(255) NOT NULL, '.
        'side ENUM("right", "left", "none") NOT NULL, '.
        'filename VARCHAR(127) NOT NULL, '.
        'link_source VARCHAR(127) NULL, '.
        'path VARCHAR(511) NOT NULL, '.
        'PRIMARY KEY ( id ), '.
        'KEY dk_study_phase_id ( study_phase_id ), '.
        'KEY dk_participant_id ( participant_id ), '.
        'KEY dk_filename ( filename ), '.
        'KEY dk_link_source ( link_source )'.
      ')'
    );

    $image_type_list = ['carotid_intima', 'dxa', 'retinal'];

    foreach( $image_type_list as $image_type )
    {
      // If the last sync file is present then only get files which were created after it was
      $command = sprintf(
        'find %s/[0-9]/%s -type f,l %s -printf "%s"',
        IMAGES_PATH,
        $image_type,
        file_exists( $last_sync_file ) ? sprintf( '-newer %s', $last_sync_file ) : '',
        '%p\t%l\t%y\n'
      );
      $file_list = array();
      exec( $command, $file_list );

      // organize files by participant
      $insert_list = array();
      foreach( $file_list as $row )
      {
        $parts = explode( "\t", $row );
        $path = $parts[0];
        $directory = dirname( $path );
        $filename = basename( $path );
        $link_source = 'f' == $parts[1] ? NULL : $parts[1];

        // get the phase and uid from the directory
        $matches = NULL;
        preg_match(
          sprintf( '#%s/([0-9])/%s/([A-Z][0-9]{6})/.+#', IMAGES_PATH, $image_type ),
          $directory,
          $matches
        );

        // Since phase and uid come from file names already scrubbed by the data librarian, they
        // can be trusted to always be valid.
        $participant_id = $participant_class_name::get_unique_record( 'uid', $matches[1] )->id;
        $study_phase_id = $study_phase_list[$matches[2]]; // convert from phase rank to name

        // get the side from the filename
        $side = preg_match( '#(right|left)#', $filename, $matches ) ? $matches[0] : 'none';

        // get the scan type from the full path
        $scan_type_name = NULL;
        if( preg_match( '/carotid_intima/', $path ) ) $scan_type_name = 'carotid_intima';
        else if( preg_match( '/forearm/', $path ) ) $scan_type_name = 'forearm';
        else if( preg_match( '/hip/', $path ) ) $scan_type_name = 'hip';
        else if( preg_match( '/lateral/', $path ) ) $scan_type_name = 'lateral';
        else if( preg_match( '/spine/', $path ) ) $scan_type_name = 'spine';
        else if( preg_match( '/wbody/', $path ) ) $scan_type_name = 'wbody';
        else if( preg_match( '/retinal/', $path ) ) $scan_type_name = 'retinal';

        // ignore files that don't have one of the scan types listed above
        if( is_null( $scan_type_name ) ) continue;

        $scan_type_id = $scan_type_list[$scan_type_name];

        // get the stage from the full path
        $stage_name = NULL;
        if( preg_match( '/carotid_intima/', $path ) ) $stage_name = 'Carotid Intima';
        else if( preg_match( '/hip/', $path ) ) $stage_name = 'Hip Bone Density Scan';
        else if( preg_match( '/retinal_right/', $path ) ) $stage_name = 'Retinal Camera (right)';
        else if( preg_match( '/retinal_left/', $path ) ) $stage_name = 'Retinal Camera (left)';
        else $stage_name = 'Other DXA Scans';

        $insert_list[] = sprintf(
          '( %s, %s, %s, %s, %s, $s, $s, $s )',
          static::db()->format_string( $study_phase_id ),
          static::db()->format_string( $participant_id ),
          static::db()->format_string( $scan_type_id ),
          static::db()->format_string( $stage_name ),
          static::db()->format_string( $side ),
          static::db()->format_string( $filename ),
          is_null( $link_source ) ? 'NULL' : static::db()->format_string( $link_source ),
          static::db()->format_string( $path )
        );

        // divide inserting data into groups of 1000 records
        if( 1000 <= count( $insert_list ) )
        {
          static::db()->execute( sprintf(
            'INSERT INTO temp_image( '.
              'study_phase_id, participant_id, scan_type_id, stage_name, side, filename, link_source, path '.
            ') VALUES %s',
            implode( ',', $insert_list )
          ) );
          $insert_list = array();
        }
      }

      if( 0 < count( $insert_list ) )
      {
        static::db()->execute( sprintf(
          'INSERT INTO temp_image( '.
            'study_phase_id, participant_id, scan_type_id, stage_name, side, filename, link_source, path '.
          ') VALUES %s',
          implode( ',', $insert_list )
        ) );
      }
    }

    // delete all files pointed to by symlinks
    static::db()->execute(
      'DELETE FROM temp_image WHERE id IN ( '.
        'SELECT file.id '.
        'FROM temp_image AS link '.
        'JOIN temp_image AS file '.
          'ON link.study_phase_id = file.study_phase_id '.
          'AND link.participant_id = file.participant_id '.
          'AND link.scan_type_id = file.scan_type_id '.
          'AND link.link_source = file.filename '.
      ')'
    );

    // Because we're joining to Pine's database we'll need to manually refer to database names in queries below
    $alder_db = static::db()->get_name();
    $pine_db = str_replace( 'alder', 'pine', $alder_db );
    $cenozo_db = str_replace( 'alder', 'cenozo', $alder_db );

    // 1) CREATE INTERVIEW RECORDS
    $select = lib::create( 'database\select' );
    $select->from( 'temp_image' );
    $select->set_distinct( true );
    $select->add_column( 'temp_image.participant_id' );
    $select->add_column( 'temp_image.study_phase_id' );
    $select->add_column( 'response.site_id' );
    $select->add_column( 'respondent.token' );
    $select->add_column( 'respondent.start_datetime' );
    $select->add_column( 'respondent.end_datetime' );

    // join to the respondent and response records
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( sprintf( '%s.study_phase', $cenozo_db ), 'temp_image.study_phase_id', 'study_phase.id' );
    $modifier->join(
      sprintf( '%s.respondent', $pine_db ),
      'temp_image.participant_id',
      'respondent.participant_id'
    );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire.name', '=', 'CONCAT( study_phase.name, " Site" )', false );
    $join_mod->where( 'respondent.qnaire_id', '=', 'qnaire.id', false );
    $modifier->join_modifier( sprintf( '%s.qnaire', $pine_db ), $join_mod );
    $modifier->join( 'response', 'respondent.id', 'response.respondent_id' );

    static::db()->execute(
      sprintf(
        'INSERT IGNORE INTO interview( '.
          'participant_id, study_phase_id, site_id, token, start_datetime, end_datetime '.
        ') ',
        '%s %s',
        $select->get_sql(),
        $modifier->get_sql()
      ),
      false
    );

    // 2) CREATE EXAM RECORDS
    $select = lib::create( 'database\select' );
    $select->from( 'temp_image' );
    $select->set_distinct( true );
    $select->add_column( 'interview.id' );
    $select->add_column( 'temp_image.scan_type_id' );
    $select->add_column( 'temp_image.side' );
    $select->add_column( 'response_stage.username' );
    $select->add_column( 'response_stage.start_datetime' );
    $select->add_column( 'response_stage.end_datetime' );

    // join to the interview record (created above)
    $modifier = lib::create( 'database\modifier' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'temp_image.study_phase_id', '=', 'interview.study_phase_id', false );
    $join_mod->where( 'temp_image.participant_id', '=', 'interview.participant_id', false );
    $modifier->join_modifier( sprintf( '%s.interview', $alder_db ), $join_mod );

    // join to the response_stage record
    $modifier->join( '%s.respondent', 'temp_image.participant_id', 'respondent.participant_id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire.name', '=', 'CONCAT( study_phase.name, " Site" )', false );
    $join_mod->where( 'respondent.qnaire_id', '=', 'qnaire.id', false );
    $modifier->join_modifier( '%s.qnaire', $join_mod );
    $modifier->join( 'response', 'respondent.id', 'response.respondent_id' );
    $modifier->join( 'response_stage', 'response.id', 'response_stage.response_id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'response_stage.stage_id', '=', 'stage.id', false );
    $join_mod->where( 'temp_image.stage_name', '=', 'stage.name' );
    $modifier->join_modifier( 'stage', $join_mod );

    static::db()->execute(
      sprintf(
        'INSERT IGNORE INTO exam( '.
          'interview_id, scan_type_id, side, interviewer, start_datetime, end_datetime '.
        ') ',
        '%s %s',
        $select->get_sql(),
        $modifier->get_sql()
      ),
      false
    );

    // 3) CREATE IMAGE RECORDS
    $select = lib::create( 'database\select' );
    $select->from( 'temp_image' );
    $select->add_column( 'exam.id' );
    $select->add_column( 'temp_image.path' );
    
    // join to the exam record (created above)
    $modifier = lib::create( 'database\modifier' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'temp_image.study_phase_id', '=', 'interview.study_phase_id', false );
    $join_mod->where( 'temp_image.participant_id', '=', 'interview.participant_id', false );
    $modifier->join_modifier( sprintf( '%s.interview', $alder_db ), $join_mod );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'interview.id', '=', 'exam.interview_id', false );
    $join_mod->where( 'temp_image.scan_type_id', '=', 'exam.scan_type_id', false );
    $join_mod->where( 'temp_image.side', '=', 'exam.side', false );
    $modifier->join_modifier( sprintf( '%s.exam', $alder_db ), $join_mod );

    static::db()->execute(
      sprintf(
        'INSERT IGNORE INTO image( exam_id, path ) '.
        '%s %s',
        $select->get_sql(),
        $modifier->get_sql()
      ),
      false
    );
    
    // We don't need the temp_image table anymore
    static::db()->execute( 'DROP TABLE temp_image' );

    // now update the sync file to track when the last sync was done
    if( !file_exists( $last_sync_file ) )
    {
      file_put_contents(
        $last_sync_file,
        'This file is used to track which image files have been read into the database, DO NOT REMOVE.'
      );
    }
    touch( $last_sync_file );

    return $result;
  }
}
