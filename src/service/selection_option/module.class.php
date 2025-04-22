<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\selection_option;
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

    $modifier->join( 'selection', 'selection_option.selection_id', 'selection.id' );
    $modifier->join( 'scan_type', 'selection.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
  }
}
