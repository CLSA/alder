<?php
/**
 * analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

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
    $code_group_mod->where( 'code_group.apex', '=', false );
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

  /**
   * Returns a list of all selections for this analysis
   */
  public function get_selections()
  {
    $analysis_selection_class_name = lib::get_class_name( 'database\analysis_selection' );

    $selection_list = [];
    $db_scan_type = $this->get_image()->get_exam()->get_scan_type();
    $selection_mod = lib::create( 'database\modifier' );
    $selection_mod->where( 'selection.apex', '=', false );
    $selection_mod->order( 'rank' );
    foreach( $db_scan_type->get_selection_object_list( $selection_mod ) as $db_selection )
    {
      // get the record's option for this selection
      $db_analysis_selection = $analysis_selection_class_name::get_unique_record(
        ['analysis_id', 'selection_id'],
        [$this->id, $db_selection->id]
      );

      $selection = [
        'id' => $db_selection->id,
        'rank' => $db_selection->rank,
        'name' => $db_selection->name,
        'description' => $db_selection->description,
        'option_list' => [],
        'selection_option_id' =>
          is_null( $db_analysis_selection ) ? NULL : $db_analysis_selection->selection_option_id
      ];

      $option_sel = lib::create( 'database\select' );
      $option_sel->add_table_column( 'selection_option', 'id' );
      $option_sel->add_column( 'rank' );
      $option_sel->add_column( 'name' );
      $option_mod = lib::create( 'database\modifier' );
      $option_mod->order( "selection_option.rank" );
      foreach( $db_selection->get_selection_option_list($option_sel, $option_mod) as $option )
      {
        $selection['option_list'][] = [
          'id' => $option['id'],
          'name' => $option['name']
        ];
      }

      $selection_list[] = $selection;
    }

    return $selection_list;
  }
}
