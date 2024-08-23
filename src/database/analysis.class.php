<?php
/**
 * analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @fileanalysis
 */

namespace alder\database;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * analysis: record
 */
class analysis extends \cenozo\database\record
{
  /**
   * Returns a list of all codes for this analysis
   */
  public function get_codes()
  {
    $code_list = [];
    $db_scan_type = $this->get_image()->get_exam()->get_scan_type();
    $code_group_mod = lib::create( 'database\modifier' );
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

      $code_type_sel = lib::create( 'database\select' );
      $code_type_sel->add_table_column( 'code_type', 'id', 'code_type_id' );
      $code_type_sel->add_column( 'rank' );
      $code_type_sel->add_column( 'name' );
      $code_type_sel->add_column( 'value' );
      $code_type_sel->add_column( 'description' );
      $code_type_sel->add_column( 'code.id IS NOT NULL', 'selected', false, 'boolean' );
      $code_type_mod = lib::create( 'database\modifier' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'code_type.id', '=', 'code.code_type_id', false );
      $join_mod->where( 'code.analysis_id', '=', $this->id );
      $code_type_mod->join_modifier( 'code', $join_mod, 'left' );
      $code_type_mod->order( "code_type.rank" );
      foreach( $db_code_group->get_code_type_list($code_type_sel, $code_type_mod) as $code_type )
      {
        $group['code_list'][] = [
          'code_type_id' => $code_type['code_type_id'],
          'rank' => $code_type['rank'],
          'name' => $code_type['name'],
          'value' => $code_type['value'],
          'description' => $code_type['description'],
          'selected' => $code_type['selected']
        ];
      }

      $code_list[] = $group;
    }

    return $code_list;
  }
}
