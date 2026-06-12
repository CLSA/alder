CREATE TABLE apex_review_effective_apex_analysis (
  apex_review_id int(10) unsigned NOT NULL,
  apex_analysis_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (apex_review_id,apex_analysis_id),
  KEY fk_apex_review_id (apex_review_id),
  KEY fk_apex_analysis_id (apex_analysis_id),
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES apex_review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
