<?php
/**
 * util.class.php
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder;
use cenozo\lib, cenozo\log;

/**
 * util: utility class of static methods
 *
 * Extends cenozo's util class with additional functionality.
 */
class util extends \cenozo\util
{
  /**
   * Given address details this method returns an array with two elements which map to
   * @return associative array
   * @access public
   */
  public static function parse_dxa_filename( $filename )
  {
    $study_class_name = lib::get_class_name( 'database\study' );

    $data = [
      'filename' => $filename,
      'uid' => null,
      'phase' => null,
      'type' => null,
      'side' => null,
      'number' => null,
      'reanalysed' => null,
      'phase_string' => null,
      'short_type_string' => null,
      'patient_id' => null,
      'scan_id' => null,
    ];

    $matches = [];
    if( preg_match( '#([0-9]+)/dxa/([^/]+)/dxa_([^.]+)(\.reanalysed)?\.dcm#', $filename, $matches ) )
    {
      $db_study = $study_class_name::get_unique_record( 'name', 'clsa' );
      $study_phase_sel = lib::create( 'database\select' );
      $study_phase_sel->add_column( 'rank' );
      $study_phase_sel->add_column( 'code' );
      $study_phase_sel->add_column( 'name' );
      $study_phase_mod = lib::create( 'database\modifier' );
      $study_phase_mod->where( 'rank', '=', $matches[1] );

      $phase = $db_study->get_study_phase_list( $study_phase_sel, $study_phase_mod )[0];
      $uid = $matches[2];
      $parts = explode( '_', $matches[3] );
      $reanalysed = 4 < count( $matches );

      $type = NULL;
      $side = NULL;
      $number = NULL;
      foreach( $parts as $index => $part )
      {
        if( 0 == $index ) $type = $part;
        else if( in_array( $part, ['left', 'none', 'right'] ) ) $side = $part;
        else if( preg_match( '/^[0-9]+$/', $part ) ) $number = $part;
      }

      $data['uid'] = $uid;
      $data['phase'] = $phase;
      $data['type'] = $type;
      $data['side'] = null == $side ? 'none' : $side;
      $data['type_side'] = 'none' == $side ? $type : sprintf( '%s_%s', $type, $side );
      $data['number'] = $number;
      $data['reanalysed'] = $reanalysed;

      // the phase string includes the phase and R if the scan is reanalysed
      $data['phase_string'] = sprintf( '%d%s', $data['phase']['rank'], $data['reanalysed'] ? 'R' : '' );

      // the type string includes the type and side (if left or right only)
      $data['type_string'] = (
        'none' == $data['side'] ? $data['type'] : sprintf( '%s (%s)', $data['type'], $data['side'] )
      );

      // the short type string is the first letter of the type and side (excluding side if it's "none")
      $data['short_type_string'] = strtoupper(
        'none' == $data['side'] ? $data['type'][0] : $data['type'][0].$data['side'][0]
      );

      $data['patient_id'] = sprintf( '%s_%s', $data['uid'], $data['short_type_string'] );
      $data['scan_id'] = sprintf( '%s%s%s', $data['uid'], $data['phase_string'], $data['short_type_string'] );
      $data['identifier'] = sprintf( '%s_%s', $data['uid'], $data['short_type_string'] );

      // add the full path to the filename (only if it isn't already included)
      if( $data['reanalysed'] )
      {
        if( 0 === preg_match( sprintf( '#%s#', SUPPLEMENTARY_PATH ), $data['filename'] ) )
          $data['filename'] = sprintf( '%s%s', SUPPLEMENTARY_PATH, $data['filename'] );
      }
      else
      {
        if( 0 === preg_match( sprintf( '#%s#', IMAGES_PATH ), $data['filename'] ) )
          $data['filename'] = sprintf( '%s%s', IMAGES_PATH, $data['filename'] );
      }
    }

    return $data;
  }
}
