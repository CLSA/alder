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
    $apex_code_class_name = lib::get_class_name( 'database\apex_code' );

    $apex_code_sel = lib::create( 'database\select' );
    $apex_code_sel->add_table_column( 'apex_code', 'id' );
    $apex_code_sel->add_column( 'rank' );
    $apex_code_sel->add_column( 'name' );
    $apex_code_sel->add_column( 'description' );
    $apex_code_sel->add_column(
      'apex_analysis_has_apex_code.apex_analysis_id IS NOT NULL',
      'selected',
      false,
      'boolean'
    );

    $apex_code_mod = lib::create( 'database\modifier' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'apex_code.id', '=', 'apex_analysis_has_apex_code.apex_code_id', false );
    $join_mod->where( 'apex_analysis_has_apex_code.apex_analysis_id', '=', $this->id );
    $apex_code_mod->join_modifier( 'apex_analysis_has_apex_code', $join_mod, 'left' );
    $apex_code_mod->order( 'apex_code.rank' );

    $group = [
      'rank' => 1,
      'name' => '',
      'value' => NULL,
      'description' => '',
      'code_list' => [],
    ];
    foreach( $apex_code_class_name::select( $apex_code_sel, $apex_code_mod ) as $apex_code )
    {
      $group['code_list'][] = [
        'id' => $apex_code['id'],
        'rank' => $apex_code['rank'],
        'name' => $apex_code['name'],
        'description' => $apex_code['description'],
        'selected' => $apex_code['selected']
      ];
    }

    // emulate code groups by returning the only (default) group as an array
    return [$group];
  }
}
