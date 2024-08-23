DROP PROCEDURE IF EXISTS patch_review;
DELIMITER //
CREATE PROCEDURE patch_review()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new review table" AS ""; 

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS review ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "exam_id INT(10) UNSIGNED NOT NULL, ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "completed TINYINT(1) NOT NULL DEFAULT 0, ",
        "notification ENUM('alert', 'read') NULL DEFAULT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_user_id (user_id ASC), ",
        "INDEX fk_exam_id (exam_id ASC), ",
        "UNIQUE INDEX uq_exam_id_user_id (exam_id ASC, user_id ASC), ",
        "CONSTRAINT fk_review_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_review_exam_id ",
          "FOREIGN KEY (exam_id) ",
          "REFERENCES exam (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_review();
DROP PROCEDURE IF EXISTS patch_review;


DELIMITER $$

DROP TRIGGER IF EXISTS review_AFTER_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER review_AFTER_INSERT AFTER INSERT ON review FOR EACH ROW
BEGIN
  INSERT INTO analysis (review_id, image_id)
  SELECT NEW.id, image.id FROM image WHERE exam_id = NEW.exam_id;
END$$

DELIMITER ;
