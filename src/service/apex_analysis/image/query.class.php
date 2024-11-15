<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\apex_analysis\image;
use cenozo\lib, cenozo\log, alder\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Replace parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // the status will be 404, reset it to 200
    $this->status->set_code( 200 );
  }

  /**
   * Extends parent method
   */
  protected function setup()
  {
    // cache the list of images that can be uploded to apex for this apex_anslysis
    $this->images_for_apex = $this->get_parent_record()->get_images_for_apex();
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    return count( $this->images_for_apex );
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    return $this->images_for_apex;
  }

  /**
   * A cache of all images that can be uploaded to apex for this apex_anslysis
   * @var array(array)
   * @access private
   */
  private $images_for_apex = NULL;
}
