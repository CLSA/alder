CREATE TABLE analysis_has_code (
  analysis_id INT(10) UNSIGNED NOT NULL,
  code_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (analysis_id, code_id),
  INDEX fk_code_id (code_id ASC),
  INDEX fk_analysis_id (analysis_id ASC),
  CONSTRAINT fk_analysis_has_code_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES alder.analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_has_code_code_id
    FOREIGN KEY (code_id)
    REFERENCES alder.code (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
