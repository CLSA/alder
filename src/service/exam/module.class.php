<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\exam;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    if( $this->service->may_continue() )
    {
      $db_exam = $this->get_resource();

      if( !is_null( $db_exam ) )
      {
        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( $db_restrict_site->id != $db_exam->get_interview()->site_id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    $review_type = lib::create( 'business\session' )->get_user()->get_apex_user() ? 'apex_review' : 'review';
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->left_join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) ) $modifier->where( 'interview.site_id', '=', $db_restrict_site->id );

    // when listing exams exclude non DXA scans from apex users
    if( is_null( $this->get_resource() ) && 'apex_review' == $review_type )
    {
      $modifier->where( 'modality.name', '=', 'dxa' );
    }

    // add the list of user reviews via the review table
    $this->add_list_column( 'user_list', 'user', 'name', $select, $modifier, $review_type );

    if( $select->has_column( 'scan_type' ) )
    {
      $select->add_column(
        'CONCAT( '.
          'scan_type.name, '.
          'IF( scan_type.side = "none", "", CONCAT( " (", scan_type.side, ")" ) ) '.
        ')',
        'scan_type',
        false
      );
    }

    if( $select->has_column( 'review_status' ) )
    {
      $modifier->join(
        'exam_effective_apex_review',
        'exam.id',
        'exam_effective_apex_review.exam_id'
      );
      $modifier->left_join(
        'apex_review',
        'exam_effective_apex_review.apex_review_id',
        'effective_apex_review.id',
        'effective_apex_review'
      );
      $modifier->left_join(
        'apex_review_effective_apex_analysis',
        'effective_apex_review.id',
        'apex_review_effective_apex_analysis.apex_review_id'
      );
      $modifier->left_join(
        'apex_analysis',
        'apex_review_effective_apex_analysis.apex_analysis_id',
        'effective_apex_analysis.id',
        'effective_apex_analysis'
      );

      $select->add_column(
        'IF( '.
          'effective_apex_review.end_datetime is NOT NULL, "Closed", '.
          'IF( '.
            'effective_apex_analysis.download_datetime IS NOT NULL, "Downloaded", '.
            'IF( effective_apex_analysis.upload_datetime IS NOT NULL, "Uploaded", '.
              'IF( effective_apex_review.id IS NOT NULL, "Assigned", "Unassigned" ) '.
            ') '.
          ') '.
        ')',
        'review_status',
        false
      );
    }
  }
}
