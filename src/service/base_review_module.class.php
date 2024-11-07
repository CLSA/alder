<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class base_review_module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    if( $this->service->may_continue() )
    {
      $db_review = $this->get_resource(); // may be a review or apex_review object

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
    $review_type = $this->get_subject();

    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();
    $db_role = $session->get_role();

    $modifier->join( 'exam', sprintf( '%s.exam_id', $review_type ), 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'user', sprintf( '%s.user_id', $review_type ), 'user.id' );

    // only show typists their own reviews
    if( 'typist' == $db_role->name )
    {
      $modifier->where( sprintf( '%s.user_id', $review_type ), '=', $db_user->id );
    }

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
          'IF( scan_type.side = "none", "", CONCAT( " (", scan_type.side, ")" ) ) '.
        ')',
        'scan_type',
        false
      );
    }

    if( !is_null( $this->get_resource() ) )
    {
      // include the user's first/last/user name
      $select->add_column(
        'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        'formatted_user_id',
        false
      );
    }
  }
}
