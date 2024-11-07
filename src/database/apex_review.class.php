<?php
/**
 * apex_review.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * apex_review: record
 */
class apex_review extends base_review
{
  /**
   * Returns a list of all original and reanalysed images that may be used for reanalysis in Apex
   * 
   * @return associative array
   */
  public function get_images_for_apex()
  {
    $study_class_name = lib::create( 'database\study' );
    $study_phase_class_name = lib::create( 'database\study_phase' );
    $db_study = $study_class_name::get_unique_record( 'name', 'clsa' );
    $db_exam = $this->get_exam();
    $db_scan_type = $db_exam->get_scan_type();
    $uid = $db_exam->get_interview()->get_participant()->uid;

    $study_phase_sel = lib::create( 'database\select' );
    $study_phase_sel->add_column( 'rank' );
    $study_phase_sel->add_column( 'name' );
    $study_phase_mod = lib::create( 'database\modifier' );
    $study_phase_mod->order( 'rank' );
    $study_phase_list = [];
    foreach( $db_study->get_study_phase_list( $study_phase_sel, $study_phase_mod ) as $study_phase )
      $study_phase_list[$study_phase['rank']] = $study_phase['name'];

    $images = [];

    $original_glob = sprintf(
      '%s/*/dxa/%s/dxa_%s%s*',
      IMAGES_PATH,
      $db_exam->get_interview()->get_participant()->uid,
      $db_scan_type->name,
      'none' == $db_scan_type->side ? '' : sprintf( '_%s', $db_scan_type->side )
    );

    $reanalysed_glob = sprintf(
      '%s/*/dxa/%s/dxa_%s%s*.reanalysed.dcm',
      SUPPLEMENTARY_PATH,
      $uid,
      $db_scan_type->name,
      'none' == $db_scan_type->side ? '' : sprintf( '_%s', $db_scan_type->side )
    );
    foreach( array_merge( glob( $original_glob ), glob( $reanalysed_glob ) ) as $filename )
    {
      $matches = [];
      if( preg_match(
        sprintf( '#/([0-9]+)/dxa/%s/dxa_([^.]+)(\.reanalysed)?\.dcm#', $uid ),
        $filename,
        $matches
      ) ) {
        // filenames are in the form of "dxa_type_<left|right>_<number>_<reanalysed>.dcm"
        $phase = $study_phase_list[$matches[1]];
        $parts = explode( '_', $matches[2] );
        $reanalysed = 3 < count( $matches );

        $type = NULL;
        $side = NULL;
        $number = NULL;
        foreach( $parts as $index => $part )
        {
          if( 0 == $index ) $type = $part;
          else if( in_array( $part, ['left', 'right'] ) ) $side = $part;
          else if( preg_match( '/^[0-9]+$/', $part ) ) $number = $part;
        }

        $images[] = [
          'filename' => $matches[0],
          'phase' => $phase,
          'type' => $type,
          'side' => $side,
          'number' => $number,
          'reanalysed' => $reanalysed
        ];
      }
    }

    // sort files by study phase, then type, side, number and reanalysed
    usort(
      $images,
      function($a, $b) {
        return strcmp(
          implode( ' ', [$a['phase'], $a['type'], $a['side'], $a['number'], $a['reanalysed'] ? '1' : '0'] ),
          implode( ' ', [$b['phase'], $b['type'], $b['side'], $b['number'], $b['reanalysed'] ? '1' : '0'] )
        );
      }
    );

    return $images;
  }
}
