<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\analysis\analysis_selection;
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
      // cache the full list of selections (selected or not) for this analysis
      $this->selections = $this->get_parent_record()->get_selections();
    }
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    if( $this->get_argument( "full", false ) )
    {
      // return the total number of selections belonging to the parent image
      $total = 0;
      foreach( $this->selections as $selection ) $total += count( $selection['option_list'] );
      return $total;
    }

    return parent::get_record_count();
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    if( $this->get_argument( "full", false ) ) return $this->selections;
    return parent::get_record_list();
  }

  /**
   * A cache of all possible selections for this analysis
   * @var array(array)
   * @access private
   */
  private $selections = NULL;
}
