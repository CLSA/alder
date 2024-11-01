DROP PROCEDURE IF EXISTS populate_analysis_has_code;
DELIMITER //
CREATE PROCEDURE populate_analysis_has_code()
  BEGIN

    SELECT COUNT(*) INTO @test
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "code2";

    IF @test = 1 THEN
      SELECT "Transferring data from defunct code2 table to analysis_has_code table" AS "";

      -- Note that the code_type table has been renamed to code
      INSERT IGNORE INTO analysis_has_code( analysis_id, code_id, update_timestamp, create_timestamp )
      SELECT analysis_id, code_type_id, update_timestamp, create_timestamp
      FROM code2;

      DROP TABLE code2;
    END IF;

  END //
DELIMITER ;

SELECT "Creating new analysis_has_code table" AS "";

CREATE TABLE IF NOT EXISTS analysis_has_code (
  analysis_id INT(10) UNSIGNED NOT NULL,
  code_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (analysis_id, code_id),
  INDEX fk_code_id (code_id ASC),
  INDEX fk_analysis_has_code_analysis_id (analysis_id ASC),
  CONSTRAINT fk_analysis_has_code_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_has_code_code_id
    FOREIGN KEY (code_id)
    REFERENCES code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CALL populate_analysis_has_code();
DROP PROCEDURE IF EXISTS populate_analysis_has_code;


DELIMITER $$

DROP TRIGGER IF EXISTS analysis_has_code_AFTER_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER analysis_has_code_AFTER_INSERT AFTER INSERT ON analysis_has_code FOR EACH ROW
BEGIN
  CALL calculate_rating(NEW.analysis_id);
END$$

DROP TRIGGER IF EXISTS analysis_has_code_AFTER_DELETE$$
CREATE DEFINER = CURRENT_USER TRIGGER analysis_has_code_AFTER_DELETE AFTER DELETE ON analysis_has_code FOR EACH ROW
BEGIN
  CALL calculate_rating(OLD.analysis_id);
END$$

DELIMITER ;
