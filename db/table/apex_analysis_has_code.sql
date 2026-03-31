CREATE TABLE apex_analysis_has_code (
  apex_analysis_id INT(10) UNSIGNED NOT NULL,
  code_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL,
  create_timestamp TIMESTAMP NOT NULL,
  PRIMARY KEY (apex_analysis_id, code_id),
  INDEX fk_code_id (code_id ASC),
  INDEX fk_apex_analysis_id (apex_analysis_id ASC),
  CONSTRAINT fk_apex_analysis_has_code_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES alder.apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_has_code_code_id
    FOREIGN KEY (code_id)
    REFERENCES alder.code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
