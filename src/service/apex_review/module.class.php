<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_review;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \alder\service\base_review_module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'user', 'apex_review.user_id', 'user.id' );
    $modifier->left_join( 'apex_host', 'user.id', 'apex_host.user_id' );

    if( $select->has_column( 'status' ) )
    {
      $modifier->join(
        'apex_review_effective_apex_analysis',
        'apex_review.id',
        'apex_review_effective_apex_analysis.apex_review_id'
      );
      $modifier->join(
        'apex_analysis',
        'apex_review_effective_apex_analysis.apex_analysis_id',
        'effective_apex_analysis.id',
        '',
        'effective_apex_analysis'
      );

      $select->add_column(
        'IF( apex_review.end_datetime is NOT NULL, "Closed", '.
          'IF( effective_apex_analysis.download_datetime IS NOT NULL, "Downloaded", '.
            'IF( effective_apex_analysis.upload_datetime IS NOT NULL, "Uploaded", '.
              'IF( effective_apex_analysis.upload_status IS NOT NULL, "Not Uploaded", "Assigned" ) '.
            ') '.
          ') '.
        ')',
        'status',
        false
      );
    }
  }
}
