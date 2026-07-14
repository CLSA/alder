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
      $user_class_name = lib::get_class_name( 'database\user' );

      $temp_sel = lib::create( 'database\select' );
      $temp_sel->add_column( 'user_id' );
      $temp_sel->set_distinct( true );
      $temp_sel->from( $apex_user ? 'apex_review' : 'review' );

      $user_class_name::db()->execute( sprintf(
        'CREATE TEMPORARY TABLE has_reviews %s',
        $temp_sel->get_sql()
      ) );
      $user_class_name::db()->execute( 'ALTER TABLE has_reviews ADD INDEX dk_user_id (user_id)' );
      $modifier->left_join( 'has_reviews', 'user.id', 'has_reviews.user_id' );

      $modifier->where_bracket( true );
      $modifier->where( 'apex_user.id', $apex_user ? '!=' : '=', NULL );
      $modifier->or_where( 'has_reviews.user_id', '!=', NULL );
      $modifier->where_bracket( false );
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
