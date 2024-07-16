<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\review\code;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    if( $this->get_argument( "full", false ) )
    {
      // cache the full list of codes (selected or not) for this review
      $this->codes = $this->get_parent_record()->get_codes();
    }
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    if( $this->get_argument( "full", false ) )
    {
      // return the total number of codes belonging to the parent image
      $total = 0;
      foreach( $this->codes as $group ) $total += count( $group['code_list'] );
      return $total;
    }

    return parent::get_record_count();
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    if( $this->get_argument( "full", false ) ) return $this->codes;
    return parent::get_record_list();
  }

  /**
   * A cache of all possible codes for this review
   * @var array(array)
   * @access private
   */
  private $codes = NULL;
}
