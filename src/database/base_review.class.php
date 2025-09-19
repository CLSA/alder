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
   * Returns the previous and next interview and exam related to this review
   */
  public function get_neighbouring_reviews()
  {
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $exam_class_name = lib::get_class_name( 'database\exam' );

    $is_typist = 'typist' == lib::create( 'business\session' )->get_role()->name;
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

    // Get the previous and next interview
    // Typists will only see their own available reviews, other roles will see all reviews
    // This is done by getting a list of all interviews, sorting them by uid (for apex reviews) or site and uid
    // (for non-apex reviews), then finding the interview before and after the current interview.
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
    $interview_mod->join( 'exam', 'interview.id', 'exam.interview_id' );
    $interview_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    if( 'review' == $review_table ) $interview_mod->left_join( 'site', 'interview.site_id', 'site.id' );
    $interview_mod->where( 'interview.study_phase_id', '=', $db_current_interview->study_phase_id );
    if( $is_typist ) $this->apply_typist_restriction_to_modifier( $interview_mod );
    if( 'review' == $review_table ) $interview_mod->order( 'site.name' );
    $interview_mod->order( 'participant.uid' );

    $interview_sel = lib::create( 'database\select' );
    $interview_sel->add_column( 'id' );
    $interview_sel->set_distinct( true );

    // find the index of this interview in the list
    $interview_list = $interview_class_name::select( $interview_sel, $interview_mod );

    if( 0 < count( $interview_list ) )
    {
      $current_index = NULL;
      foreach( $interview_list as $index => $interview )
      {
        if( $interview['id'] == $db_current_interview->id )
        {
          $current_index = $index;
          break;
        }
      }

      if( is_null( $current_index ) )
      {
        // if we can't find the current interview then something is wrong
        throw lib::create( 'exception\runtime',
          sprintf(
            'Cannot find previous/next interviews for %s %d',
            $review_table,
            $this->id
          ),
          __METHOD__
        );
      }

      $prev_index = $current_index - 1;
      if( array_key_exists( $prev_index, $interview_list ) )
      {
        // get the first review for the prev interview (sorted by scan type)
        $exam_sel = lib::create( 'database\select' );
        $exam_sel->add_table_column( $review_table, 'id' );
        $exam_mod = lib::create( 'database\modifier' );
        $exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
        $exam_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
        $exam_mod->order( 'CONCAT( scan_type.name, scan_type.side )' );
        $exam_mod->limit( 1 );

        $db_prev_interview = lib::create( 'database\interview', $interview_list[$prev_index]['id'] );
        $row = current( $db_prev_interview->get_exam_list( $exam_sel, $exam_mod ) );
        $neighbours['prev_interview_review_id'] = $row['id'];
      }

      $next_index = $current_index + 1;
      if( array_key_exists( $next_index, $interview_list ) )
      {
        // get the first review for the next interview (sorted by scan type)
        $exam_sel = lib::create( 'database\select' );
        $exam_sel->add_table_column( $review_table, 'id' );
        $exam_mod = lib::create( 'database\modifier' );
        $exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
        $exam_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
        $exam_mod->order( 'CONCAT( scan_type.name, scan_type.side )' );
        $exam_mod->limit( 1 );

        $db_next_interview = lib::create( 'database\interview', $interview_list[$next_index]['id'] );
        $row = current( $db_next_interview->get_exam_list( $exam_sel, $exam_mod ) );
        $neighbours['next_interview_review_id'] = $row['id'];
      }
    }

    // Get the previous and next exam
    $base_exam_mod = lib::create( 'database\modifier' );
    $base_exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $base_exam_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    $base_exam_mod->where( 'exam.interview_id', '=', $db_current_interview->id );
    if( $is_typist ) $this->apply_typist_restriction_to_modifier( $base_exam_mod );
    $base_exam_mod->limit( 1 );

    $base_review_mod = lib::create( 'database\modifier' );
    $base_review_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $base_review_mod->join( $review_table, 'exam.id', sprintf( '%s.exam_id', $review_table ) );
    if( $is_typist ) $this->apply_typist_restriction_to_modifier( $base_review_mod );
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

  /**
   * Used by the get_neighbouring_reviews() method only (
   */
  private function apply_typist_restriction_to_modifier( &$modifier )
  {
    $review_table = static::get_table_name();
    $modifier->where( sprintf( '%s.user_id', $review_table ), '=', $this->user_id );

    // also restrict typists to reviews that have been uploaded
    if( 'apex_review' == $review_table )
    {
      $modifier->join(
        'apex_review_effective_apex_analysis',
        'apex_review.id',
        'apex_review_effective_apex_analysis.apex_review_id'
      );
      $modifier->join(
        'apex_analysis',
        'apex_review_effective_apex_analysis.apex_analysis_id',
        'apex_analysis.id'
      );
      $modifier->where_bracket( true );
      $modifier->where( 'apex_review.end_datetime', '=', NULL );
      $modifier->where( 'apex_analysis.download_datetime', '=', NULL );
      $modifier->where( 'apex_analysis.upload_datetime', '!=', NULL );
      $modifier->where( 'apex_analysis.upload_status', '=', NULL );
      $modifier->where_bracket( false );

      // but always allow the current review, no matter what the status
      $modifier->where_bracket( true, true );
      $modifier->where( 'apex_review.id', '=', $this->id );
      $modifier->where_bracket( false );
    }
  }
}
