<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace alder\service\image\arrow;
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

    // set the arrow's owner
    $db_arrow = $this->get_leaf_record();
    $db_arrow->user_id = lib::create( 'business\session' )->get_user()->id;
  }
}
