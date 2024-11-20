<?php
/**
 * apex_analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

/**
 * apex_analysis: record
 */
class apex_analysis extends \cenozo\database\record
{
  /**
   * Returns a list of all codes for this apex_analysis
   */
  public function get_codes()
  {
    $code_list = [];
    $db_scan_type = $this->get_image()->get_exam()->get_scan_type();
    $code_group_mod = lib::create( 'database\modifier' );
    $code_group_mod->where( 'code_group.apex', '=', true );
    $code_group_mod->order( 'rank' );
    foreach( $db_scan_type->get_code_group_object_list( $code_group_mod ) as $db_code_group )
    {
      $group = [
        'rank' => $db_code_group->rank,
        'name' => $db_code_group->name,
        'value' => $db_code_group->value,
        'description' => $db_code_group->description,
        'code_list' => []
      ];

      $code_sel = lib::create( 'database\select' );
      $code_sel->add_table_column( 'code', 'id' );
      $code_sel->add_column( 'rank' );
      $code_sel->add_column( 'name' );
      $code_sel->add_column( 'value' );
      $code_sel->add_column( 'description' );
      $code_sel->add_column( 'apex_analysis_has_code.apex_analysis_id IS NOT NULL', 'selected', false, 'boolean' );
      $code_mod = lib::create( 'database\modifier' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'code.id', '=', 'apex_analysis_has_code.code_id', false );
      $join_mod->where( 'apex_analysis_has_code.apex_analysis_id', '=', $this->id );
      $code_mod->join_modifier( 'apex_analysis_has_code', $join_mod, 'left' );
      $code_mod->order( "code.rank" );
      foreach( $db_code_group->get_code_list($code_sel, $code_mod) as $code )
      {
        $group['code_list'][] = [
          'id' => $code['id'],
          'rank' => $code['rank'],
          'name' => $code['name'],
          'value' => $code['value'],
          'description' => $code['description'],
          'selected' => $code['selected']
        ];
      }

      $code_list[] = $group;
    }

    return $code_list;
  }

  /**
   * Returns the image associated with this analysis and the base paired image (for forearm, hip and spine only)
   *
   * @param database\apex_host An optional check to see if the images have been uploaded to the host
   * @return associative array
   */
  public function get_images_for_apex( $db_apex_host = NULL )
  {
    $image_class_name = lib::get_class_name( 'database\image' );

    $db_image = $this->get_image();
    $db_exam = $this->get_apex_review()->get_exam();
    $db_scan_type = $db_exam->get_scan_type();
    $db_interview = $db_exam->get_interview();
    $uid = $db_interview->get_participant()->uid;
    $db_study_phase = $db_interview->get_study_phase();
    $apex_manager = is_null( $db_apex_host ) ? NULL : lib::create( 'business\apex_manager', $db_apex_host );

    // determine whether the image has a number
    $parts = explode( '_', $db_image->filename );
    $last_part = end( $parts );
    $number = preg_match( '/^[0-9]+$/', $last_part ) ? $last_part : NULL;

    $image = [
      'uid' => $uid,
      'phase' => [
        'rank' => $db_study_phase->rank,
        'code' => $db_study_phase->code,
        'name' => $db_study_phase->name
      ],
      'type' => $db_scan_type->name,
      'side' => $db_scan_type->side,
      'number' => $number,
      'reanalysed' => false,
      'filename' => sprintf(
        '/%d/dxa/%s/%s',
        $db_study_phase->rank,
        $uid,
        $db_image->filename
      )
    ];

    // if an apex host is provided then check if the image is on the workstation
    if( !is_null( $apex_manager ) )
      $image['uploaded'] = $apex_manager->check_for_scan( $image['filename'] );

    $images[] = $image;

    // get the base paired file, if necessary
    if( in_array( $db_scan_type->name, ['forearm', 'hip', 'spine'] ) )
    {
      // get the earliest passed analysis for this participant/scan-type
      $select = lib::create( 'database\select' );
      $select->add_table_column( 'study_phase', 'rank' );
      $select->add_table_column( 'study_phase', 'code' );
      $select->add_table_column( 'study_phase', 'name' );
      $select->add_table_column( 'image', 'filename' );

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'apex_analysis', 'image.id', 'apex_analysis.image_id' );
      $modifier->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
      $modifier->join( 'exam', 'apex_review.exam_id', 'exam.id' );
      $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
      $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
      $modifier->where( 'apex_analysis.pass', '=', true );
      $modifier->where( 'exam.scan_type_id', '=', $db_scan_type->id );
      $modifier->where( 'interview.participant_id', '=', $db_interview->participant_id );
      $modifier->order( 'study_phase.rank' ); // get the lowest study phase rank
      $modifier->order( 'image.filename' ); // get the lowest image number (if there is one)
      $modifier->limit( 1 );

      $base_image_list = $image_class_name::select( $select, $modifier );

      if( 1 == count( $base_image_list ) )
      {
        $base_image = current( $base_image_list );
        $parts = explode( '_', $db_image->filename );
        $last_part = end( $parts );
        $base_number = preg_match( '/^[0-9]+$/', $last_part ) ? $last_part : NULL;

        $image = [
          'uid' => $uid,
          'phase' => [
            'rank' => $base_image['rank'],
            'code' => $base_image['code'],
            'name' => $base_image['name']
          ],
          'type' => $db_scan_type->name,
          'side' => $db_scan_type->side,
          'number' => $base_number,
          'reanalysed' => true,
          'filename' => sprintf(
            '/%d/dxa/%s/%s',
            $base_image['rank'],
            $uid,
            $base_image['filename']
          )
        ];

        // if an apex host is provided then check if the image is on the workstation
        if( !is_null( $apex_manager ) )
          $image['uploaded'] = $apex_manager->check_for_scan( $image['filename'] );

        $images[] = $image;
      }
    }

    return $images;
  }
}
