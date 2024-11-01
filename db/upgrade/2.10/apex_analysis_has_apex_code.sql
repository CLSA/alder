SELECT "Creating new apex_analysis_has_apex_code table" AS "";

CREATE TABLE IF NOT EXISTS apex_analysis_has_apex_code (
  apex_analysis_id INT(10) UNSIGNED NOT NULL,
  apex_code_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (apex_analysis_id, apex_code_id),
  INDEX fk_apex_code_id (apex_code_id ASC),
  INDEX fk_apex_analysis_id (apex_analysis_id ASC),
  CONSTRAINT fk_apex_analysis_has_apex_code_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_has_apex_code_apex_code_id
    FOREIGN KEY (apex_code_id)
    REFERENCES apex_code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
