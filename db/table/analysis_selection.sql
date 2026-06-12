CREATE TABLE analysis_selection (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  analysis_id int(10) unsigned NOT NULL,
  selection_id int(10) unsigned NOT NULL,
  selection_option_id int(10) unsigned NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_analysis_id_selection_id (analysis_id,selection_id),
  UNIQUE KEY uq_analysis_id_selection_option_id (analysis_id,selection_option_id),
  KEY fk_analysis_id (analysis_id),
  KEY fk_selection_id (selection_id),
  KEY fk_selection_option_id (selection_option_id),
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
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
