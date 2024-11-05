<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_review;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'state';
    $this->extract_parameter_list[] = 'note';
    parent::prepare();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // complete or reopen the review
    $state = $this->get_argument( 'state', false );
    if( false !== $state )
    {   
      $db_apex_review = $this->get_leaf_record();
      if( "reopen" == $state ) $db_apex_review->end_datetime = NULL;
      // only update the end datetime if it isn't set yet
      else if( is_null( $db_apex_review->end_datetime ) ) $db_apex_review->end_datetime = util::get_datetime_object();
      $db_apex_review->save();
    }   

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
