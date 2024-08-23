SELECT "Creating new code table" AS "";

CREATE TABLE IF NOT EXISTS code (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  analysis_id INT(10) UNSIGNED NOT NULL,
  code_type_id INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_code_type_id (code_type_id ASC),
  INDEX fk_analysis_id (analysis_id ASC),
  UNIQUE INDEX uq_analysis_id_code_type_id (analysis_id ASC, code_type_id ASC),
  CONSTRAINT fk_code_code_type_id
    FOREIGN KEY (code_type_id)
    REFERENCES code_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_code_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


DELIMITER $$

DROP TRIGGER IF EXISTS code_AFTER_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER code_AFTER_INSERT AFTER INSERT ON code FOR EACH ROW
BEGIN
  CALL calculate_rating(NEW.analysis_id);
END$$

DROP TRIGGER IF EXISTS code_AFTER_DELETE$$
CREATE DEFINER = CURRENT_USER TRIGGER code_AFTER_DELETE AFTER DELETE ON code FOR EACH ROW
BEGIN
  CALL calculate_rating(OLD.analysis_id);
END$$

DELIMITER ;
