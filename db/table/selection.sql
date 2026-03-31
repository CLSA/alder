CREATE TABLE selection (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  scan_type_id INT(10) UNSIGNED NOT NULL,
  apex TINYINT(1) NOT NULL DEFAULT 0,
  rank INT(10) NOT NULL,
  name VARCHAR(45) NOT NULL,
  description TEXT NULL,
  PRIMARY KEY (id),
  INDEX fk_scan_type_id (scan_type_id ASC),
  UNIQUE INDEX uq_scan_type_id_apex_rank (scan_type_id ASC, apex ASC, rank ASC),
  UNIQUE INDEX uq_scan_type_id_apex_name (scan_type_id ASC, apex ASC, name ASC),
  CONSTRAINT fk_selection_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES alder.scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
