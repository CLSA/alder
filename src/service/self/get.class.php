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
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );
    $resource = parent::create_resource( $index );

    // include whether the user is an apex user
    $resource['user']['apex_user'] = $session->get_user()->get_apex_user();

    return $resource;
  }
}
