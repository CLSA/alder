SELECT "Creating new code table" AS "";

CREATE TABLE IF NOT EXISTS code (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  review_id INT(10) UNSIGNED NOT NULL,
  code_type_id INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_code_type_id (code_type_id ASC),
  INDEX fk_review_id (review_id ASC),
  UNIQUE INDEX uq_review_id_code_type_id (review_id ASC, code_type_id ASC),
  CONSTRAINT fk_code_code_type_id
    FOREIGN KEY (code_type_id)
    REFERENCES code_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_cpde_review_id
    FOREIGN KEY (review_id)
    REFERENCES review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


DELIMITER $$

DROP TRIGGER IF EXISTS code_AFTER_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER code_AFTER_INSERT AFTER INSERT ON code FOR EACH ROW
BEGIN
  CALL calculate_rating(NEW.review_id);
END$$

DROP TRIGGER IF EXISTS code_AFTER_DELETE$$
CREATE DEFINER = CURRENT_USER TRIGGER code_AFTER_DELETE AFTER DELETE ON code FOR EACH ROW
BEGIN
  CALL calculate_rating(OLD.review_id);
END$$

DELIMITER ;
