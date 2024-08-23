<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\review;
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
      $db_review = $this->get_resource();

      if( !is_null( $db_review ) )
      {
        // restrict typist access
        $session = lib::create( 'business\session' );
        $db_user = $session->get_user();
        $db_role = $session->get_role();
        if( 'typist' == $db_role->name && $db_review->user_id != $db_user->id )
        {
          $this->get_status()->set_code( 403 );
          return;
        }

        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( $db_restrict_site->id != $db_review->get_exam()->get_interview()->site_id )
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
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();
    $db_role = $session->get_role();

    $modifier->join( 'exam', 'review.exam_id', 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'user', 'review.user_id', 'user.id' );

    // only show typists their own reviews
    if( 'typist' == $db_role->name ) $modifier->where( 'review.user_id', '=', $db_user->id );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) )
    {
      $modifier->where( 'interview.site_id', '=', $db_restrict_site->id );
    }

    if( $select->has_column( 'scan_type' ) )
    {
      $select->add_column(
        'CONCAT( '.
          'scan_type.name, '.
          'IF( '.
            '"none" = scan_type.side, '.
            'NULL, '.
            'CONCAT( " (", scan_type.side, ")" ) '.
          ') '.
        ')',
        'scan_type',
        false
      );
    }
  }
}
