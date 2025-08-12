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
    $has_codes = false;
    $has_selections = false;
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

        // determine if the scan type has codes
        $code_mod = lib::create( 'database\modifier' );
        $code_mod->join( 'scan_type', 'code_group.scan_type_id', 'scan_type.id' );
        $code_mod->where( 'scan_type.name', '=', $scan_type_name );
        if( 0 < $code_group_class_name::count( $code_mod ) ) $has_codes = true;

        // determine if the scan type has selections
        $selection_mod = lib::create( 'database\modifier' );
        $selection_mod->join( 'scan_type', 'selection.scan_type_id', 'scan_type.id' );
        $selection_mod->where( 'scan_type.name', '=', $scan_type_name );
        if( 0 < $selection_class_name::count( $selection_mod ) ) $has_selections = true;
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
    if( in_array( $scan_type_name, ['forearm', 'hip', 'retinal', 'carotid_intima'] ) )
      $select->add_column( 'scan_type.side', 'Side', false );
    $select->add_column( 'image.filename', 'Filename', false );
    $select->add_column( 'exam.interviewer', 'Interviewer', false );
    $select->add_column(
      sprintf( '%s.%s', $analysis_type, $apex_user ? 'pass' : 'rating' ),
      $apex_user ? 'Pass' : 'Rating',
      false
    );
    if( !$apex_user ) $select->add_column( 'analysis.quality', 'Quality', false );

    if( $has_codes )
    {
      $select->add_column(
        'GROUP_CONCAT( '.
          'code.name '.
          'ORDER BY code.name '.
          'SEPARATOR ";" ) ',
        'Codes',
        false
      );
    }

    if( $has_selections )
    {
      $select->add_column(
        'GROUP_CONCAT( '.
          'CONCAT( selection.name, ":", selection_option.name ) '.
          'ORDER BY selection.name '.
          'SEPARATOR ";" '.
        ') ',
        'Selections',
        false
      );
    }

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

    if( $has_codes )
    {
      // add all codes
      $modifier->left_join(
        sprintf( '%s_has_code', $analysis_type ),
        sprintf( '%s.id', $analysis_type ),
        sprintf( '%s_has_code.%s_id', $analysis_type, $analysis_type )
      );
      $modifier->left_join( 'code', sprintf( '%s_has_code.code_id', $analysis_type ), 'code.id' );
    }

    if( $has_selections )
    {
      // add all selections
      $modifier->left_join(
        sprintf( '%s_selection', $analysis_type ),
        sprintf( '%s.id', $analysis_type ),
        sprintf( '%s_selection.%s_id', $analysis_type, $analysis_type )
      );
      $modifier->left_join( 'selection', sprintf( '%s_selection.selection_id', $analysis_type ), 'selection.id' );
      $modifier->left_join(
        'selection_option',
        sprintf( '%s_selection.selection_option_id', $analysis_type ),
        'selection_option.id'
      );
    }

    $modifier->where( 'scan_type.name', '=', $scan_type_name );
    $modifier->where( 'interview.study_phase_id', '=', $db_study_phase->id );
    $modifier->where( sprintf( '%s.end_datetime', $review_type ), '!=', NULL );

    $modifier->group( 'analysis.id' );
    $modifier->order( 'uid' );
    $modifier->order( 'exam.datetime' );
    $modifier->order( 'user.name' );
    $modifier->order( 'image.filename' );

    $this->apply_restrictions( $modifier );

    $this->add_table_from_select( NULL, $analysis_class_name::select( $select, $modifier ) );
  }
}
