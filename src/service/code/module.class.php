<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\code;
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

    $modifier->join( 'review', 'code.review_id', 'review.id' );
    $modifier->join( 'code_type', 'code.code_type_id', 'code_type.id' );
    $modifier->join( 'code_group', 'code_type.code_group_id', 'code_group.id' );
  }
}
