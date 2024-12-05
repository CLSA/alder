<?php
/**
 * base_review.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

abstract class base_review extends \cenozo\database\record
{
  /**
   * Override parent method
   */
  public function save()
  {
    // make sure the start_datetime is set
    if( is_null( $this->start_datetime ) ) $this->start_datetime = util::get_datetime_object();

    parent::save();
  }

  /**
   * Returns the previous and next interview and exam related to this review (by user)
   */
  public function get_neighbouring_reviews()
  {
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $exam_class_name = lib::get_class_name( 'database\exam' );

    $review_table = static::get_table_name();
    $db_current_exam = $this->get_exam();
    $db_current_scan_type = $db_current_exam->get_scan_type();
    $db_current_interview = $db_current_exam->get_interview();
    $db_current_participant = $db_current_interview->get_participant();
    $current_scan_type = $db_current_scan_type->name . $db_current_scan_type->side;

    $neighbours = [
      'prev_interview_review_id' => NULL,
      'prev_exam_review_id' => NULL,
      'next_exam_review_id' => NULL,
      'next_interview_review_id' => NULL
    ];

    // Get the previous and next interviews for this user.
    // This is done by getting the previous or next UID in alphabetical order that the user of the current
    // review is also assigned to, then returning the first review in scan-type alphabetical order.

    $base_interview_mod = lib::create( 'database\modifier' );
    $base_interview_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
    $base_interview_mod->join( 'exam', 'interview.id', 'exam.interview_id' );
    $base_interview_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    $base_interview_mod->where( 'interview.study_phase_id', '=', $db_current_interview->study_phase_id );
    $base_interview_mod->where( sprintf( '%s.user_id', $review_table ), '=', $this->user_id );
    $base_interview_mod->limit( 1 );

    $base_review_mod = lib::create( 'database\modifier' );
    $base_review_mod->join( 'exam', 'interview.id', 'exam.interview_id' );
    $base_review_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $base_review_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    $base_review_mod->where( sprintf( '%s.user_id', $review_table ), '=', $this->user_id );
    $base_review_mod->order( 'scan_type.name' );
    $base_review_mod->order( 'scan_type.side' );
    $base_review_mod->limit( 1 );

    $interview_sel = lib::create( 'database\select' );
    $interview_sel->add_column( 'id' );
    $interview_mod = clone $base_interview_mod;
    $interview_mod->where( 'participant.uid', '<', $db_current_participant->uid );
    $interview_mod->order_desc( 'participant.uid' );

    $interview_list = $interview_class_name::select( $interview_sel, $interview_mod );
    if( 0 < count( $interview_list ) )
    {
      $prev_interview_id = current( $interview_list )['id'];

      // now use the previous interview ID to get the previous review
      $review_sel = lib::create( 'database\select' );
      $review_sel->add_table_column( $review_table, 'id' );
      $review_mod = clone $base_review_mod;
      $review_mod->where( 'interview.id', '=', $prev_interview_id );

      $review_list = $interview_class_name::select( $review_sel, $review_mod );
      if( 0 < count( $review_list ) ) $neighbours['prev_interview_review_id'] = current( $review_list )['id'];
    }

    $interview_sel = lib::create( 'database\select' );
    $interview_sel->add_column( 'id' );
    $interview_mod = clone $base_interview_mod;
    $interview_mod->where( 'participant.uid', '>', $db_current_participant->uid );
    $interview_mod->order( 'participant.uid' );

    $interview_list = $interview_class_name::select( $interview_sel, $interview_mod );
    if( 0 < count( $interview_list ) )
    {
      $next_interview_id = current( $interview_list )['id'];

      // now use the next interview ID to get the next review
      $review_sel = lib::create( 'database\select' );
      $review_sel->add_table_column( $review_table, 'id' );
      $review_mod = clone $base_review_mod;
      $review_mod->where( 'interview.id', '=', $next_interview_id );

      $review_list = $interview_class_name::select( $review_sel, $review_mod );
      if( 0 < count( $review_list ) ) $neighbours['next_interview_review_id'] = current( $review_list )['id'];
    }

    // Get the previous and next exam for this user.
    // This is done by getting the previous or next exam belonging to the same interview in scan-type
    // alphabetical order that the user is also assigned to which also belongs to the same interview
    // as the current review.

    $base_exam_mod = lib::create( 'database\modifier' );
    $base_exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $base_exam_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    $base_exam_mod->where( 'exam.interview_id', '=', $db_current_interview->id );
    $base_exam_mod->where( sprintf( '%s.user_id', $review_table ), '=', $this->user_id );
    $base_exam_mod->limit( 1 );

    $base_review_mod = lib::create( 'database\modifier' );
    $base_review_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $base_review_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    $base_review_mod->where( sprintf( '%s.user_id', $review_table ), '=', $this->user_id );
    $base_review_mod->order( 'scan_type.name' );
    $base_review_mod->order( 'scan_type.side' );
    $base_review_mod->limit( 1 );

    $exam_sel = lib::create( 'database\select' );
    $exam_sel->add_column( 'id' );
    $exam_mod = clone $base_exam_mod;
    $exam_mod->where( 'CONCAT( scan_type.name, scan_type.side )', '<', $current_scan_type );
    $exam_mod->order_desc( 'CONCAT( scan_type.name, scan_type.side )' );
    $exam_list = $exam_class_name::select( $exam_sel, $exam_mod );
    if( 0 < count( $exam_list ) )
    {
      $prev_exam_id = current( $exam_list )['id'];

      // now use the previous exam ID to get the previous review
      $review_sel = lib::create( 'database\select' );
      $review_sel->add_table_column( $review_table, 'id' );
      $review_mod = clone $base_review_mod;
      $review_mod->where( 'exam.id', '=', $prev_exam_id );

      $review_list = $exam_class_name::select( $review_sel, $review_mod );
      if( 0 < count( $review_list ) ) $neighbours['prev_exam_review_id'] = current( $review_list )['id'];
    }

    $exam_sel = lib::create( 'database\select' );
    $exam_sel->add_column( 'id' );
    $exam_mod = clone $base_exam_mod;
    $exam_mod->where( 'CONCAT( scan_type.name, scan_type.side )', '>', $current_scan_type );
    $exam_mod->order( 'CONCAT( scan_type.name, scan_type.side )' );
    $exam_list = $exam_class_name::select( $exam_sel, $exam_mod );
    if( 0 < count( $exam_list ) )
    {
      $next_exam_id = current( $exam_list )['id'];

      // now use the next exam ID to get the next review
      $review_sel = lib::create( 'database\select' );
      $review_sel->add_table_column( $review_table, 'id' );
      $review_mod = clone $base_review_mod;
      $review_mod->where( 'exam.id', '=', $next_exam_id );

      $review_list = $exam_class_name::select( $review_sel, $review_mod );
      if( 0 < count( $review_list ) ) $neighbours['next_exam_review_id'] = current( $review_list )['id'];
    }

    return $neighbours;
  }
}
