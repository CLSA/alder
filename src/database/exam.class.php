<?php
/**
 * exam.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

/**
 * exam: record
 */
class exam extends \cenozo\database\record
{
  /**
   * Get the exam's effective apex_review record
   * @return database\apex_review
   */
  public function get_effective_apex_review()
  {
    $select = lib::create( 'database\select' );
    $select->from( 'exam_effective_apex_review' );
    $select->add_column( 'apex_review_id' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'exam_id', '=', $this->id );

    $apex_review_id = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return is_null( $apex_review_id ) ? NULL : lib::create( 'database\apex_review', $apex_review_id );
  }
}
