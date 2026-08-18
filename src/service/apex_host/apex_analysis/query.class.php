<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_host\apex_analysis;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Replace parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // the status will be 404, reset it to 200
    $this->status->set_code( 200 );
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    $apex_analysis_class_name = lib::get_class_name( 'database\apex_analysis' );

    // return all apex analysis records that have not been upload
    $modifier = clone $this->modifier;
    $modifier->where( 'apex_host.id', '=', $this->get_parent_record()->id );
    $modifier->where( 'apex_analysis.upload_status', '!=', NULL );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $apex_analysis_class_name::count( $modifier );
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $apex_analysis_class_name = lib::get_class_name( 'database\apex_analysis' );

    // return all apex analysis records that have not been upload
    $modifier = clone $this->modifier;
    $modifier->where( 'apex_host.id', '=', $this->get_parent_record()->id );
    $modifier->where( 'apex_analysis.upload_status', '!=', NULL );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $apex_analysis_class_name::select( $this->select, $modifier );
  }
}
