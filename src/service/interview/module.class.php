<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\interview;
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
      $db_interview = $this->get_resource();

      if( !is_null( $db_interview ) )
      {
        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( $db_restrict_site->id != $db_interview->site_id )
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

    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->join( 'site', 'interview.site_id', 'site.id' );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) )
    {
      $modifier->where( 'interview.site_id', '=', $db_restrict_site->id );
    }
  }
}
