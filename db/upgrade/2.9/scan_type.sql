SELECT "Creating new scan_type table" AS "";

CREATE TABLE IF NOT EXISTS scan_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  modality_id INT(10) UNSIGNED NOT NULL,
  name VARCHAR(45) NOT NULL,
  side ENUM("left", "right", "none") NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_modality_id (modality_id ASC),
  UNIQUE KEY uq_name_side (name ASC, side ASC),
  CONSTRAINT fk_scan_type_modality_id
    FOREIGN KEY (modality_id)
    REFERENCES modality (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @dxa_id FROM modality WHERE name = "dxa";
SELECT id INTO @retinal_id FROM modality WHERE name = "retinal";
SELECT id INTO @carotid_intima_id FROM modality WHERE name = "carotid_intima";

INSERT IGNORE INTO scan_type( modality_id, name, side ) VALUES
(@dxa_id, "forearm", "left" ),
(@dxa_id, "forearm", "right" ),
(@dxa_id, "hip", "left" ),
(@dxa_id, "hip", "right" ),
(@dxa_id, "lateral", "none" ),
(@dxa_id, "spine", "none" ),
(@dxa_id, "wbody", "none" ),
(@retinal_id, "retinal", "left"),
(@retinal_id, "retinal", "right"),
(@carotid_intima_id, "carotid_intima", "left"),
(@carotid_intima_id, "carotid_intima", "right");
