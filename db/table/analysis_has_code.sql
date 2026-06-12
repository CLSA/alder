CREATE TABLE analysis_has_code (
  analysis_id int(10) unsigned NOT NULL,
  code_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (analysis_id,code_id),
  KEY fk_code_id (code_id),
  KEY fk_analysis_has_code_analysis_id (analysis_id),
  CONSTRAINT fk_analysis_has_code_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_has_code_code_id
    FOREIGN KEY (code_id)
    REFERENCES code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
