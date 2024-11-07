<?php
/**
 * setting_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\business;
use cenozo\lib, cenozo\log, alder\util;

/**
 * A manager to provide various data to external sources based on string-based keys
 */
class setting_manager extends \cenozo\business\setting_manager
{
  /**
   * Extend the parent constructor
   */
  protected function __construct( $arguments )
  {
    parent::__construct( $arguments );

    $args = $arguments[0];
    $args = is_array( $arguments[0] ) ? $arguments[0] : array();
    $this->read_settings( 'apex', $args );
  }
}
