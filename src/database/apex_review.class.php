<?php
/**
 * apex_review.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\database;
use cenozo\lib, cenozo\log, alder\util;

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
    $db_exam = $this->get_exam();
    $db_scan_type = $db_exam->get_scan_type();
    $uid = $db_exam->get_interview()->get_participant()->uid;
    $sub_path = sprintf(
      '*/dxa/%s/dxa_%s%s{,_[0-9]}',
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
      $data['filename'] = $filename;
      $images[] = $data;
    }

    $reanalysed_glob = sprintf( '%s/%s.reanalysed.dcm', SUPPLEMENTARY_PATH, $sub_path );
    foreach( glob( $reanalysed_glob, GLOB_BRACE ) as $filename )
    {
      // remove the base path
      $filename = str_replace( SUPPLEMENTARY_PATH, '', $filename );
      $data = util::parse_dxa_filename( $filename );
      $data['filename'] = $filename;
      $images[] = $data;
    }

    // sort files by study phase, then type, side, number and reanalysed
    usort(
      $images,
      function($a, $b) {
        return strcmp(
          implode(
            ' ',
            [
              $a['phase']['rank'],
              $a['type'],
              $a['side'],
              $a['number'],
              $a['reanalysed'] ? '1' : '0'
            ]
          ),
          implode(
            ' ',
            [
              $b['phase']['rank'],
              $b['type'],
              $b['side'],
              $b['number'],
              $b['reanalysed'] ? '1' : '0'
            ]
          )
        );
      }
    );

    return $images;
  }
}
