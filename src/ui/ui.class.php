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
    if( !is_null( $module ) )
    {
      $module->add_child( 'review' );
      $module->add_child( 'apex_review' );
      $module->add_action( 'display', '/{identifier}' );
    }

    $module = $this->get_module( 'scan_type' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'exam' );
      $module->add_choose( 'code_group' );
    }

    $module = $this->get_module( 'modality' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'scan_type' );
      $module->add_choose( 'user' );
    }

    $module = $this->get_module( 'user' );
    if( !is_null( $module ) ) $module->add_choose( 'modality' );

    $module = $this->get_module( 'code_group' );
    if( !is_null( $module ) ) $module->add_child( 'code' );

    $module = $this->get_module( 'code' );
    if( !is_null( $module ) )
    {
      $module->add_choose( 'apex_review' );
      $module->add_choose( 'review' );
    }

    $module = $this->get_module( 'apex_host' );
    if( !is_null( $module ) ) $module->add_choose( 'image' );

    $module = $this->get_module( 'apex_analysis' );
    if( !is_null( $module ) )
    {
      $module->add_action( 'import', '/{identifier}' );
      $module->add_action( 'export', '/{identifier}' );
    }
  }

  /**
   * Extends the parent method
   */
  protected function build_listitem_list()
  {
    $db_role = lib::create( 'business\session' )->get_role();

    parent::build_listitem_list();

    // add application-specific lists to the base list
    $this->add_listitem( 'Apex Hosts', 'apex_host' );
    $this->add_listitem( 'Apex Reviews', 'apex_review' );
    $this->add_listitem( 'Interviews', 'interview' );
    $this->add_listitem( 'Modalities', 'modality' );
    $this->add_listitem( 'Reviews', 'review' );
  }

  /**
   * Extends the parent method
   */

  protected function get_utility_items()
  {
    $db_role = lib::create( 'business\session' )->get_role();
    $list = parent::get_utility_items();
    unset( $list['Participant Export'] );
    unset( $list['Participant Multiedit'] );
    unset( $list['Participant Search'] );
    unset( $list['Tracing'] );
    if( 2 < $db_role->tier )
    {
      $list['Apex Review Multiedit'] = array( 'subject' => 'apex_review', 'action' => 'multiedit' );
      $list['Review Multiedit'] = array( 'subject' => 'review', 'action' => 'multiedit' );
    }
    return $list;
  }
}
