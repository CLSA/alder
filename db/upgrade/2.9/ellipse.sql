DROP PROCEDURE IF EXISTS patch_ellipse;
DELIMITER //
CREATE PROCEDURE patch_ellipse()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new ellipse table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS ellipse ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "image_id INT(10) UNSIGNED NOT NULL, ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "x FLOAT NOT NULL, ",
        "y FLOAT NOT NULL, ",
        "rx FLOAT NOT NULL, ",
        "ry FLOAT NOT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_image_id (image_id ASC), ",
        "INDEX fk_user_id (user_id ASC), ",
        "CONSTRAINT fk_ellipse_image_id ",
          "FOREIGN KEY (image_id) ",
          "REFERENCES image (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_ellipse_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_ellipse();
DROP PROCEDURE IF EXISTS patch_ellipse;
