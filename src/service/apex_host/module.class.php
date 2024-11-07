<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_host;
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

    if( $select->has_column( 'status' ) )
    {
      $db_apex_host = $this->get_resource();
      if( !is_null( $db_apex_host ) )
      {
        $apex_manager = lib::create( 'business\apex_manager', $db_apex_host );
        $select->add_constant( util::json_encode( $apex_manager->get_status() ), 'status' );
      }
    }
  }
}
