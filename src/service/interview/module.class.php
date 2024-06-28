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
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->join( 'site', 'interview.site_id', 'site.id' );
  }
}
