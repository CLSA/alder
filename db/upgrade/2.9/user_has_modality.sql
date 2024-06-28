DROP PROCEDURE IF EXISTS patch_user_has_modality;
DELIMITER //
CREATE PROCEDURE patch_user_has_modality()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new user_has_modality table" AS ""; 

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS user_has_modality ( ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "modality_id INT(10) UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "PRIMARY KEY (user_id, modality_id), ",
        "INDEX fk_modality_id (modality_id ASC), ",
        "INDEX fk_user_id (user_id ASC), ",
        "CONSTRAINT fk_user_has_modality_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_user_has_modality_modality_id ",
          "FOREIGN KEY (modality_id) ",
          "REFERENCES modality (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_user_has_modality();
DROP PROCEDURE IF EXISTS patch_user_has_modality;
