<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_review;
use cenozo\lib, cenozo\log, alder\util;

class patch extends \alder\service\base_review_patch
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // reassign all reviews having the same study, participant and scan type
    if( $this->get_argument( 'reassign_all', false ) )
    {
      $apex_host_class_name = lib::get_class_name( 'database\apex_host' );
      $apex_review_class_name = lib::get_class_name( 'database\apex_review' );

      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_apex_host = $apex_host_class_name::get_unique_record( 'user_id', $db_user->id );
      if( is_null( $db_apex_host ) )
      {
        throw lib::create( 'exception\notice',
          sprintf( 'Cannot proceed, user %s is not assigned to an apex host.', $db_user->name ),
          __METHOD__
        );
      }

      $apex_manager = lib::create( 'business\apex_manager', $db_apex_host );
      $status_list = $apex_manager->get_status();
      foreach( $status_list as $status ) if( false == $status )
        throw lib::create( 'exception\notice', 'Cannot proceed, apex host is not available.', __METHOD__ );

      $db_current_apex_review = $this->get_leaf_record();
      $db_exam = $db_current_apex_review->get_exam();
      $db_interview = $db_exam->get_interview();
      $db_study_phase = $db_interview->get_study_phase();

      $apex_review_mod = lib::create( 'database\modifier' );
      $apex_review_mod->join( 'exam', 'apex_review.exam_id', 'exam.id' );
      $apex_review_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
      $apex_review_mod->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
      $apex_review_mod->where( 'study_phase.study_id', '=', $db_study_phase->study_id );
      $apex_review_mod->where( 'interview.participant_id', '=', $db_interview->participant_id );
      $apex_review_mod->where( 'exam.scan_type_id', '=', $db_exam->scan_type_id );
      $apex_review_mod->order_desc( 'study_phase.rank' );

      $base_study_phase_rank = NULL;
      foreach( $apex_review_class_name::select_objects( $apex_review_mod ) as $db_apex_review )
      {
        $db_apex_review->user_id = $db_current_apex_review->user_id;
        $db_apex_review->end_datetime = null;
        $db_apex_review->save();

        // upload the analysis
        $db_apex_analysis = $db_apex_review->get_effective_apex_analysis();
        $db_apex_analysis->upload_status = 'In progress';
        $db_apex_analysis->save();
        $session->get_database()->complete_transaction();

        // upload the provided files to the host (do not replace and do not upload the paired scan)
        $result_list = $apex_manager->upload_files( $db_apex_analysis, false );

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
    }
  }
}
