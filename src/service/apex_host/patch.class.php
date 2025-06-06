<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_host;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    if( !$this->get_argument( 'delete_patients', false ) ) parent::setup();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    if( $this->get_argument( 'delete_patients', false ) )
    {
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->delete_all_patients() );
    }
    else
    {
      parent::execute();
    }
  }
}
