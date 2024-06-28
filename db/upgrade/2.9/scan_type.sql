SELECT "Creating new scan_type table" AS "";

CREATE TABLE IF NOT EXISTS scan_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  modality_id INT(10) UNSIGNED NOT NULL,
  name VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_modality_id (modality_id ASC),
  UNIQUE KEY uq_name (name ASC),
  CONSTRAINT fk_scan_type_modality_id
    FOREIGN KEY (modality_id)
    REFERENCES modality (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @dexa_id FROM modality WHERE name = "dexa";
SELECT id INTO @retinal_id FROM modality WHERE name = "retinal";
SELECT id INTO @ultrasound_id FROM modality WHERE name = "ultrasound";

INSERT IGNORE INTO scan_type( modality_id, name ) VALUES
(@dexa_id, "forearm"),
(@dexa_id, "hip"),
(@dexa_id, "lateral"),
(@dexa_id, "spine"),
(@dexa_id, "wbody"),
(@retinal_id, "retinal"),
(@ultrasound_id, "carotid_intima");
