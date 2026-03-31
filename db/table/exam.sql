CREATE TABLE exam (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  interview_id INT(10) UNSIGNED NOT NULL,
  scan_type_id INT(10) UNSIGNED NOT NULL,
  interviewer VARCHAR(45) NULL DEFAULT NULL,
  datetime DATETIME NULL DEFAULT NULL,
  note TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_interview_id (interview_id ASC),
  INDEX fk_scan_type_id (scan_type_id ASC),
  UNIQUE INDEX uq_interview_id_scan_type_id (interview_id ASC, scan_type_id ASC),
  CONSTRAINT fk_exam_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES alder.interview (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES alder.scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
