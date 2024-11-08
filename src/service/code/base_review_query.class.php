<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\code;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
abstract class base_review_query extends \cenozo\service\query
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
    $review_type = $this->get_leaf_subject();
    $analysis_type = str_replace( 'review', 'analysis', $review_type );

    $review_class_name = lib::create( sprintf( 'database\%s', $review_type ) );

    $db_code = $this->get_parent_record();
    $modifier = clone $this->modifier;
    $modifier->join(
      $analysis_type,
      sprintf( '%s.id', $review_type ),
      sprintf( '%s.%s_id', $analysis_type, $review_type )
    );
    $modifier->join(
      sprintf( '%s_has_code', $analysis_type ),
      sprintf( '%s.id', $analysis_type ),
      sprintf( '%s_has_code.%s_id', $analysis_type, $analysis_type ),
    );
    $modifier->where( sprintf( '%s_has_code.code_id', $analysis_type ), '=', $db_code->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $review_class_name::count( $modifier, true ); // distinct
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $review_type = $this->get_leaf_subject();
    $analysis_type = str_replace( 'review', 'analysis', $review_type );

    $review_class_name = lib::create( sprintf( 'database\%s', $review_type ) );

    $db_code = $this->get_parent_record();
    $select = clone $this->select;
    $select->set_distinct( true );
    $modifier = clone $this->modifier;
    $modifier->join(
      $analysis_type,
      sprintf( '%s.id', $review_type ),
      sprintf( '%s.%s_id', $analysis_type, $review_type )
    );
    $modifier->join(
      sprintf( '%s_has_code', $analysis_type ),
      sprintf( '%s.id', $analysis_type ),
      sprintf( '%s_has_code.%s_id', $analysis_type, $analysis_type )
    );
    $modifier->where( sprintf( '%s_has_code.code_id', $analysis_type ), '=', $db_code->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $review_class_name::select( $select, $modifier );
  }
}
