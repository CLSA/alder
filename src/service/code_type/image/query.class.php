<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\code_type\image;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // the status will be 404, reset it to 200
    $this->status->set_code( 200 );
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    $image_class_name = lib::create( 'database\image' );

    $db_code_type = $this->get_parent_record();
    $modifier = clone $this->modifier;
    $modifier->join( 'code', 'image.id', 'code.image_id' );
    $modifier->where( 'code.code_type_id', '=', $db_code_type->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $image_class_name::count( $modifier, true ); // distinct
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $image_class_name = lib::create( 'database\image' );

    $db_code_type = $this->get_parent_record();
    $select = clone $this->select;
    $select->set_distinct( true );
    $modifier = clone $this->modifier;
    $modifier->join( 'code', 'image.id', 'code.image_id' );
    $modifier->where( 'code.code_type_id', '=', $db_code_type->id );
    $this->select->apply_aliases_to_modifier( $modifier );

    return $image_class_name::select( $select, $modifier );
  }
}
