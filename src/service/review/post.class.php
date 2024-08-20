<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\review;
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
      $image_class_name = lib::get_class_name( 'database\image' );
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_role = $session->get_role();
      $file = $this->get_file_as_array();

      if( array_key_exists( 'uid_list', $file ) || array_key_exists( 'start_date', $file ) )
      {
        // only tier-3 roles can process uid-lists or random image assignments
        if( 3 > $db_role->tier ) $this->status->set_code( 403 );
      }
      else
      {
        // only typists can create reviews directly
        if( 'typist' != $db_role->name )
        {
          $this->status->set_code( 403 );
        }
        else
        {
          $image_sel = lib::create( 'database\select' );
          $image_sel->set_distinct( true );
          $image_sel->add_column( 'id' );
          $image_mod = lib::create( 'database\modifier' );
          $image_mod->join( 'exam', 'image.exam_id', 'exam.id' );
          $image_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );

          // the image belongs to a modality that the user had access to
          $image_mod->join(
            'user_has_modality',
            'scan_type.modality_id',
            'user_has_modality.modality_id'
          );
          $image_mod->where( 'user_has_modality.user_id', '=', $db_user->id );

          // join to the review table to make sure the image doesn't have an existing review
          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where( 'image.id', '=', 'review.image_id', false );
          $join_mod->where( 'review.user_id', '=', $db_user->id );
          $image_mod->join_modifier( 'review', $join_mod, 'left' );
          $image_mod->where( 'review.id', '=', NULL );

          $image_list = $image_class_name::select( $image_sel, $image_mod );

          if( 0 == count( $image_list ) )
          {
            $this->set_data( 'There are no images available for a review at this time, please try again later.' );
            $this->status->set_code( 408 );
          }
          else
          {
            $image = current( $image_list );
            $this->image_id = $image['id'];
          }
        }
      }
    }
  }

  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();
    $file = $this->get_file_as_array();

    if( !array_key_exists( 'uid_list', $file ) && !array_key_exists( 'start_date', $file ) )
    {
      $db_review = $this->get_leaf_record();

      // set the user and image based on what was determined in the validate method
      $db_review->user_id = $db_user->id;
      $db_review->image_id = $this->image_id;
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $review_class_name = lib::get_class_name( 'database\review' );
    $image_class_name = lib::get_class_name( 'database\image' );
    $file = $this->get_file_as_array();

    if( array_key_exists( 'uid_list', $file ) || array_key_exists( 'start_date', $file ) )
    {
      $data = [];
      $study_phase_id = array_key_exists( 'study_phase_id', $file ) ? $file['study_phase_id'] : NULL;
      $modality_id = array_key_exists( 'modality_id', $file ) ? $file['modality_id'] : NULL;
      $user_id = array_key_exists( 'user_id', $file ) ? $file['user_id'] : NULL;
      $process = array_key_exists( 'process', $file ) && $file['process'];

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'interview', 'participant.id', 'interview.participant_id' );
      $modifier->join( 'exam', 'interview.id', 'exam.interview_id' );
      $modifier->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );

      if( !is_null( $study_phase_id ) ) $modifier->where( 'interview.study_phase_id', '=', $study_phase_id );
      if( !is_null( $modality_id ) ) $modifier->where( 'scan_type.modality_id', '=', $modality_id );

      if( array_key_exists( 'start_date', $file ) )
      {
        $start_date = $file['start_date'];
        $end_date = $file['end_date'];

        // determine how many exams exist per phase/modality/interviewer in the given date span
        $participant_sel = lib::create( 'database\select' );
        $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
        $participant_sel->add_table_column( 'modality', 'name', 'modality' );
        $participant_sel->add_table_column( 'site', 'name', 'site' );
        $participant_sel->add_table_column( 'exam', 'interviewer' );
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
        $participant_mod->group( 'modality.id' );
        $participant_mod->group( 'exam.interviewer' );
        $participant_mod->order( 'study_phase.name' );
        $participant_mod->order( 'modality.name' );
        $participant_mod->order( 'site.name' );
        $participant_mod->order( 'exam.interviewer' );
        $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '>=', $start_date );
        $participant_mod->where( 'DATE(CONVERT_TZ(exam.datetime,"UTC",site.timezone))', '<=', $end_date );
        $interviewer_list = $participant_class_name::select( $participant_sel, $participant_mod );

        if( $process )
        {
          $data = 0;
          $exams_per_interviewer = $file['exams_per_interviewer'];

          // get a list of all possible exams
          foreach( $interviewer_list as $interview )
          {
            // select up to the requested number of interviews
            $interview_id_list = explode( ',', $interview['interview_id_list'] );
            $interview_index_list = range( 0, count( $interview_id_list )-1 );
            shuffle( $interview_index_list );
            foreach( array_slice( $interview_index_list, 0, $exams_per_interviewer ) as $interview_index )
            {
              $interview_id = $interview_id_list[$interview_index];

              // get a list of all of images done by the interviewer for the given modality grouped by scan-type
              $image_sel = lib::create( 'database\select' );
              $image_sel->add_table_column( 'scan_type', 'name' );
              $image_sel->add_column( 'GROUP_CONCAT( image.id )', 'image_id_list', false );
              $image_mod = lib::create( 'database\modifier' );
              $image_mod->join( 'exam', 'image.exam_id', 'exam.id' );
              $image_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
              $image_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
              $image_mod->where( 'modality.name', '=', $interview['modality'] );
              $image_mod->where( 'exam.interview_id', '=', $interview_id );
              $image_mod->group( 'scan_type.id' );

              // pick a random image belonging to each scan-type
              foreach( $image_class_name::select( $image_sel, $image_mod ) as $scan_type )
              {
                $image_id_list = explode( ',', $scan_type['image_id_list'] );
                $image_id = $image_id_list[rand( 0, count($image_id_list)-1 )];
                
                // make sure a review doesn't already exist
                $db_review = $review_class_name::get_unique_record(
                  ['image_id', 'user_id'],
                  [$image_id, $user_id]
                );

                if( is_null( $db_review ) )
                {
                  $db_review = lib::create( 'database\review' );
                  $db_review->image_id = $image_id;
                  $db_review->user_id = $user_id;
                  $db_review->save();
                  $data++;
                }
              }
            }
          }
        }
        else
        {
          // break down the number of images for each phase, modality, site and interviewer
          foreach( $interviewer_list as $interview )
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
      else if( array_key_exists( 'uid_list', $file ) )
      {
        $uid_list = $participant_class_name::get_valid_uid_list( $file['uid_list'], $modifier );
        $completed = array_key_exists( 'completed', $file ) ? $file['completed'] : NULL;
        $notification = array_key_exists( 'notification', $file ) ? $file['notification'] : NULL;

        if( $process )
        {
          $data = ['new' => 0, 'edit' => 0];

          // modify existing reviews (do this first so the new reviews created below are not affected)
          if( !is_null( $completed ) || !is_null( $notification ) )
          {
            $review_mod = lib::create( 'database\modifier' );
            $review_mod->join( 'image', 'review.image_id', 'image.id' );
            $review_mod->join( 'exam', 'image.exam_id', 'exam.id' );
            $review_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $review_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $review_mod->where( 'uid', 'IN', $uid_list );
            foreach( $review_class_name::select_objects( $review_mod ) as $db_review )
            {
              if( !is_null( $completed ) ) $db_review->completed = $completed;
              if( !is_null( $notification ) ) $db_review->notification = $notification;
              $db_review->save();
              $data['edit']++;
            }
          }

          // create new reviews
          if( !is_null( $user_id ) )
          {
            $image_sel = lib::create( 'database\select' );
            $image_sel->from( 'image' );
            $image_sel->add_column( 'id' );
            $image_mod = lib::create( 'database\modifier' );
            $image_mod->join( 'exam', 'image.exam_id', 'exam.id' );
            $image_mod->join( 'scan_type', 'exam.scan_type_id', 'scan_type.id' );
            $image_mod->join( 'user_has_modality', 'scan_type.modality_id', 'user_has_modality.modality_id' );
            $image_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
            $image_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
            $image_mod->where( 'uid', 'IN', $uid_list );
            if( !is_null( $study_phase_id ) ) $image_mod->where( 'interview.study_phase_id', '=', $study_phase_id );
            if( !is_null( $modality_id ) ) $image_mod->where( 'scan_type.modality_id', '=', $modality_id );
            $image_mod->where( 'user_has_modality.user_id', '=', $user_id );
            foreach( $image_class_name::select( $image_sel, $image_mod ) as $image )
            {
              $db_review = $review_class_name::get_unique_record(
                ['image_id', 'user_id'],
                [$image['id'], $user_id]
              );
              if( is_null( $db_review ) )
              {
                $db_review = lib::create( 'database\review' );
                $db_review->image_id = $image['id'];
                $db_review->user_id = $user_id;
                $db_review->save();
                $data['new']++;
              }
            }
          }
        }
        else
        {
          // determine how many reviews (existing and missing) of each study phase and modality exist
          $participant_sel = lib::create( 'database\select' );
          $participant_sel->add_table_column( 'study_phase', 'name', 'study_phase' );
          $participant_sel->add_table_column( 'modality', 'name', 'modality' );
          $participant_sel->add_column( 'review.id IS NOT NULL', 'has_review', false );
          $participant_sel->add_column( 'COUNT(*)', 'total', false );
          $participant_mod = clone $modifier;
          $participant_mod->join( 'study_phase', 'interview.study_phase_id', 'study_phase.id' );
          $participant_mod->join( 'modality', 'scan_type.modality_id', 'modality.id' );
          $participant_mod->join( 'image', 'exam.id', 'image.exam_id' );
          $participant_mod->left_join( 'review', 'image.id', 'review.image_id' );
          $participant_mod->group( 'study_phase.id' );
          $participant_mod->group( 'modality.id' );
          $participant_mod->group( 'review.id IS NULL' );
          $participant_mod->order( 'study_phase.name' );
          $participant_mod->order( 'modality.name' );
          $participant_mod->order( 'review.id IS NOT NULL' );
          $participant_mod->where( 'uid', 'IN', $uid_list );

          $data = [
            'uid_list' => $uid_list,
            'image_list' => []
          ];

          // break down the number of images for each phase, modality and whether it has a review
          foreach( $participant_class_name::select( $participant_sel, $participant_mod ) as $row )
          {
            $phase = $row['study_phase'];
            $modality = $row['modality'];

            if( !array_key_exists( $phase, $data['image_list'] ) ) $data['image_list'][$phase] = [];
            if( !array_key_exists( $modality, $data['image_list'][$phase] ) )
              $data['image_list'][$phase][$modality] = ['with' => 0, 'without' => 0];
            $data['image_list'][$phase][$modality][$row['has_review'] ? 'with' : 'without'] = $row['total'];
          }
        }
      }

      $this->set_data( $data );
    }
    else parent::execute();
  }

  /**
   * A caching variable
   * @var integer
   */
  protected $image_id = NULL;
}
