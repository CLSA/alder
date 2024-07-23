<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\user;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\user\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $this->get_argument( 'choosing', false ) )
    {
      // make sure only typists appear in this list
      $modifier->join( 'access', 'user.id', 'access.user_id' );
      $modifier->join( 'role', 'access.role_id', 'role.id' );
      $modifier->where( 'role.name', '=', 'typist' );
    }
  }
}
