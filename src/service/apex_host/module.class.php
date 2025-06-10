<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_host;
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

    $modifier->left_join( 'user', 'apex_host.user_id', 'user.id' );

    if( $select->has_column( 'status' ) )
    {
      $db_apex_host = $this->get_resource();
      if( !is_null( $db_apex_host ) )
      {
        $apex_manager = lib::create( 'business\apex_manager', $db_apex_host );
        $select->add_constant( util::json_encode( $apex_manager->get_status() ), 'status' );
      }
    }

    if( $select->has_column( 'pending_apex_analysis_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'apex_analysis' );
      $join_sel->add_table_column( 'apex_host', 'id', 'apex_host_id' );
      $join_sel->add_column( 'COUNT(*)', 'pending_apex_analysis_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
      $join_mod->join( 'apex_host', 'apex_review.user_id', 'apex_host.user_id' );
      $join_mod->group( 'apex_host.id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS pending_apex_analysis_join', $join_sel->get_sql(), $join_mod->get_sql() ),
        'apex_host.id',
        'pending_apex_analysis_join.apex_host_id' );
      $select->add_column( 'IFNULL( pending_apex_analysis_count, 0 )', 'pending_apex_analysis_count', false );
    }
  }
}
