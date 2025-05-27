<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

/**
 * user: record
 */
class user extends \cenozo\database\user
{
  /**
   * Returns whether a user has signed up for the apex_user
   */
  public function get_apex_user()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'apex_user', 'user.id', 'apex_user.user_id' );
    $modifier->where( 'user.id', '=', $this->id );
    return 0 < static::count( $modifier );
  }

  /**
   * Sets whether a user should be signed up for the apex_user
   * @param boolean $apex_user
   */
  public function set_apex_user( $apex_user )
  {
    if( $apex_user )
    {
      return static::db()->execute( sprintf(
        'REPLACE INTO apex_user SET user_id = %s',
        static::db()->format_string( $this->id )
      ) );
    }
    else
    {
      return static::db()->execute( sprintf(
        'DELETE FROM apex_user WHERE user_id = %s',
        static::db()->format_string( $this->id )
      ) );
    }
  }
}
