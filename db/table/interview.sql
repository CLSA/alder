CREATE TABLE interview (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  participant_id INT(10) UNSIGNED NOT NULL,
  study_phase_id INT(10) UNSIGNED NOT NULL,
  site_id INT(10) UNSIGNED NULL DEFAULT NULL,
  token CHAR(19) NULL DEFAULT NULL,
  previous_fracture TINYINT(1) NULL DEFAULT NULL,
  parent_hip_fracture TINYINT(1) NULL DEFAULT NULL,
  current_smoker TINYINT(1) NULL DEFAULT NULL,
  glucocorticoid TINYINT(1) NULL DEFAULT NULL,
  rheumatoid_arthritis TINYINT(1) NULL DEFAULT NULL,
  secondary_osteoporosis TINYINT(1) NULL DEFAULT NULL,
  alcohol TINYINT(1) NULL DEFAULT NULL,
  height FLOAT NULL DEFAULT NULL,
  weight FLOAT NULL DEFAULT NULL,
  body_mass_index FLOAT NULL DEFAULT NULL,
  start_datetime DATETIME NULL DEFAULT NULL,
  end_datetime DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_participant_id (participant_id ASC),
  INDEX fk_study_phase_id (study_phase_id ASC),
  INDEX fk_site_id (site_id ASC),
  UNIQUE INDEX uq_participant_id_study_phase_id (participant_id ASC, study_phase_id ASC),
  CONSTRAINT fk_interview_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_study_phase_id
    FOREIGN KEY (study_phase_id)
    REFERENCES cenozo.study_phase (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
