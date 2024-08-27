SELECT "Creating new image table" AS "";

CREATE TABLE IF NOT EXISTS image (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  exam_id INT(10) UNSIGNED NOT NULL,
  filename VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_exam_id (exam_id ASC),
  UNIQUE INDEX uq_exam_id_path (exam_id ASC, filename ASC),
  CONSTRAINT fk_image_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES exam (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
