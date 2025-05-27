<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\user;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \cenozo\service\user\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'apex_user';

    parent::prepare();
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $apex_user = $this->get_argument( 'apex_user', NULL );
    if( NULL !== $apex_user )
    {
      $db_user = $this->get_leaf_record();
      $db_user->set_apex_user( $apex_user );
    }
  }
}
