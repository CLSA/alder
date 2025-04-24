SELECT "Creating new selection table" AS "";

CREATE TABLE IF NOT EXISTS selection (
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
    REFERENCES scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @scan_type_id FROM scan_type WHERE name = "spirometry";
INSERT IGNORE INTO selection (scan_type_id, rank, name) VALUES
(@scan_type_id, 1, "Grading"),
(@scan_type_id, 2, "Best FEV1"),
(@scan_type_id, 3, "Best FVC"),
(@scan_type_id, 4, "Best PEF");
