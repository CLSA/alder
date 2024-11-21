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

    $apex_analysis_id = $this->get_argument( 'download', NULL );
    $db_download_apex_analysis =
      is_null( $apex_analysis_id ) ? NULL : lib::create( 'database\apex_analysis', $apex_analysis_id );
    if( !is_null( $db_download_apex_analysis ) )
    {
      // download the provided files to the host
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->download_files( $db_download_apex_analysis ) );
    }

    $apex_analysis_id = $this->get_argument( 'upload', NULL );
    $db_upload_apex_analysis =
      is_null( $apex_analysis_id ) ? NULL : lib::create( 'database\apex_analysis', $apex_analysis_id );
    if( !is_null( $db_upload_apex_analysis ) )
    {
      // upload the provided files to the host
      $apex_manager = lib::create( 'business\apex_manager', $this->get_leaf_record() );
      $this->set_data( $apex_manager->upload_files( $db_upload_apex_analysis ) );
    }
  }
}
