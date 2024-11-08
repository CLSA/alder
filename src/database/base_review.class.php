<?php
/**
 * base_review.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

abstract class base_review extends \cenozo\database\record
{
  /**
   * Override parent method
   */
  public function save()
  {
    // make sure the start_datetime is set
    if( is_null( $this->start_datetime ) ) $this->start_datetime = util::get_datetime_object();

    parent::save();
  }
}
