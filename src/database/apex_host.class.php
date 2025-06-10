<?php
/**
 * apex_host.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

/**
 * apex_host: record
 */
class apex_host extends \cenozo\database\record
{
  /**
   * Marks all apex analysis records belonging to this server that failed to upload back to pending upload
   */
  public function reupload_images()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
    $modifier->join( 'apex_host', 'apex_review.user_id', 'apex_host.user_id' );
    $modifier->where( 'apex_host.id', '=', $this->id );
    $modifier->where( 'apex_analysis.upload_status', 'NOT IN', ['In progress', 'Pending'] );
    static::db()->execute( sprintf(
      'UPDATE apex_analysis %s SET upload_status = "Pending" WHERE %s',
      $modifier->get_join(),
      $modifier->get_where()
    ) );
  }
}
