SELECT "Creating new analysis_has_apex_code table" AS "";

CREATE TABLE IF NOT EXISTS analysis_has_apex_code (
  analysis_id INT(10) UNSIGNED NOT NULL,
  apex_code_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (analysis_id, apex_code_id),
  INDEX fk_apex_code_id (apex_code_id ASC),
  INDEX fk_analysis_id (analysis_id ASC),
  CONSTRAINT fk_analysis_has_apex_code_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_has_apex_code_apex_code_id
    FOREIGN KEY (apex_code_id)
    REFERENCES apex_code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
