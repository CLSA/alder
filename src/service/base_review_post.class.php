<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service;
use cenozo\lib, cenozo\log, alder\util;

/**
 * The base class of all post services.
 */
class base_review_post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      $db_role = lib::create( 'business\session' )->get_role();
      $file = $this->get_file_as_array();

      if( array_key_exists( 'uid_list', $file ) || array_key_exists( 'start_date', $file ) )
      {
        // only tier-3 roles can process uid-lists or random exam assignments
        if( 3 > $db_role->tier ) $this->status->set_code( 403 );
      }
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $review_type = $this->get_left_subject();

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $review_class_name = lib::get_class_name( sprintf( 'database\%s', $review_type ) );
    $exam_class_name = lib::get_class_name( 'database\exam' );
    $file = $this->get_file_as_array();

    if( array_key_exists( 'uid_list', $file ) || array_key_exists( 'start_date', $file ) )
    {
      $data = [];
      $study_phase_id = array_key_exists( 'study_phase_id', $file ) ? $file['study_phase_id'] : NULL;
      $modality_id = array_key_exists( 'modality_id', $file ) ? $file['modality_id'] : NULL; // reviews only
      $scan_type_id = array_key_exists( 'scan_type_id', $file ) ? $file['scan_type_id'] : NULL; // apex_reviews only
      $user_id = array_key_exists( 'user_id', $file ) ? $file['user_id'] : NULL;
      $process = array_key_exists( 'process', $file ) && $file['process'];

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'interview', 'participant.id', 'interview.participant_id' );
      $modifier->join( 'exam', 'interview.id', 'exam.interview_id' );
      $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );

      if( !is_null( $study_phase_id ) ) $modifier->where( 'interview.study_phase_id', '=', $study_phase_id );
      if( !is_null( $modality_id ) ) $modifier->where( 'scan_type.modality_id', '=', $modality_id );
      if( !is_null( $scan_type_id ) ) $modifier->where( 'scan_type.scan_type_id', '=', $scan_type_id );

      if( array_key_exists( 'start_date', $file ) )
      {
        $start_date = $file['start_date'];
        $end_date = $file['end_date'];

        // determine how many exams exist per grouping in the given date span
        $participant_sel = lib::create( 'database\select' );
        $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
        if( 'apex_review' == $review_type )
        {
          $participant_sel->add_table_column( 'scan_type', 'id', 'scan_type_id' );
          $participant_sel->add_column(
            'IF( scan_type.side = "none", scan_type.name, CONCAT( scan_type.side, " ", scan_type.name ) )',
            'scan_type',
            false
          );
        }
        else
        {
          $participant_sel->add_table_column( 'modality', 'name', 'modality' );
          $participant_sel->add_table_column( 'site', 'name', 'site' );
          $participant_sel->add_table_column( 'exam', 'interviewer' );
        }

        if( $process )
        {
          $participant_sel->add_column( 'GROUP_CONCAT(DISTINCT interview.id)', 'interview_id_list', false );
        }
        else
        {
          $participant_sel->add_column( 'COUNT(DISTINCT interview.id)', 'total', false );
        }
        $participant_mod = clone $modifier;
        $participant_mod->join( 'site', 'interview.site_id', 'site.id' );
        $participant_mod->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
        $participant_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
        $participant_mod->group( 'study_phase.id' );

        if( 'apex_review' == $review_type )
        {
          $participant_mod->group( 'scan_type.id' );
          $participant_mod->order( 'study_phase.name' );
          $participant_mod->order( 'scan_type.name' );
          $participant_mod->order( 'scan_type.side' );
          $participant_mod->where( 'modality.name', '=', 'dxa' );

          // only include exams without an existing review
          $modifier->left_join( 'apex_review', 'exam.id', 'apex_review.exam_id' );
          $modifier->where( 'apex_review.id', '=', NULL );
        }
        else
        {
          $participant_mod->group( 'modality.id' );
          $participant_mod->group( 'exam.interviewer' );
          $participant_mod->order( 'study_phase.name' );
          $participant_mod->order( 'modality.name' );
          $participant_mod->order( 'site.name' );
          $participant_mod->order( 'exam.interviewer' );
        }

        if( !is_null( $start_date ) ) 
          $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '>=', $start_date );
        if( !is_null( $start_date ) ) 
          $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '<=', $end_date );

        $interview_list = $participant_class_name::select( $participant_sel, $participant_mod );

        if( $process )
        {
          $data = 0;
          $exams_per = $file['exams_per'];

          // get a list of all possible exams
          foreach( $interview_list as $interview )
          {
            // select up to the requested number of interviews
            $interview_id_list = explode( ',', $interview['interview_id_list'] );
            $interview_index_list = range( 0, count( $interview_id_list )-1 );
            // randomize reviews
            if( 'apex_review' != $review_type ) shuffle( $interview_index_list );
            foreach( array_slice( $interview_index_list, 0, $exams_per ) as $interview_index )
            {
              $interview_id = $interview_id_list[$interview_index];

              // get a list of all of exams for the given grouping
              $exam_sel = lib::create( 'database\select' );
              $exam_sel->add_table_column( 'exam', 'id' );
              $exam_mod = lib::create( 'database\modifier' );

              if( 'apex_review' == $review_type )
              {
                $exam_mod->where( 'scan_type_id', '=', $interview['scan_type_id'] );
              }
              else
              {
                $exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
                $exam_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
                $exam_mod->where( 'modality.name', '=', $interview['modality'] );
              }
              $exam_mod->where( 'exam.interview_id', '=', $interview_id );

              foreach( $exam_class_name::select( $exam_sel, $exam_mod ) as $exam )
              {
                if( 'apex_review' != $review_type )
                {
                  // make sure the review doesn't already exist
                  $db_review = $review_class_name::get_unique_record(
                    ['exam_id', 'user_id'],
                    [$exam['id'], $user_id]
                  );

                  if( !is_null( $db_review ) ) continue;
                }

                $db_review = lib::create( sprintf( 'database\%s', $review_type ) );
                $db_review->exam_id = $exam['id'];
                $db_review->user_id = $user_id;
                $db_review->save();
                $data++;
              }
            }
          }
        }
        else
        {
          // break down the number of exams into categories
          foreach( $interview_list as $interview )
          {
            if( 'apex_review' == $review_type )
            {
              $phase = $interview['study_phase'];
              $scan_type = $interview['scan_type'];

              if( !array_key_exists( $phase, $data ) ) $data[$phase] = [];
              if( !array_key_exists( $scan_type, $data[$phase] ) ) $data[$phase][$scan_type] = [];
              $data[$phase][$scan_type] = $interview['total'];
            }
            else
            {
              $phase = $interview['study_phase'];
              $modality = $interview['modality'];
              $site = $interview['site'];
              $interviewer = $interview['interviewer'];

              if( !array_key_exists( $phase, $data ) ) $data[$phase] = [];
              if( !array_key_exists( $modality, $data[$phase] ) ) $data[$phase][$modality] = [];
              if( !array_key_exists( $site, $data[$phase][$modality] ) ) $data[$phase][$modality][$site] = [];
              $data[$phase][$modality][$site][$interviewer] = $interview['total'];
            }
          }
        }
      }
      else if( array_key_exists( 'uid_list', $file ) )
      {
        $uid_list = $participant_class_name::get_valid_uid_list( $file['uid_list'], $modifier );
        $completed = array_key_exists( 'completed', $file ) ? $file['completed'] : NULL;
        $notification = array_key_exists( 'notification', $file ) ? $file['notification'] : NULL; // reviews only

        if( $process )
        {
          $data = ['new' => 0, 'edit' => 0];

          // modify existing reviews (do this first so the new reviews created below are not affected)
          if( !is_null( $completed ) || !is_null( $notification ) )
          {
            $now = lib::get_datetime_object();
            $review_mod = lib::create( 'database\modifier' );
            $review_mod->join( 'exam', sprintf( '%s.exam_id', $review_type ), 'exam.id' );
            $review_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $review_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $review_mod->where( 'uid', 'IN', $uid_list );
            foreach( $review_class_name::select_objects( $review_mod ) as $db_review )
            {
              if( !is_null( $completed ) ) $db_review->completed = $now;
              if( !is_null( $notification ) ) $db_review->notification = $notification;
              $db_review->save();
              $data['edit']++;
            }
          }

          // create new reviews
          if( !is_null( $user_id ) )
          {
            $exam_sel = lib::create( 'database\select' );
            $exam_sel->from( 'exam' );
            $exam_sel->add_column( 'id' );
            $exam_mod = lib::create( 'database\modifier' );
            if( 'apex_review' != $review_type )
            {
              $exam_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
              $exam_mod->join( 'user_has_modality', 'scan_type.modality_id', 'user_has_modality.modality_id' );
              $exam_mod->where( 'user_has_modality.user_id', '=', $user_id );
            }
            $exam_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $exam_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $exam_mod->where( 'uid', 'IN', $uid_list );
            if( !is_null( $study_phase_id ) ) $exam_mod->where( 'interview.study_phase_id', '=', $study_phase_id );
            if( !is_null( $modality_id ) ) $exam_mod->where( 'scan_type.modality_id', '=', $modality_id );
            if( !is_null( $scan_type_id ) ) $exam_mod->where( 'scan_type_id', '=', $scan_type_id );
            foreach( $exam_class_name::select( $exam_sel, $exam_mod ) as $exam )
            {
              $db_review = $review_class_name::get_unique_record(
                ['exam_id', 'user_id'],
                [$exam['id'], $user_id]
              );
              if( is_null( $db_review ) )
              {
                $db_review = lib::create( sprintf( 'database\%s', $review_type ) );
                $db_review->exam_id = $exam['id'];
                $db_review->user_id = $user_id;
                $db_review->save();
                $data['new']++;
              }
            }
          }
        }
        else
        {
          // determine how many reviews (existing and missing) of each grouping
          $participant_sel = lib::create( 'database\select' );
          $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
          if( 'apex_review' == $review_type )
          {
            $participant_sel->add_table_column( 'scan_type', 'id', 'scan_type_id' );
            $participant_sel->add_column(
              'IF( scan_type.side = "none", scan_type.name, CONCAT( scan_type.side, " ", scan_type.name ) )',
              'scan_type',
              false
            );
          }
          else
          {
            $participant_sel->add_table_column( 'modality', 'name', 'modality' );
          }
          $participant_sel->add_column( sprintf( '%s.id IS NOT NULL', $review_type ), 'has_review', false );
          $participant_sel->add_column( 'COUNT(*)', 'total', false );

          $participant_mod = clone $modifier;
          $participant_mod->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
          $participant_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
          $participant_mod->left_join( $review_type, 'exam.id', sprintf( '%s.exam_id', $review_type ) );
          $participant_mod->group( 'study_phase.id' );
          if( 'apex_review' == $review_type )
          {
            $participant_mod->group( 'scan_type.id' );
            $participant_mod->group( 'apex_review.id IS NULL' );
            $participant_mod->order( 'study_phase.name' );
            $participant_mod->order( 'scan_type.name' );
            $participant_mod->order( 'scan_type.side' );
            $participant_mod->order( 'apex_review.id IS NOT NULL' );
            $participant_mod->where( 'modality.name', '=', 'dxa' );
          }
          else
          {
            $participant_mod->group( 'modality.id' );
            $participant_mod->group( 'review.id IS NULL' );
            $participant_mod->order( 'study_phase.name' );
            $participant_mod->order( 'modality.name' );
            $participant_mod->order( 'review.id IS NOT NULL' );
          }
          $participant_mod->where( 'uid', 'IN', $uid_list );

          $data = [
            'uid_list' => $uid_list,
            'exam_list' => []
          ];

          // break down the number of exams for each grouping
          foreach( $participant_class_name::select( $participant_sel, $participant_mod ) as $row )
          {
            $phase = $row['study_phase'];
            $group = $row['apex_review' == $review_type ? 'scan_type' : 'modality'];

            if( !array_key_exists( $phase, $data['exam_list'] ) ) $data['exam_list'][$phase] = [];
            if( !array_key_exists( $group, $data['exam_list'][$phase] ) )
              $data['exam_list'][$phase][$group] = ['with' => 0, 'without' => 0];
            $data['exam_list'][$phase][$group][$row['has_review'] ? 'with' : 'without'] = $row['total'];
          }
        }
      }

      $this->set_data( $data );
    }
    else parent::execute();
  }
}
