CREATE TABLE apex_review (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  exam_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  start_datetime datetime NOT NULL,
  end_datetime datetime DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exam_id_user_id (exam_id,user_id),
  KEY fk_exam_id (exam_id),
  KEY fk_user_id (user_id),
  CONSTRAINT fk_apex_review_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES exam (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_review_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
