<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\analysis;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'note';
    parent::prepare();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // set the parent image's note if it was provided as part of the patch
    $note = $this->get_argument( 'note', false );
    if( false !== $note )
    {
      $db_image = $this->get_leaf_record()->get_image();
      $db_image->note = $note;
      $db_image->save();
    }
  }
}
