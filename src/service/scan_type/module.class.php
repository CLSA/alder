<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\scan_type;
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
    $apex_user = lib::create( 'business\session' )->get_user()->get_apex_user();

    parent::prepare_read( $select, $modifier );

    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $this->add_count_column( 'exam_count', 'exam', $select, $modifier );

    if( $apex_user ) $modifier->where( 'modality.name', '=', 'dxa' );
  }
}
