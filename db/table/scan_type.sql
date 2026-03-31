CREATE TABLE scan_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  modality_id INT(10) UNSIGNED NOT NULL,
  name VARCHAR(45) NOT NULL,
  side ENUM("left", "right", "none") NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_modality_id (modality_id ASC),
  UNIQUE INDEX uq_name_side (name ASC, side ASC),
  CONSTRAINT fk_scan_type_modality_id
    FOREIGN KEY (modality_id)
    REFERENCES alder.modality (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
