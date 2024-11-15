<?php
/**
 * apex_analysis.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

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
    $code_group_mod->where( 'code_group.apex', '=', true );
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
      $code_sel->add_column( 'apex_analysis_has_code.apex_analysis_id IS NOT NULL', 'selected', false, 'boolean' );
      $code_mod = lib::create( 'database\modifier' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'code.id', '=', 'apex_analysis_has_code.code_id', false );
      $join_mod->where( 'apex_analysis_has_code.apex_analysis_id', '=', $this->id );
      $code_mod->join_modifier( 'apex_analysis_has_code', $join_mod, 'left' );
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
   * Returns a list of all images that may be used for this analysis
   *
   * @return associative array
   */
  public function get_images_for_apex()
  {
    $db_exam = $this->get_apex_review()->get_exam();
    $db_scan_type = $db_exam->get_scan_type();
    $uid = $db_exam->get_interview()->get_participant()->uid;
    $paired = in_array( $db_scan_type->name, ['forearm', 'hip', 'spine'] );

    $matches = [];
    preg_match( '/[0-9]+/', $this->get_image()->filename, $matches );
    $analysis_number = 1 == count( $matches ) ? $matches[0] : NULL;
    $analysis_phase_rank = $db_exam->get_interview()->get_study_phase()->rank;

    $sub_path = sprintf(
      '%s/dxa/%s/dxa_%s%s{,_[0-9]}',
      // wbody and lateral are not paired, so we only need to look in the current phase
      $paired ? '*' : $analysis_phase_rank,
      $uid,
      $db_scan_type->name,
      'none' == $db_scan_type->side ? '' : sprintf( '_%s', $db_scan_type->side )
    );

    $images = [];

    $original_glob = sprintf(
      '%s/%s.dcm',
      IMAGES_PATH,
      $sub_path
    );
    foreach( glob( $original_glob, GLOB_BRACE ) as $filename )
    {
      // remove the base path
      $filename = str_replace( IMAGES_PATH, '', $filename );
      $data = util::parse_dxa_filename( $filename );
      $analysis_image = $analysis_phase_rank == $data['phase']['rank'] && $analysis_number === $data['number'];

      $data['filename'] = $filename;
      $data['analysis_image'] = $analysis_image;

      if(
        // always include the analysis image
        $analysis_image ||
        (
          // We do not allow multiple images from the same phase, so:
          // only include other images when doing paired analysis...
          $paired &&
          // and this isn't a numbered image in the same phase as the numbered analysis image
          !(
            $analysis_phase_rank == $data['phase']['rank'] &&
            !is_null( $analysis_number ) &&
            !is_null( $data['number'] )
          )
        )
      ) $images[] = $data;
    }


    if( $paired )
    {
      $reanalysed_glob = sprintf( '%s/%s.reanalysed.dcm', SUPPLEMENTARY_PATH, $sub_path );
      foreach( glob( $reanalysed_glob, GLOB_BRACE ) as $filename )
      {
        // remove the base path
        $filename = str_replace( SUPPLEMENTARY_PATH, '', $filename );
        $data = util::parse_dxa_filename( $filename );
        $data['filename'] = $filename;
        $data['analysis_image'] = false;
        $images[] = $data;
      }
    }

    // sort files by study phase, then type, side, number and reanalysed
    usort(
      $images,
      function($a, $b) {
        return strcmp(
          implode(
            ' ',
            [$a['phase']['rank'], $a['type'], $a['side'], $a['number'], $a['reanalysed'] ? '1' : '0']
          ),
          implode(
            ' ',
            [$b['phase']['rank'], $b['type'], $b['side'], $b['number'], $b['reanalysed'] ? '1' : '0']
          )
        );
      }
    );

    return $images;
  }
}
