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
   * Override parent save method to make sure only one analysis can have a download datetime
   * 
   * @throws exception\permission
   * @access public
   */
  public function save()
  {
    $setting_download_datetime =
      $this->has_column_changed( 'download_datetime' ) &&
      !is_null( $this->download_datetime );

    parent::save();

    if( $setting_download_datetime )
    {
      // remove the download datetime from all other analysis records belonging to this exam
      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
      $modifier->where( 'apex_review.exam_id', '=', $this->get_apex_review()->exam_id );
      $modifier->where( 'apex_analysis.download_datetime', '!=', NULL );
      $modifier->where( 'apex_analysis.id', '!=', $this->id );
      $sql = sprintf(
        'UPDATE apex_analysis %s SET download_datetime = NULL %s',
        $modifier->get_join(),
        $modifier->get_sql_without_joins()
      );
      static::db()->execute( $sql );
    }
  }

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
   * Returns a list of all selections for this apex_analysis
   */
  public function get_selections()
  {
    $apex_analysis_selection_class_name = lib::get_class_name( 'database\apex_analysis_selection' );

    $selection_list = [];
    $db_scan_type = $this->get_image()->get_exam()->get_scan_type();
    $selection_mod = lib::create( 'database\modifier' );
    $selection_mod->where( 'selection.apex', '=', false );
    $selection_mod->order( 'rank' );
    foreach( $db_scan_type->get_selection_object_list( $selection_mod ) as $db_selection )
    {
      // get the record's option for this selection
      $db_apex_analysis_selection = $apex_analysis_selection_class_name::get_unique_record(
        ['apex_analysis_id', 'selection_id'],
        [$this->id, $db_selection->id]
      );

      $selection = [
        'id' => $db_selection->id,
        'rank' => $db_selection->rank,
        'name' => $db_selection->name,
        'description' => $db_selection->description,
        'option_list' => [],
        'selection_option_id' =>
          is_null( $db_apex_analysis_selection ) ? NULL : $db_apex_analysis_selection->selection_option_id
      ];

      $option_sel = lib::create( 'database\select' );
      $option_sel->add_table_column( 'selection_option', 'id' );
      $option_sel->add_column( 'rank' );
      $option_sel->add_column( 'name' );
      $option_mod = lib::create( 'database\modifier' );
      $option_mod->order( "selection_option.rank" );
      foreach( $db_selection->get_selection_option_list($option_sel, $option_mod) as $option )
      {
        $selection['option_list'][] = [
          'id' => $option['id'],
          'name' => $option['name']
        ];
      }

      $selection_list[] = $selection;
    }

    return $selection_list;
  }

  /**
   * Returns the image associated with this analysis and the base paired image (for forearm, hip and spine only)
   *
   * @param boolean $current_image_only If true then the current analysis image is returned instead of an array
   * @return array or [array]
   */
  public function get_images_for_apex( $current_image_only = false )
  {
    $image_class_name = lib::get_class_name( 'database\image' );

    $db_image = $this->get_image();
    $db_apex_review = $this->get_apex_review();
    $db_exam = $db_apex_review->get_exam();
    $db_interview = $db_exam->get_interview();
    $uid = $db_interview->get_participant()->uid;
    $db_study_phase = $db_interview->get_study_phase();
    $db_apex_host = $db_apex_review->get_apex_host();
    $apex_manager = is_null( $db_apex_host ) ? NULL : lib::create( 'business\apex_manager', $db_apex_host );

    // parse the image filename to get all image data
    $data = util::parse_dxa_filename( sprintf(
      '/%d/dxa/%s/%s',
      $db_study_phase->rank,
      $uid,
      $db_image->filename
    ) );

    // if an apex host is provided then check if the image is on the workstation
    if( !is_null( $apex_manager ) ) $data['uploaded'] = $apex_manager->check_for_scan( $data['filename'] );

    // we can return now if only the current image is required
    if( $current_image_only ) return $data;

    $images_for_apex = [$data];

    // get the base paired file, if necessary
    $db_scan_type = $db_exam->get_scan_type();
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

        $data = util::parse_dxa_filename( sprintf(
          '/%d/dxa/%s/%s',
          $base_image['rank'],
          $uid,
          preg_replace( '/(_[0-9]+)?\.dcm/', '.reanalysed.dcm', $base_image['filename'] )
        ) );

        // if an apex host is provided then check if the image is on the workstation
        if( !is_null( $apex_manager ) )
          $data['uploaded'] = $apex_manager->check_for_scan( $data['filename'] );

        $images_for_apex[] = $data;
      }
    }

    return $images_for_apex;
  }
}
