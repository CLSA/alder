CREATE TABLE exam_effective_apex_review (
  exam_id int(10) unsigned NOT NULL,
  apex_review_id int(10) unsigned DEFAULT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (exam_id),
  KEY fk_exam_id (exam_id),
  KEY fk_apex_review_id (apex_review_id),
  CONSTRAINT fk_exam_effective_apex_review_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES apex_review (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_effective_apex_review_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES exam (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;