<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\image\code;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    // if no user_id argument is provided then do nothing different
    $user_id = $this->get_argument( "user_id", NULL );
    if( is_null( $user_id ) ) return parent::get_record_count();

    // return a list of how many code groups this image has
    $code_class_name = lib::create( 'database\code' );

    return $this->get_parent_record()->get_exam()->get_scan_type()->get_code_group_count();
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    // if no user_id argument is provided then do nothing different
    $user_id = $this->get_argument( "user_id", NULL );
    if( is_null( $user_id ) ) return parent::get_record_list();

    // return an associative array of all codes for this image
    return $this->get_parent_record()->get_codes( lib::create( 'business\session' )->get_user() );
  }
}
