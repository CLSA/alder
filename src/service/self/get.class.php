<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\self;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Special service for handling the get meta-resource
 */
class get extends \cenozo\service\self\get
{
  /**
   * Override parent method since self is a meta-resource
   */
  protected function create_resource( $index )
  {
    $apex_host_class_name = lib::get_class_name( 'database\apex_host' );

    $setting_manager = lib::create( 'business\setting_manager' );
    $db_user = lib::create( 'business\session' )->get_user();
    $resource = parent::create_resource( $index );

    // include whether the user is an apex user
    $resource['user']['apex_user'] = $db_user->get_apex_user();

    // include the user's apex host ID (if they have one)
    if( $resource['user']['apex_user'] )
    {
      $db_apex_host = $apex_host_class_name::get_unique_record( 'user_id', $db_user->id );
      $resource['user']['apex_host_id'] = is_null( $db_apex_host ) ? NULL : $db_apex_host->id;
    }

    return $resource;
  }
}
