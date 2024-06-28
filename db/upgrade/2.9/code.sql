DROP PROCEDURE IF EXISTS patch_code;
DELIMITER //
CREATE PROCEDURE patch_code()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new code table" AS ""; 

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS code ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "image_id INT(10) UNSIGNED NOT NULL, ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "code_type_id INT(10) UNSIGNED NOT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_user_id (user_id ASC), ",
        "INDEX fk_code_type_id (code_type_id ASC), ",
        "INDEX fk_image_id (image_id ASC), ",
        "UNIQUE INDEX uq_image_id_user_id_code_type_id (image_id ASC, user_id ASC, code_type_id ASC), ",
        "CONSTRAINT fk_code_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_code_code_type_id ",
          "FOREIGN KEY (code_type_id) ",
          "REFERENCES code_type (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_code_image_id ",
          "FOREIGN KEY (image_id) ",
          "REFERENCES image (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_code();
DROP PROCEDURE IF EXISTS patch_code;
