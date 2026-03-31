CREATE TABLE apex_review_effective_apex_analysis (
  apex_review_id INT(10) UNSIGNED NOT NULL,
  apex_analysis_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  INDEX fk_apex_review_id (apex_review_id ASC),
  INDEX fk_apex_analysis_id (apex_analysis_id ASC),
  PRIMARY KEY (apex_review_id, apex_analysis_id),
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES alder.apex_review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES alder.apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
