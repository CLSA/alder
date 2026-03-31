CREATE TABLE exam_effective_apex_review (
  exam_id INT(10) UNSIGNED NOT NULL,
  apex_review_id INT(10) UNSIGNED NULL DEFAULT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (exam_id),
  INDEX fk_exam_id (exam_id ASC),
  INDEX fk_apex_review_id (apex_review_id ASC),
  CONSTRAINT fk_exam_effective_apex_review_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES alder.exam (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_effective_apex_review_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES alder.apex_review (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
