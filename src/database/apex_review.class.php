<?php
/**
 * apex_review.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

/**
 * apex_review: record
 */
class apex_review extends base_review
{
  /**
   * Returns the apex_host associated to this review (based on the user it is assigned to)
   */
  public function get_apex_host()
  {
    $apex_host_class_name = lib::get_class_name( 'database\apex_host' );
    return $apex_host_class_name::get_unique_record( 'user_id', $this->user_id );
  }

  /**
   * Get the apex_review's effective apex_analysis record
   * @return database\apex_analysis
   */
  public function get_effective_apex_analysis()
  {
    $select = lib::create( 'database\select' );
    $select->from( 'apex_review_effective_apex_analysis' );
    $select->add_column( 'apex_analysis_id' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'apex_review_id', '=', $this->id );

    $apex_analysis_id = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return is_null( $apex_analysis_id ) ? NULL : lib::create( 'database\apex_analysis', $apex_analysis_id );
  }
}
