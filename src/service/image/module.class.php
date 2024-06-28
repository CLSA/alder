<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\image;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'exam', 'image.exam_id', 'exam.id' );

    if( $select->has_column( 'filename' ) )
    {
      $select->add_column( 'RIGHT(path, LOCATE("/", REVERSE(path))-1)', 'filename', false );
    }
  }
}
