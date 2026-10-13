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
class ui3 extends \cenozo\ui\ui3
{
  /**
   * Extends the parent method
   */
  protected function generate_modules()
  {
    parent::generate_modules();

    $session = lib::create( 'business\session' );
    $review_type = $session->get_user()->get_apex_user() ? 'apex_review' : 'review';

    // add child actions to certain modules
    $module = $this->get_module( $review_type );
    if( !is_null( $module ) )
    {
      $module->add_action( 'multiedit' );
    }

    $module = $this->get_module( 'apex_analysis' );
    if( !is_null( $module ) )
    {
      $module->add_action( 'download', '/{identifier}' );
      $module->add_action( 'upload', '/{identifier}' );
    }

    $module = $this->get_module( 'apex_host' );
    if( !is_null( $module ) ) $module->add_child( 'apex_analysis' );

    $module = $this->get_module( 'code_group' );
    if( !is_null( $module ) ) $module->add_child( 'code' );

    $module = $this->get_module( 'code' );
    if( !is_null( $module ) ) $module->add_choose( $review_type );

    $module = $this->get_module( 'exam' );
    if( !is_null( $module ) )
    {
      $module->add_child( $review_type );
      $module->add_action( 'display', '/{identifier}' );
    }

    $module = $this->get_module( 'interview' );
    if( !is_null( $module ) ) $module->add_child( 'exam' );

    $module = $this->get_module( 'modality' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'scan_type' );
      $module->add_choose( 'user' );
    }

    $module = $this->get_module( 'scan_type' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'exam' );
      $module->add_child( 'code_group' );
      $module->add_child( 'selection' );
    }

    $module = $this->get_module( 'selection' );
    if( !is_null( $module ) ) $module->add_child( 'selection_option' );

    $module = $this->get_module( 'user' );
    if( !is_null( $module ) ) $module->add_choose( 'modality' );
  }

  /**
   * Extends the parent method
   */
  protected function generate_menus()
  {
    parent::generate_menus();

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $review_type = $session->get_user()->get_apex_user() ? 'apex_review' : 'review';

    // remove participant lists and utilities
    $this->remove_menu_item( 'utility', 'Participant Multi-Edit' );
    $this->remove_menu_item( 'utility', 'Participant Import' );
    $this->remove_menu_item( 'utility', 'Participant Export' );
    $this->remove_menu_item( 'utility', 'Participant Search' );
    $this->remove_menu_item( 'utility', 'Tracing' );
    $this->remove_menu_item( 'list', 'Languages' );
    $this->remove_menu_item( 'list', 'Sites' );

    $this->add_menu_item( 'list', 'Interviews', 'interview' );
    $this->add_menu_item( 'list', 'Reviews', $review_type );

    if( 'review' == $review_type )
    {
      $this->add_menu_item( 'list', 'Modalities', 'modality' );
    }
    else
    {
      $this->add_menu_item( 'list', 'Apex Hosts', 'apex_host' );
      $this->add_menu_item( 'list', 'Scan Types', 'scan_type' );
    }

    if( 2 < $db_role->tier )
    {
      $this->add_menu_item( 'utility', 'Review Multi-Edit', $review_type, 'multiedit' );
    }
  }
}
