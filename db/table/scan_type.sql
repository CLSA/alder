CREATE TABLE scan_type (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  modality_id int(10) unsigned NOT NULL,
  name varchar(45) NOT NULL,
  side enum('left','right','none') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_name_side (name,side),
  KEY fk_modality_id (modality_id),
  CONSTRAINT fk_scan_type_modality_id
    FOREIGN KEY (modality_id)
    REFERENCES modality (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
