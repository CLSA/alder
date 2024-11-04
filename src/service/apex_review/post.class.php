<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_review;
use cenozo\lib, cenozo\log, alder\util;

/**
 * The base class of all post services.
 */
class post extends \cenozo\service\post
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
        // only tier-3 roles can process uid-lists or bulk exam assignments
        if( 3 > $db_role->tier ) $this->status->set_code( 403 );
      }
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $apex_review_class_name = lib::get_class_name( 'database\apex_review' );
    $exam_class_name = lib::get_class_name( 'database\exam' );
    $file = $this->get_file_as_array();

    if( array_key_exists( 'uid_list', $file ) || array_key_exists( 'start_date', $file ) )
    {
      $data = [];
      $study_phase_id = array_key_exists( 'study_phase_id', $file ) ? $file['study_phase_id'] : NULL;
      $scan_type_id = array_key_exists( 'scan_type_id', $file ) ? $file['scan_type_id'] : NULL;
      $user_id = array_key_exists( 'user_id', $file ) ? $file['user_id'] : NULL;
      $process = array_key_exists( 'process', $file ) && $file['process'];

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'interview', 'participant.id', 'interview.participant_id' );
      $modifier->join( 'exam', 'interview.id', 'exam.interview_id' );
      $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
      
      if( !is_null( $study_phase_id ) ) $modifier->where( 'interview.study_phase_id', '=', $study_phase_id );
      if( !is_null( $scan_type_id ) ) $modifier->where( 'exam.scan_type_id', '=', $scan_type_id );

      if( array_key_exists( 'start_date', $file ) )
      {
        $start_date = $file['start_date'];
        $end_date = $file['end_date'];

        // determine how many exams exist per phase/scan_type in the given date span
        $participant_sel = lib::create( 'database\select' );
        $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
        $participant_sel->add_table_column( 'scan_type', 'id', 'scan_type_id' );
        $participant_sel->add_column(
          'IF( scan_type.side = "none", scan_type.name, CONCAT( scan_type.side, " ", scan_type.name ) )',
          'scan_type',
          false
        );
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
        $participant_mod->group( 'scan_type.id' );
        $participant_mod->order( 'study_phase.name' );
        $participant_mod->order( 'scan_type.name' );
        $participant_mod->order( 'scan_type.side' );
        if( !is_null( $start_date ) )
          $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '>=', $start_date );
        if( !is_null( $start_date ) )
          $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '<=', $end_date );
        $participant_mod->where( 'modality.name', '=', 'dxa' );

        // only include exams without an existing review
        $modifier->left_join( 'apex_review', 'exam.id', 'apex_review.exam_id' );
        $modifier->where( 'apex_review.id', '=', NULL );

        $interview_list = $participant_class_name::select( $participant_sel, $participant_mod );

        if( $process )
        {
          $data = 0;

          // get a list of all possible exams
          foreach( $interview_list as $interview )
          {
            // select up to the requested number of interviews
            $interview_id_list = explode( ',', $interview['interview_id_list'] );
            $interview_index_list = range( 0, count( $interview_id_list )-1 );
            foreach( array_slice( $interview_index_list, 0, $exams_per_category ) as $interview_index )
            {
              $interview_id = $interview_id_list[$interview_index];

              // get a list of all of exams for the given scan_type
              $exam_sel = lib::create( 'database\select' );
              $exam_sel->add_table_column( 'exam', 'id' );
              $exam_mod = lib::create( 'database\modifier' );
              $exam_mod->where( 'scan_type_id', '=', $interview['scan_type_id'] );
              $exam_mod->where( 'exam.interview_id', '=', $interview_id );

              foreach( $exam_class_name::select( $exam_sel, $exam_mod ) as $exam )
              {
                $db_apex_review = lib::create( 'database\apex_review' );
                $db_apex_review->exam_id = $exam['id'];
                $db_apex_review->user_id = $user_id;
                $db_apex_review->save();
                $data++;
              }
            }
          }
        }
        else
        {
          // break down the number of exams for each phase and scan_type
          foreach( $interview_list as $interview )
          {
            $phase = $interview['study_phase'];
            $scan_type = $interview['scan_type'];

            if( !array_key_exists( $phase, $data ) ) $data[$phase] = [];
            if( !array_key_exists( $scan_type, $data[$phase] ) ) $data[$phase][$scan_type] = [];
            $data[$phase][$scan_type] = $interview['total'];
          }
        }
      }
      else if( array_key_exists( 'uid_list', $file ) )
      {
        $uid_list = $participant_class_name::get_valid_uid_list( $file['uid_list'], $modifier );
        $completed = array_key_exists( 'completed', $file ) ? $file['completed'] : NULL;

        if( $process )
        {
          $data = ['new' => 0, 'edit' => 0];

          // modify existing reviews (do this first so the new reviews created below are not affected)
          if( !is_null( $completed ) )
          {
            $now = lib::get_datetime_object();
            $apex_review_mod = lib::create( 'database\modifier' );
            $apex_review_mod->join( 'exam', 'apex_review.exam_id', 'exam.id' );
            $apex_review_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $apex_review_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $apex_review_mod->where( 'uid', 'IN', $uid_list );
            foreach( $apex_review_class_name::select_objects( $apex_review_mod ) as $db_apex_review )
            {
              if( !is_null( $completed ) ) $db_apex_review->completed = $now;
              $db_apex_review->save();
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
            $exam_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $exam_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $exam_mod->where( 'uid', 'IN', $uid_list );
            if( !is_null( $study_phase_id ) ) $exam_mod->where( 'interview.study_phase_id', '=', $study_phase_id );
            if( !is_null( $scan_type_id ) ) $exam_mod->where( 'scan_type_id', '=', $scan_type_id );
            foreach( $exam_class_name::select( $exam_sel, $exam_mod ) as $exam )
            {
              $db_apex_review = $apex_review_class_name::get_unique_record(
                ['exam_id', 'user_id'],
                [$exam['id'], $user_id]
              );
              if( is_null( $db_apex_review ) )
              {
                $db_apex_review = lib::create( 'database\apex_review' );
                $db_apex_review->exam_id = $exam['id'];
                $db_apex_review->user_id = $user_id;
                $db_apex_review->save();
                $data['new']++;
              }
            }
          }
        }
        else
        {
          // determine how many reviews (existing and missing) of each study phase and scan_type exist
          $participant_sel = lib::create( 'database\select' );
          $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
          $participant_sel->add_table_column( 'scan_type', 'id', 'scan_type_id' );
          $participant_sel->add_column(
            'IF( scan_type.side = "none", scan_type.name, CONCAT( scan_type.side, " ", scan_type.name ) )',
            'scan_type',
            false
          );
          $participant_sel->add_column( 'apex_review.id IS NOT NULL', 'has_apex_review', false );
          $participant_sel->add_column( 'COUNT(*)', 'total', false );
          $participant_mod = clone $modifier;
          $participant_mod->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
          $participant_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
          $participant_mod->left_join( 'apex_review', 'exam.id', 'apex_review.exam_id' );
          $participant_mod->group( 'study_phase.id' );
          $participant_mod->group( 'scan_type.id' );
          $participant_mod->group( 'apex_review.id IS NULL' );
          $participant_mod->order( 'study_phase.name' );
          $participant_mod->order( 'scan_type.name' );
          $participant_mod->order( 'scan_type.side' );
          $participant_mod->order( 'apex_review.id IS NOT NULL' );
          $participant_mod->where( 'modality.name', '=', 'dxa' );
          $participant_mod->where( 'uid', 'IN', $uid_list );

          $data = [
            'uid_list' => $uid_list,
            'exam_list' => []
          ];

          // break down the number of exams for each phase, scan_type and whether it has a review
          foreach( $participant_class_name::select( $participant_sel, $participant_mod ) as $row )
          {
            $phase = $row['study_phase'];
            $scan_type = $row['scan_type'];

            if( !array_key_exists( $phase, $data['exam_list'] ) ) $data['exam_list'][$phase] = [];
            if( !array_key_exists( $scan_type, $data['exam_list'][$phase] ) )
              $data['exam_list'][$phase][$scan_type] = ['with' => 0, 'without' => 0];
            $data['exam_list'][$phase][$scan_type][$row['has_review'] ? 'with' : 'without'] = $row['total'];
          }
        }
      }

      $this->set_data( $data );
    }
    else parent::execute();
  }
}
