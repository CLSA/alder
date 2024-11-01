<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_code\apex_review;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
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
    $apex_review_class_name = lib::create( 'database\apex_review' );

    $db_apex_code = $this->get_parent_record();
    $modifier = clone $this->modifier;
    $modifier->join( 'apex_analysis', 'apex_review.id', 'apex_analysis.apex_review_id' );
    $modifier->join( 'apex_analysis_has_apex_code', 'apex_analysis.id', 'apex_analysis_has_apex_code.apex_analysis_id' );
    $modifier->where( 'apex_analysis_has_apex_code.apex_code_id', '=', $db_apex_code->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $apex_review_class_name::count( $modifier, true ); // distinct
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $apex_review_class_name = lib::create( 'database\apex_review' );

    $db_apex_code = $this->get_parent_record();
    $select = clone $this->select;
    $select->set_distinct( true );
    $modifier = clone $this->modifier;
    $modifier->join( 'apex_analysis', 'apex_review.id', 'apex_analysis.apex_review_id' );
    $modifier->join( 'apex_analysis_has_apex_code', 'apex_analysis.id', 'apex_analysis_has_apex_code.apex_analysis_id' );
    $modifier->where( 'apex_analysis_has_apex_code.apex_code_id', '=', $db_apex_code->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $apex_review_class_name::select( $select, $modifier );
  }
}
