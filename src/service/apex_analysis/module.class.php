<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_analysis;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    $apex_analysis_class_name = lib::get_class_name( 'database\apex_analysis' );

    parent::validate();

    if( $this->service->may_continue() )
    {
      $method = $this->get_method();
      $action = $this->get_argument( 'action', false );
      $db_apex_analysis = $this->get_resource();

      if( !is_null( $db_apex_analysis ) )
      {
        $db_apex_review = $db_apex_analysis->get_apex_review();

        // restrict typist access
        $session = lib::create( 'business\session' );
        $db_user = $session->get_user();
        $db_role = $session->get_role();
        if( 'typist' == $db_role->name && $db_apex_review->user_id != $db_user->id )
        {
          $this->get_status()->set_code( 403 );
          return;
        }

        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( $db_restrict_site->id != $db_apex_review->get_exam()->get_interview()->site_id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
      }

      if( $action && $this->service->may_continue() )
      {
        if( 'PATCH' == $method )
        {
          $db_apex_review = $db_apex_analysis->get_apex_review();

          // make sure the review's pass property is set
          if( is_null( $db_apex_analysis->pass ) )
          {
            $this->set_data( 'The pass property must be set before the analysis can be downloaded.' );
            $this->get_status()->set_code( 306 );
          }
          else if( is_null( $db_apex_review->get_apex_host() ) )
          {
            $this->set_data( sprintf(
              'Unable to %s Apex analysis as user %s is not assigned to an apex_host',
              $action,
              $db_apex_review->get_user()->name
            ) );
            $this->get_status()->set_code( 306 );
          }
        }
        else if( 'GET' == $method && is_null( $db_apex_analysis ) && 'upload' == $action )
        {
          // only the utility account can upload scans in batches
          $setting_manager = lib::create( 'business\setting_manager' );
          $utility = $setting_manager->get_setting( 'utility', 'username' );
          if( $utility != lib::create( 'business\session' )->get_user()->name )
          {
            $this->get_status()->set_code( 403 );
          }
          else
          {
            // do not proceed if there are other uploads already in progress
            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'upload_status', '=', 'In progress' );
            if( 0 < $apex_analysis_class_name::count( $modifier ) )
            {
              $this->get_status()->set_code( 409 );
            }
          }
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();
    $db_role = $session->get_role();

    $modifier->join( 'apex_review', 'apex_analysis.apex_review_id', 'apex_review.id' );
    $modifier->join( 'exam', 'apex_review.exam_id', 'exam.id' );
    $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
    $modifier->join( 'interview', 'exam.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
    $modifier->left_join( 'site', 'interview.site_id', 'site.id' );
    $modifier->join( 'user', 'apex_review.user_id', 'user.id' );
    $modifier->left_join( 'apex_host', 'user.id', 'apex_host.user_id' );

    // only show typists their own analyses
    if( 'typist' == $db_role->name ) $modifier->where( 'apex_review.user_id', '=', $db_user->id );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) )
    {
      $modifier->where( 'interview.site_id', '=', $db_restrict_site->id );
    }

    if( $select->has_column( 'scan_type' ) )
    {
      $select->add_column(
        'CONCAT( '.
          'scan_type.name, '.
          'IF( scan_type.side = "none", "", CONCAT( " (", scan_type.side, ")" ) ) '.
        ')',
        'scan_type',
        false
      );
    }

    // when uploading restrict to pending records only, and limit by max batch size
    if( is_null( $this->get_resource() ) && 'upload' == $this->get_argument( 'action', false ) )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $batch_size = $setting_manager->get_setting( 'apex', 'batch_size' );
      $modifier->where( 'upload_status', '=', 'Pending' );
      $modifier->order( 'participant.uid' );
      $modifier->limit( $batch_size );
    }
  }

  /**
   * Extend parent method
   */
  public function post_read( &$row )
  {
    parent::post_read( $row );

    $action = $this->get_argument( 'action', false );
    if( 'upload' == $action )
    {
      $session = lib::create( 'business\session' );

      // upload each analysis record one at a time
      $db_apex_analysis = lib::create( 'database\apex_analysis', $row['id'] );
      $db_apex_host = $db_apex_analysis->get_apex_review()->get_apex_host();

      $db_apex_analysis->upload_status = 'In progress';
      $db_apex_analysis->save();
      $session->get_database()->complete_transaction();

      if( is_null( $db_apex_host ) )
      {
        $db_apex_analysis->upload_status = 'Reviewer is not assigned to an Apex host.';
        $db_apex_analysis->upload_datetime = NULL;
        $db_apex_analysis->save();
      }
      else
      {
        // upload the provided files to the host
        $apex_manager = lib::create( 'business\apex_manager', $db_apex_host );
        $result_list = $apex_manager->upload_files( $db_apex_analysis );

        // now either set the upload status to an error or successful
        $error = false;
        foreach( $result_list as $result )
        {
          if( !is_null( $result['error'] ) )
          {
            $error = true;
            $db_apex_analysis->upload_status = $result['error'];
            $db_apex_analysis->upload_datetime = NULL;
            $db_apex_analysis->save();
            break;
          }
        }

        if( !$error )
        {
          $db_apex_analysis->upload_status = NULL;
          $db_apex_analysis->upload_datetime = util::get_datetime_object();
          $db_apex_analysis->save();
        }
      }

      // update the row
      $row['upload_status'] = $db_apex_analysis->upload_status;
      $row['upload_datetime'] = $db_apex_analysis->upload_datetime;
    }
  }

  /**
   * Extend parent method
   */
  public function post_write( $record )
  {
    parent::post_write( $record );

    $action = $this->get_argument( 'action', false );
    if( $action && 'PATCH' == $this->get_method() )
    {
      // name the analysis record to make the code below more readable
      $db_apex_analysis = $record;

      $apex_manager = lib::create(
        'business\apex_manager',
        $db_apex_analysis->get_apex_review()->get_apex_host()
      );

      if( 'download' == $action )
      {
        // download the provided files to the host
        $result = $apex_manager->download_files( $db_apex_analysis );

        $db_apex_analysis->download_datetime = NULL;
        $db_apex_review = $db_apex_analysis->get_apex_review();
        $db_apex_review->end_datetime = NULL;

        if( true === $result )
        {
          $now = util::get_datetime_object();
          $db_apex_analysis->download_datetime = $now;
          $db_apex_review->end_datetime = $now;
        }

        $db_apex_analysis->save();
        $db_apex_review->save();

        $this->set_data( $result );
      }
      else if( 'upload' == $action )
      {
        // make sure the analysis isn't already being uploaded
        if( 'In progress' == $db_apex_analysis->upload_status )
        {
          $this->set_data( 'In progress' );
        }
        else
        {
          // start by setting the upload status as in progress
          $db_apex_analysis->upload_status = 'In progress';
          $db_apex_analysis->save();

          // upload the provided files to the host
          $result_list = $apex_manager->upload_files( $db_apex_analysis );

          // now either set the upload status to an error or successful
          $error = false;
          foreach( $result_list as $result )
          {
            if( !is_null( $result['error'] ) )
            {
              $error = true;
              $db_apex_analysis->upload_status = $result['error'];
              $db_apex_analysis->upload_datetime = NULL;
              break;
            }
          }

          if( !$error )
          {
            $db_apex_analysis->upload_status = NULL;
            $db_apex_analysis->upload_datetime = util::get_datetime_object();
          }

          $db_apex_analysis->save();
          $this->set_data( $result_list );
        }
      }
    }
  }
}
