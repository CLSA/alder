CREATE TABLE interview (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  participant_id int(10) unsigned NOT NULL,
  study_phase_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned DEFAULT NULL,
  token char(19) DEFAULT NULL,
  previous_fracture tinyint(1) DEFAULT NULL,
  parent_hip_fracture tinyint(1) DEFAULT NULL,
  current_smoker tinyint(1) DEFAULT NULL,
  glucocorticoid tinyint(1) DEFAULT NULL,
  rheumatoid_arthritis tinyint(1) DEFAULT NULL,
  secondary_osteoporosis tinyint(1) DEFAULT NULL,
  alcohol tinyint(1) DEFAULT NULL,
  height float DEFAULT NULL,
  weight float DEFAULT NULL,
  body_mass_index float DEFAULT NULL,
  start_datetime datetime DEFAULT NULL,
  end_datetime datetime DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_participant_id_study_phase_id (participant_id,study_phase_id),
  KEY fk_participant_id (participant_id),
  KEY fk_study_phase_id (study_phase_id),
  KEY fk_site_id (site_id),
  CONSTRAINT fk_interview_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_study_phase_id
    FOREIGN KEY (study_phase_id)
    REFERENCES cenozo.study_phase (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;