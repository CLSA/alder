<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\analysis_selection;
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

    $modifier->join( 'analysis', 'analysis_selection.analysis_id', 'analysis.id' );
    $modifier->join( 'selection', 'analysis_selection.selection_id', 'selection.id' );
    $modifier->join( 'scan_type', 'selection.scan_type_id', 'scan_type.id' );
    $modifier->join( 'modality', 'scan_type.modality_id', 'modality.id' );
    $modifier->join( 'selection_option', 'analysis_selection.selection_option_id', 'selection_option.id' );
  }
}
