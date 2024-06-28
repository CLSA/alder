SELECT "Creating new exam table" AS "";

CREATE TABLE IF NOT EXISTS exam (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  interview_id INT(10) UNSIGNED NOT NULL,
  scan_type_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) NOT NULL,
  side ENUM("right", "left", "none") NOT NULL,
  interviewer VARCHAR(45) NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_interview_id (interview_id ASC),
  INDEX fk_scan_type_id (scan_type_id ASC),
  UNIQUE INDEX uq_interview_id_scan_type_id_rank (interview_id ASC, scan_type_id ASC, rank ASC),
  CONSTRAINT fk_exam_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
