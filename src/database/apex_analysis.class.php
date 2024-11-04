<?php
/**
 * apex_analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * apex_analysis: record
 */
class apex_analysis extends \cenozo\database\record
{
  /**
   * Returns a list of all codes for this apex_analysis
   */
  public function get_codes()
  {
    $code_list = [];
    $db_scan_type = $this->get_image()->get_exam()->get_scan_type();
    $code_group_mod = lib::create( 'database\modifier' );
    $code_group_mod->where( 'code_group.name', '=', 'Apex' );
    $code_group_mod->order( 'rank' );
    foreach( $db_scan_type->get_code_group_object_list( $code_group_mod ) as $db_code_group )
    {
      $group = [
        'rank' => $db_code_group->rank,
        'name' => $db_code_group->name,
        'value' => $db_code_group->value,
        'description' => $db_code_group->description,
        'code_list' => []
      ];

      $code_sel = lib::create( 'database\select' );
      $code_sel->add_table_column( 'code', 'id' );
      $code_sel->add_column( 'rank' );
      $code_sel->add_column( 'name' );
      $code_sel->add_column( 'value' );
      $code_sel->add_column( 'description' );
      $code_sel->add_column( 'analysis_has_code.analysis_id IS NOT NULL', 'selected', false, 'boolean' );
      $code_mod = lib::create( 'database\modifier' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'code.id', '=', 'analysis_has_code.code_id', false );
      $join_mod->where( 'analysis_has_code.analysis_id', '=', $this->id );
      $code_mod->join_modifier( 'analysis_has_code', $join_mod, 'left' );
      $code_mod->order( "code.rank" );
      foreach( $db_code_group->get_code_list($code_sel, $code_mod) as $code )
      {
        $group['code_list'][] = [
          'id' => $code['id'],
          'rank' => $code['rank'],
          'name' => $code['name'],
          'value' => $code['value'],
          'description' => $code['description'],
          'selected' => $code['selected']
        ];
      }

      $code_list[] = $group;
    }

    return $code_list;
  }
}
