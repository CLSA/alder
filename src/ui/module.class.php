<?php
/**
 * module.class.php
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\ui;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Defines a module's properties
 */
class module extends \cenozo\ui\module
{
  /**
   * Constructor
   * 
   * @param string $subject The module's subject
   */
  public function __construct( $subject )
  {
    parent::__construct( $subject );

    if( "interview" == $this->subject )
    {
      $this->framework = false;
    }
  }
}
