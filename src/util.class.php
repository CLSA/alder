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
      'uid' => null,
      'phase' => null,
      'type' => null,
      'side' => null,
      'number' => null,
      'reanalysed' => null
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
        else if( in_array( $part, ['left', 'right'] ) ) $side = $part;
        else if( preg_match( '/^[0-9]+$/', $part ) ) $number = $part;
      }

      $data = [
        'uid' => $uid,
        'phase' => $phase,
        'type' => $type,
        'side' => $side,
        'number' => $number,
        'reanalysed' => $reanalysed
      ];
    }

    return $data;
  }
}
