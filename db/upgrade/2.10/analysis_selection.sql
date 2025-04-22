SELECT "Creating new analysis_selection table" AS "";

CREATE TABLE IF NOT EXISTS analysis_selection (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  analysis_id INT(10) UNSIGNED NOT NULL,
  selection_id INT(10) UNSIGNED NOT NULL,
  selection_option_id INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_analysis_id (analysis_id ASC),
  INDEX fk_selection_id (selection_id ASC),
  INDEX fk_selection_option_id (selection_option_id ASC),
  UNIQUE INDEX uq_analysis_id_selection_id (analysis_id ASC, selection_id ASC),
  UNIQUE INDEX uq_analysis_id_selection_option_id (analysis_id ASC, selection_option_id ASC),
  CONSTRAINT fk_analysis_selection_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_selection_selection_id
    FOREIGN KEY (selection_id)
    REFERENCES selection (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_selection_selection_option_id
    FOREIGN KEY (selection_option_id)
    REFERENCES selection_option (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
