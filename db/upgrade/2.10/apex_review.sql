DROP PROCEDURE IF EXISTS patch_apex_review;
DELIMITER //
CREATE PROCEDURE patch_apex_review()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new apex_review table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS apex_review ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "exam_id INT(10) UNSIGNED NOT NULL, ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "start_datetime DATETIME NOT NULL, ",
        "end_datetime DATETIME NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_exam_id (exam_id ASC), ",
        "INDEX fk_user_id (user_id ASC), ",
        "UNIQUE INDEX uq_exam_id_user_id (exam_id ASC, user_id ASC), ",
        "CONSTRAINT fk_apex_review_exam_id ",
          "FOREIGN KEY (exam_id) ",
          "REFERENCES exam (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_apex_review_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_apex_review();
DROP PROCEDURE IF EXISTS patch_apex_review;


DELIMITER $$

DROP TRIGGER IF EXISTS apex_review_AFTER_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER apex_review_AFTER_INSERT AFTER INSERT ON apex_review FOR EACH ROW
BEGIN
  INSERT INTO apex_analysis (apex_review_id, image_id)
  SELECT NEW.id, image.id FROM image WHERE exam_id = NEW.exam_id;
END$$

DELIMITER ;
