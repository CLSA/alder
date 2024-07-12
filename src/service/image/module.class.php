<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\image;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent property
   */
  protected static $base64_column_list = ['image' => 'image/jpeg'];

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );
    $db_user = lib::create( 'business\session' )->get_user();

    $modifier->join( 'exam', 'image.exam_id', 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'image.id', '=', 'rating.image_id', false );
    $join_mod->where( 'rating.user_id', '=', $db_user->id );
    $modifier->join_modifier( 'rating', $join_mod, 'left' );

    if( $select->has_column( 'filename' ) )
    {
      $select->add_column( 'RIGHT(path, LOCATE("/", REVERSE(path))-1)', 'filename', false );
    }

    $db_image = $this->get_resource();
    if( !is_null( $db_image ) )
    {
      if( $select->has_column( 'image' ) )
      {
        $select->add_constant( $db_image->get_base64_jpeg(), 'image' );
      }
    }
  }
}
