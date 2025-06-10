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
    $action = $this->get_argument( 'action', NULL );
    if( !in_array( $action, ['delete_patients', 'reupload_images'] ) ) parent::setup();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $action = $this->get_argument( 'action', NULL );
    if( 'delete_patients' == $action )
    {
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->delete_all_patients() );
    }
    else if( 'reupload_images' == $action )
    {
      $this->get_leaf_record()->reupload_images();
    }
    else
    {
      parent::execute();
    }
  }
}
