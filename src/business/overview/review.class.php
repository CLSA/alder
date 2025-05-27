<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\business\overview;
use cenozo\lib, cenozo\log, alder\util;

/**
 * overview: withdraw
 */
class review extends \cenozo\business\overview\base_overview
{
  /**
   * Implements abstract method
   */
  protected function build( $modifier = NULL )
  {
    $study_phase_class_name = lib::get_class_name( 'database\study_phase' );
    $review_type = lib::create( 'business\session' )->get_user()->get_apex_user() ? 'apex_review' : 'review';

    $status_column = sprintf(
      'IF( '.
        '%s.id IS NULL, '.
        '"Not reviewed", '.
        'IF( '.
          '%s.end_datetime IS NULL, '.
          '"Review assigned", '.
          '"Review completed" '.
        ') '.
      ')',
      $review_type,
      $review_type
    );

    $select = lib::create( 'database\select' );
    $select->add_table_column( 'study_phase', 'name', 'study_phase' );
    $select->add_table_column( 'scan_type', 'name', 'scan_type' );
    $select->add_column( $status_column, 'status', false );
    $select->add_column( 'COUNT(*)', 'total', false );

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    $modifier->join( 'interview', 'study_phase.id', 'interview.study_phase_id' );
    $modifier->join( 'exam', 'interview.id', 'exam.interview_id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $modifier->left_join( $review_type, 'exam.id', sprintf( '%s.exam_id', $review_type ) );
    if( 'apex_review' == $review_type ) $modifier->where( 'modality.name', '=', 'dxa' );
    $modifier->group( 'study_phase.rank' );
    $modifier->group( 'scan_type.name' );
    $modifier->group( $status_column );

    $study_phase_node = NULL;
    $scan_type_node = NULL;
    foreach( $study_phase_class_name::select( $select, $modifier ) as $row )
    {
      if( is_null( $study_phase_node ) || $study_phase_node->get_label() != $row['study_phase'] )
      {
        $study_phase_node = $this->add_root_item( $row['study_phase'] );
      }

      if( is_null( $scan_type_node ) || $scan_type_node->get_label() != $row['scan_type'] )
      {
        $scan_type_node = $this->add_item( $study_phase_node, $row['scan_type'] );
        $this->add_item( $scan_type_node, 'Not reviewed', 0 );
        $this->add_item( $scan_type_node, 'Review assigned', 0 );
        $this->add_item( $scan_type_node, 'Review completed', 0 );
      }
      
      $node = $scan_type_node->find_node( $row['status'] );
      $node->set_value( $row['total'] );
    }
  }
}
