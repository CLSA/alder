<?php
/**
 * analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\business\report;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Contact report
 */
class analysis extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $apex_user = $this->db_user->get_apex_user();
    $review_type = $apex_user ? 'apex_review' : 'review';
    $analysis_type = $apex_user ? 'apex_analysis' : 'analysis';

    $study_class_name = lib::get_class_name( 'database\study' );
    $study_phase_class_name = lib::get_class_name( 'database\study_phase' );
    $code_group_class_name = lib::get_class_name( 'database\code_group' );
    $selection_class_name = lib::get_class_name( 'database\selection' );
    $analysis_class_name = lib::get_class_name( sprintf( 'database\%s', $analysis_type ) );

    $db_study = $study_class_name::get_unique_record( 'name', 'CLSA' );

    // determine scan type and study phase restrictions from the restriction list
    $scan_type_name = NULL;
    $scan_type_side = NULL;
    $code_list = [];
    $selection_list = [];
    $db_study_phase = NULL;
    foreach( $this->get_restriction_list( true ) as $restriction )
    {
      if( 'scan_type' == $restriction['name'] )
      {
        $scan_type_name = preg_replace(
          ['/dxa /', '/ /'],
          ['', '_'],
          strtolower( $restriction['value'] )
        );
        $scan_type_side = 'none';

        // check for a side in the scan type name
        $matches = NULL;
        if( preg_match( '/(.+)_\((.+)\)/', $scan_type_name, $matches ) )
        {
          $scan_type_name = $matches[1];
          $scan_type_side = $matches[2];
        }

        // get a list of the scan type's codes
        $code_sel = lib::create( 'database\select' );
        $code_sel->add_table_column( 'code', 'id' );
        $code_sel->add_table_column( 'code', 'name' );
        $code_mod = lib::create( 'database\modifier' );
        $code_mod->join( 'scan_type', 'code_group.scan_type_id', 'scan_type.id' );
        $code_mod->join( 'code', 'code_group.id', 'code.code_group_id' );
        $code_mod->where( 'scan_type.name', '=', $scan_type_name );
        $code_mod->where( 'scan_type.side', '=', $scan_type_side );
        $code_mod->order( 'code_group.rank' );
        $code_mod->order( 'code.rank' );
        foreach( $code_group_class_name::select( $code_sel, $code_mod ) as $code )
          $code_list[$code['id']] = $code['name'];

        // get a list of the scan type's selections
        $selection_sel = lib::create( 'database\select' );
        $selection_sel->add_column( 'id' );
        $selection_sel->add_column( 'name' );
        $selection_mod = lib::create( 'database\modifier' );
        $selection_mod->join( 'scan_type', 'selection.scan_type_id', 'scan_type.id' );
        $selection_mod->where( 'scan_type.name', '=', $scan_type_name );
        $selection_mod->where( 'scan_type.side', '=', $scan_type_side );
        $selection_mod->order( 'selection.rank' );
        foreach( $selection_class_name::select( $selection_sel, $selection_mod ) as $selection )
          $selection_list[$selection['id']] = $selection['name'];
      }
      else if( 'study_phase' == $restriction['name'] )
      {
        $db_study_phase = $study_phase_class_name::get_unique_record(
          ['study_id', 'name'],
          [$db_study->id, $restriction['value']]
        );
      }
    }

    $select = lib::create( 'database\select' );
    $modifier = lib::create( 'database\modifier' );

    $select->from( $analysis_type );
    $select->add_column( 'user.name', 'Reviewer', false );
    $select->add_column( 'participant.uid', 'UID', false );
    $select->add_column( 'site.name', 'Site', false );
    $select->add_column( 'image.filename', 'Filename', false );
    $select->add_column( 'exam.interviewer', 'Interviewer', false );
    $select->add_column(
      sprintf( '%s.%s', $analysis_type, $apex_user ? 'pass' : 'rating' ),
      $apex_user ? 'Pass' : 'Rating',
      false
    );
    if( !$apex_user ) $select->add_column( 'analysis.quality', 'Quality', false );

    $select->add_column(
      $this->get_datetime_column( 'exam.datetime', 'datetime' ),
      'Exam Date & Time',
      false
    );
    $select->add_column(
      $this->get_datetime_column( sprintf( '%s.end_datetime', $review_type ), 'datetime' ),
      'Review Date & Time',
      false
    );

    $modifier->join( 'image', sprintf( '%s.image_id', $analysis_type ), 'image.id' );
    $modifier->join(
      $review_type,
      sprintf( '%s.%s_id', $analysis_type, $review_type ),
      sprintf( '%s.id', $review_type )
    );
    $modifier->join( 'user', sprintf( '%s.user_id', $review_type ), 'user.id' );
    $modifier->join( 'exam', sprintf( '%s.exam_id', $review_type ), 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->left_join( 'site', 'interview.site_id', 'site.id' );

    // add each code as a new column
    foreach( $code_list as $id => $name )
    {
      $select->add_column( sprintf( 'IF( has_code_%d.code_id IS NULL, "n", "y" )', $id ), $name, false );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where(
        sprintf( '%s.id', $analysis_type ),
        '=',
        sprintf( 'has_code_%d.%s_id', $id, $analysis_type ),
        false
      );
      $join_mod->where( sprintf( 'has_code_%d.code_id', $id ), '=', $id );
      $modifier->join_modifier(
        sprintf( '%s_has_code', $analysis_type ),
        $join_mod,
        'left',
        sprintf( 'has_code_%d', $id ),
      );
    }

    // add each selection as a new column
    foreach( $selection_list as $id => $name )
    {
      $select->add_column( sprintf( 'IFNULL( selection_option_%d.name, "" )', $id ), $name, false );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where(
        sprintf( '%s.id', $analysis_type ),
        '=',
        sprintf( 'has_selection_%d.%s_id', $id, $analysis_type ),
        false
      );
      $join_mod->where( sprintf( 'has_selection_%d.selection_id', $id ), '=', $id );
      $modifier->join_modifier(
        sprintf( '%s_selection', $analysis_type ),
        $join_mod,
        'left',
        sprintf( 'has_selection_%d', $id ),
      );
      $modifier->left_join(
        'selection_option',
        sprintf( 'has_selection_%d.selection_option_id', $id ),
        sprintf( 'selection_option_%d.id', $id ),
        sprintf( 'selection_option_%d', $id ),
      );
    }

    $modifier->where( 'scan_type.name', '=', $scan_type_name );
    $modifier->where( 'scan_type.side', '=', $scan_type_side );
    $modifier->where( 'interview.study_phase_id', '=', $db_study_phase->id );
    $modifier->where( sprintf( '%s.end_datetime', $review_type ), '!=', NULL );

    $modifier->group( sprintf( '%s.id', $analysis_type ) );
    $modifier->order( 'uid' );
    $modifier->order( 'exam.datetime' );
    $modifier->order( 'user.name' );
    $modifier->order( 'image.filename' );

    $this->apply_restrictions( $modifier );

    $this->add_table_from_select( NULL, $analysis_class_name::select( $select, $modifier ) );
  }
}
