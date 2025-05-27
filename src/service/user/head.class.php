<?php
/**
 * head.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\user;
use cenozo\lib, cenozo\log, alder\util;

/**
 * The base class of all head services
 */
class head extends \cenozo\service\head
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $this->columns['apex_user'] = array(
      'data_type' => 'tinyint',
      'default' => lib::create( 'business\session' )->get_user()->get_apex_user() ? '1' : '0',
      'required' => '1'
    );
  }
}
