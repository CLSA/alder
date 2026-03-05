<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\self;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\self\patch
{
  /**
   * Extend parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'apex_user';

    parent::prepare();
  }

  /**
   * Extend parent method
   */
  protected function execute()
  {
    $apex_user = $this->get_argument( 'apex_user', NULL );

    if( is_null( $apex_user ) )
    {
      parent::execute();
    }
    else
    {
      lib::create( 'business\session' )->get_user()->set_apex_user( $apex_user );
    }
  }
}
