SELECT "Creating new code_group table" AS "";

CREATE TABLE IF NOT EXISTS code_group (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  scan_type_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) NOT NULL,
  name VARCHAR(45) NOT NULL,
  value INT(10) NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (id),
  INDEX fk_scan_type_id (scan_type_id ASC),
  UNIQUE INDEX uq_scan_type_id_rank (scan_type_id ASC, rank ASC),
  UNIQUE INDEX uq_scan_type_id_name (scan_type_id ASC, name ASC),
  CONSTRAINT fk_code_group_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @id FROM scan_type WHERE name = "hip";
INSERT IGNORE INTO code_group( scan_type_id, rank, name, value, description ) VALUES
(@id, 1, "Positioning of Hip", -1, "Positioning of the hip within the field of view."),
(@id, 2, "Femur Angulation", -1, "Straightness of the shaft of the femur."),
(@id, 3, "Analysis", -1, "Positioning of the analysis box around the femur."),
(@id, 4, "Neck Box", -1, "Positioning of the corners of the neck box."),
(@id, 5, "Metal on Scan", -1, "Metal is present on the scan but not obstructing the field of view."),
(@id, 6, "Motion on Scan", -1, "Motion is present on the scan."),
(@id, 7, "Not Usable", -5, "Reasons that the scan is not usable.");

SELECT id INTO @id FROM scan_type WHERE name = "lateral";
INSERT IGNORE INTO code_group( scan_type_id, rank, name, value, description ) VALUES
(@id, 1, "Lateral Spine Position", -1, "Inclusion of vertebrae in the field of view."),
(@id, 2, "Vertebra Certainty", -1, "Inclusion of the 4th vertebra."),
(@id, 3, "Metal on Scan", -1, "Metal is present on the scan but not obstructing the field of view."),
(@id, 4, "Motion on Scan", -1, "Motion is present on the scan."),
(@id, 5, "Not Usable", -5, "Reasons that the scan is not usable.");

SELECT id INTO @id FROM scan_type WHERE name = "wbody";
INSERT IGNORE INTO code_group( scan_type_id, rank, name, value, description ) VALUES
(@id, 1, "Position on Table", -1, "Participant needs to be straight and positioned in middle of table to include entire whole body. Arms and hands need to be within scan limit borders and not touching sides. Hands/fingers can be cut off but must be equal amounts."),
(@id, 2, "Analysis", -1, "Positioning of analysis lines and points."),
(@id, 3, "Jewellery", -1, "Jewellery that can come off should come off for whole body scan."),
(@id, 4, "Metal on Scan", -1, "Metal is present on the scan but not obstructing the field of view."),
(@id, 5, "Motion on Scan", -1, "Motion is present on the scan."),
(@id, 6, "Not Usable", -5, "Reasons that the scan is not usable.");

SELECT id INTO @id FROM scan_type WHERE name = "forearm";
INSERT IGNORE INTO code_group( scan_type_id, rank, name, value, description ) VALUES
(@id, 1, "Position of Radius and Ulna", -1, "Radius and ulna need to be straight and centered."),
(@id, 2, "Carpal Bones", -1, "Image shows the first row of carpal bones."),
(@id, 3, "Analysis", -1, "Forearm analysis lines and points, are all placed correctly."),
(@id, 4, "Metal on Scan", -1, "Metal is present on the scan but not obstructing the field of view."),
(@id, 5, "Motion on Scan", -1, "Motion is present on the scan."),
(@id, 6, "Not Usable", -5, "Reasons that the scan is not usable.");

SELECT id INTO @id FROM scan_type WHERE name = "spine";
INSERT IGNORE INTO code_group( scan_type_id, rank, name, value, description ) VALUES
(@id, 1, "Lumbar Spine Position", -1, "Whether correction to the placement of the AP Lumbar spine within the field of view is required."),
(@id, 2, "L1 and L5 Vertebrae", -1, "L1 and L5 vertebrae not completely captured."),
(@id, 3, "Analysis", -1, "Intervertebral analysis lines in joint space and not touching bone."),
(@id, 4, "90 Degree Corner", -1, "Severe scoliosis present causing T12-L1 and L4-L5 to not have a 90 degree corner."),
(@id, 5, "Metal on Scan", -1, "Metal is present on the scan but not obstructing the field of view."),
(@id, 6, "Motion on Scan", -1, "Motion is present on the scan."),
(@id, 7, "Not Usable", -5, "Reasons that the scan is not usable.");

SELECT id INTO @id FROM scan_type WHERE name = "carotid_intima";
INSERT IGNORE INTO code_group( scan_type_id, rank, name ) VALUES
(@id, 1, "Carotid Artery Scanned"),
(@id, 2, "Position of Carotid Artery"),
(@id, 3, "IMT Visualization"),
(@id, 4, "Analysis Box Placement"),
(@id, 5, "cIMT Analysis Accuracy");
