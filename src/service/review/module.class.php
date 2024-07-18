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
class module extends \cenozo\service\module
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

    $modifier->join( 'image', 'review.image_id', 'image.id' );
    $modifier->join( 'exam', 'image.exam_id', 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'user', 'review.user_id', 'user.id' );
    $this->add_list_column( 'code_list', 'code_type', 'name', $select, $modifier, 'code' );

    if( $select->has_column( 'image_type' ) )
    {
      $select->add_column( 'CONCAT( modality.name, ": ", scan_type.name )', 'image_type', false );
    }
  }
}
