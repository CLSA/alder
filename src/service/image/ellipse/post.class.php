<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\image\ellipse;
use cenozo\lib, cenozo\log, alder\util;

/**
 * The base class of all post services.
 */
class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // set the ellipse's owner
    $db_ellipse = $this->get_leaf_record();
    $db_ellipse->user_id = lib::create( 'business\session' )->get_user()->id;
  }
}
