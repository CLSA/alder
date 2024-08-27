<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\review;
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

    // set the parent exam's note if it was provided as part of the patch
    $note = $this->get_argument( 'note', false );
    if( false !== $note )
    {
      $db_exam = $this->get_leaf_record()->get_exam();
      $db_exam->note = $note;
      $db_exam->save();
    }
  }
}
