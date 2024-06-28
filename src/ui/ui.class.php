<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\ui;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Application extension to ui class
 */
class ui extends \cenozo\ui\ui
{
  /**
   * Extends the parent method
   */
  protected function build_module_list()
  {
    parent::build_module_list();

    $db_role = lib::create( 'business\session' )->get_role();

    // add child actions to certain modules
    $module = $this->get_module( 'interview' );
    if( !is_null( $module ) ) $module->add_child( 'exam' );

    $module = $this->get_module( 'exam' );
    if( !is_null( $module ) ) $module->add_child( 'image' );

    $module = $this->get_module( 'scan_type' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'exam' );
      $module->add_choose( 'code_type' );
    }

    $module = $this->get_module( 'modality' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'scan_type' );
      $module->add_choose( 'user' );
    }

    /*
    $module = $this->get_module( 'image' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'code' );
      $module->add_child( 'rating' );
    }
    */

    $module = $this->get_module( 'code_type' );
    if( !is_null( $module ) ) $module->add_child( 'code' );

    $module = $this->get_module( 'code_group' );
    if( !is_null( $module ) ) $module->add_child( 'code_type' );
  }

  /**
   * Extends the parent method
   */
  protected function build_listitem_list()
  {
    parent::build_listitem_list();

    // add application-specific lists to the base list
    $this->add_listitem( 'Interviews', 'interview' );
    $this->add_listitem( 'Modalities', 'modality' );
    $this->add_listitem( 'Code Groups', 'code_group' );
  }
}
