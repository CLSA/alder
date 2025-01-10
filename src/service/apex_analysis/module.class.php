<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_analysis;
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
      $db_apex_analysis = $this->get_resource();

      if( !is_null( $db_apex_analysis ) )
      {
        $db_apex_review = $db_apex_analysis->get_apex_review();

        // restrict typist access
        $session = lib::create( 'business\session' );
        $db_user = $session->get_user();
        $db_role = $session->get_role();
        if( 'typist' == $db_role->name && $db_apex_review->user_id != $db_user->id )
        {
          $this->get_status()->set_code( 403 );
          return;
        }

        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( $db_restrict_site->id != $db_apex_review->get_exam()->get_interview()->site_id )
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

    $modifier->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
    $modifier->join( 'exam', 'apex_review.exam_id', 'exam.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->left_join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'user', 'apex_review.user_id', 'user.id' );

    // only show typists their own analyses
    if( 'typist' == $db_role->name ) $modifier->where( 'apex_review.user_id', '=', $db_user->id );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) )
    {
      $modifier->where( 'interview.site_id', '=', $db_restrict_site->id );
    }
  }
}
