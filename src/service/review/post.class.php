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

      if( array_key_exists( 'uid_list', $file ) )
      {
        // only tier-3 roles can process uid-lists
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

    if( !array_key_exists( 'uid_list', $file ) )
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
    $image_class_name = lib::get_class_name( 'database\image' );
    $review_class_name = lib::get_class_name( 'database\review' );
    $file = $this->get_file_as_array();

    if( array_key_exists( 'uid_list', $file ) )
    {
      $modifier = lib::create( 'database\modifier' );

      $user_id = array_key_exists( 'user_id', $file ) ? $file['user_id'] : NULL;
      $process = array_key_exists( 'process', $file ) && $file['process'];

      $uid_list = $image_class_name::get_valid_uid_list( $file['uid_list'], $modifier );

      if( $process )
      {
        // assign reviews to the user if requested
        if( !is_null( $user_id ) )
        {
          $image_sel = lib::create( 'database\select' );
          $image_sel->from( 'image' );
          $image_sel->add_column( 'id' );
          $image_mod = lib::create( 'database\modifier' );
          $image_mod->join( 'exam', 'image.exam_id', 'exam.id' );
          $image_mod->join( 'interview', 'exam.interview_id', 'interview.id' );
          $image_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
          $image_mod->where( 'uid', 'IN', $uid_list );
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
            }

            $db_review->save();
          }
        }
      }
      else
      {
        $this->set_data( $uid_list );
      }
    }
    else parent::execute();
  }

  /**
   * A caching variable
   * @var integer
   */
  protected $image_id = NULL;
}
