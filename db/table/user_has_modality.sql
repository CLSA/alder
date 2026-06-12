CREATE TABLE user_has_modality (
  user_id int(10) unsigned NOT NULL,
  modality_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (user_id,modality_id),
  KEY fk_modality_id (modality_id),
  KEY fk_user_id (user_id),
  CONSTRAINT fk_user_has_modality_modality_id
    FOREIGN KEY (modality_id)
    REFERENCES modality (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_user_has_modality_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
