<?php
/**
 * image.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @fileimage
 */

namespace alder\database;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * image: record
 */
class image extends \cenozo\database\record
{
  /**
   * Generates a jpeg image from the DICOM image
   */
  public function get_base64_jpeg()
  {
    $b64_string = NULL;

    $db_exam = $this->get_exam();
    $db_modality = $db_exam->get_scan_type()->get_modality();
    $db_interview = $db_exam->get_interview();
    $db_study_phase = $db_interview->get_study_phase();
    $db_participant = $db_interview->get_participant();
    $path = sprintf(
      '%s/%d/%s/%s/%s',
      IMAGES_PATH,
      $db_study_phase->rank,
      $db_modality->name,
      $db_participant->uid,
      $this->filename
    );

    // return jpeg files as is
    if( preg_match( "/\.(jpg|jpeg)$/", $this->filename ) )
    {
      // load the jpeg and encode it
      return base64_encode( file_get_contents( $path ) );
    }

    // unzip gz files
    if( preg_match( "/\.gz$/", $this->filename ) )
    {
      $unzipped_path = sprintf( '%s/image_%d.%s', TEMP_PATH, $this->id, $this->filename );
      copy( $path, $unzipped_path );
      $command = sprintf( 'gunzip -f %s', $unzipped_path );
      $response = util::exec_timeout( $command );
      if( 0 != $response['exitcode'] ) return NULL; // don't proceed if gunzip failed
      $path = preg_replace( '/\.gz$/', '', $unzipped_path );
    }

    // determine if this is a valid image, and encode the first slice
    $command = sprintf( 'identify %s', $path );
    $response = util::exec_timeout( $command );
    $temp_path = sprintf( '%s/image_%d.jpeg', TEMP_PATH, $this->id );
    $lines = count( explode( "\n", $response['output'] ) );

    $response = NULL;
    if( 2 < $lines )
    {
      // try converting each image until a valid one is found
      for( $sub_image = 1; $sub_image <= $lines; $sub_image++ )
      {
        // convert the image to a temporary jpeg file
        $command = sprintf( 'convert %s[%d] %s', $path, $sub_image, $temp_path );
        $response = util::exec_timeout( $command );
        if( 0 == $response['exitcode'] ) break;
      }
    }
    else
    {
      // convert the image to a temporary jpeg file
      $command = sprintf( 'convert %s %s', $path, $temp_path );
      $response = util::exec_timeout( $command );
    }

    if( 0 == $response['exitcode'] )
    {
      // load the jpeg and encode it
      $b64_string = base64_encode( file_get_contents( $temp_path ) );

      // clean up before returning the data
      unlink( $temp_path );
      if( preg_match( "/\.gz$/", $this->filename ) ) unlink( $path );
    }

    return $b64_string;
  }
}
