SELECT "Creating new scan_type_has_code_type table" AS "";

CREATE TABLE IF NOT EXISTS scan_type_has_code_type (
  scan_type_id INT(10) UNSIGNED NOT NULL,
  code_type_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (scan_type_id, code_type_id),
  INDEX fk_code_type_id (code_type_id ASC),
  INDEX fk_scan_type_id (scan_type_id ASC),
  CONSTRAINT fk_scan_type_has_code_type_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES scan_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_scan_type_has_code_type_code_type_id
    FOREIGN KEY (code_type_id)
    REFERENCES code_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
