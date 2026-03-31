CREATE TABLE apex_analysis_selection (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  apex_analysis_id INT(10) UNSIGNED NOT NULL,
  selection_id INT(10) UNSIGNED NOT NULL,
  selection_option_id INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_apex_analysis_id (apex_analysis_id ASC),
  INDEX fk_selection_id (selection_id ASC),
  INDEX fk_selection_option_id (selection_option_id ASC),
  UNIQUE INDEX uq_apex_analysis_id_selection_id (apex_analysis_id ASC, selection_id ASC),
  UNIQUE INDEX uq_apex_analysis_id_selection_option_id (apex_analysis_id ASC, selection_option_id ASC),
  CONSTRAINT fk_apex_analysis_selection_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES alder.apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_selection_selection_id
    FOREIGN KEY (selection_id)
    REFERENCES alder.selection (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_selection_selection_option_id
    FOREIGN KEY (selection_option_id)
    REFERENCES alder.selection_option (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
