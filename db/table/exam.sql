CREATE TABLE exam (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  interview_id int(10) unsigned NOT NULL,
  scan_type_id int(10) unsigned NOT NULL,
  interviewer varchar(45) DEFAULT NULL,
  datetime datetime DEFAULT NULL,
  note text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_interview_id_scan_type_id (interview_id,scan_type_id),
  KEY fk_interview_id (interview_id),
  KEY fk_scan_type_id (scan_type_id),
  CONSTRAINT fk_exam_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;