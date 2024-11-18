<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_host;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'delete';
    $this->extract_parameter_list[] = 'download';
    $this->extract_parameter_list[] = 'upload';
    parent::prepare();
  }

  /**
   * Extend parent method
   */
  public function execute()
  {
    parent::execute();

    $delete = $this->get_argument( 'delete', false );
    if( $delete )
    {
      // delete all patients on the host
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->delete_all_patients() );
    }

    $download_files = $this->get_argument( 'download', NULL );
    if( !is_null( $download_files ) )
    {
      // download the provided files to the host
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->download_files( $download_files ) );
    }

    $upload_files = $this->get_argument( 'upload', NULL );
    if( !is_null( $upload_files ) )
    {
      // upload the provided files to the host
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->upload_files( $upload_files ) );
    }
  }

  /**
   * Used to track metadata about the user providing apex_host data
   * @var array;
   * @access protected
   */
  protected $user_metadata = [];
}
