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

    $modifier->left_join( 'apex_user', 'user.id', 'apex_user.user_id' );
    $select->add_column( 'apex_user.id IS NOT NULL', 'apex_user', false, 'boolean' );

    // when listing users only show those of the same review type
    if( is_null( $this->get_resource() ) )
    {
      $apex_user = lib::create( 'business\session' )->get_user()->get_apex_user();
      $modifier->where( 'apex_user.id', $apex_user ? '!=' : '=', NULL );
    }

    if( $this->get_argument( 'choosing', false ) )
    {
      // make sure only typists appear in this list
      $modifier->join( 'access', 'user.id', 'access.user_id' );
      $modifier->join( 'role', 'access.role_id', 'role.id' );
      $modifier->where( 'role.name', '=', 'typist' );
    }

  }
}
