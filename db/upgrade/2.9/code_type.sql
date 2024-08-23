SELECT "Creating new code_type table" AS "";

CREATE TABLE IF NOT EXISTS code_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  code_group_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) NOT NULL,
  name VARCHAR(45) NOT NULL,
  value INT(10) NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (id),
  INDEX fk_code_group_id (code_group_id ASC),
  UNIQUE INDEX uq_code_group_id_rank (code_group_id ASC, rank ASC),
  UNIQUE INDEX uq_code_group_id_name (code_group_id ASC, name ASC),
  CONSTRAINT fk_code_type_code_group_id
    FOREIGN KEY (code_group_id)
    REFERENCES code_group (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @scan_type_id FROM scan_type WHERE name = "hip" AND side = "left";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Positioning of Hip";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "PL", "hip too lateral"),
(@id, 2, "PM", "hip too medial"),
(@id, 3, "PS", "hip too superior"),
(@id, 4, "PI", "hip too inferior");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Femur Angulation";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "Fad", "femur is adducted"),
(@id, 2, "Fab", "femur is abducted");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "AM", "medial line needs adjustment"),
(@id, 2, "AL", "lateral line needs adjustment"),
(@id, 3, "AS", "superior line needs adjustment"),
(@id, 4, "AI", "inferior line needs adjustment");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Neck Box";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "CA", "corner not anchored in the trochanteric notch properly"),
(@id, 2, "CS", "one or all of the corners that should be touching soft tissue only are touching bone");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");

SELECT id INTO @scan_type_id FROM scan_type WHERE name = "hip" AND side = "right";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Positioning of Hip";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "PL", "hip too lateral"),
(@id, 2, "PM", "hip too medial"),
(@id, 3, "PS", "hip too superior"),
(@id, 4, "PI", "hip too inferior");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Femur Angulation";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "Fad", "femur is adducted"),
(@id, 2, "Fab", "femur is abducted");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "AM", "medial line needs adjustment"),
(@id, 2, "AL", "lateral line needs adjustment"),
(@id, 3, "AS", "superior line needs adjustment"),
(@id, 4, "AI", "inferior line needs adjustment");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Neck Box";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "CA", "corner not anchored in the trochanteric notch properly"),
(@id, 2, "CS", "one or all of the corners that should be touching soft tissue only are touching bone");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "lateral";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Lateral Spine Position";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "LI", "lumbar spine too inferior"),
(@id, 2, "LS", "lumbar spine too superior");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Vertebra Certainty";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "L4", "spine is too inferior causing uncertainty of L4");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "wbody";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Position on Table";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "WS", "whole body needs to be straighter"),
(@id, 2, "WC", "whole body is not centered"),
(@id, 3, "Wha", "arms/hands not within scan limits or cut off at unequal amounts"),
(@id, 4, "WH", "hands touching sides");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "WN", "line is not well placed below neck and above shoulders"),
(@id, 2, "WT", "spine is not properly framed and/or line is not dividing T12-L1"),
(@id, 3, "Wsh", "lines not placed past the head of humerus"),
(@id, 4, "WP", "pelvis lines not appropriately placed"),
(@id, 5, "WL", "legs not appropriately framed");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Jewellery";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "JW", "jewellery not removed");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "forearm" AND side = "left";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Position of Radius and Ulna";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "FS", "radius and ulna are not straight"),
(@id, 2, "FC", "radius and ulna are not centered");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Carpal Bones";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "CB", "first row of carpal bones is not included or more than first row included");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "FY", "yellow line closest to the carpal bones is not at the tip of the ulna styloid process"),
(@id, 2, "FB", "two outer lines are touching bone or are not moved inward and placed to the outside of bone"),
(@id, 3, "FI", "center line is not intersecting the joint between the radius and ulna");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "forearm" AND side = "right";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Position of Radius and Ulna";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "FS", "radius and ulna are not straight"),
(@id, 2, "FC", "radius and ulna are not centered");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Carpal Bones";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "CB", "first row of carpal bones is not included or more than first row included");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "FY", "yellow line closest to the carpal bones is not at the tip of the ulna styloid process"),
(@id, 2, "FB", "two outer lines are touching bone or are not moved inward and placed to the outside of bone"),
(@id, 3, "FI", "center line is not intersecting the joint between the radius and ulna");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "spine";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Lumbar Spine Position";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "LSp", "spine too superior"),
(@id, 2, "LIn", "spine too inferior"),
(@id, 3, "LLt", "spine too lateral"),
(@id, 4, "LMd", "spine too medial");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "L1 and L5 Vertebrae";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "L1", "L1 vertebra not fully visible"),
(@id, 2, "L5", "L5 vertebra not fully visible");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "LV1", "LV1 line touching bone"),
(@id, 2, "LV2", "LV2 line touching bone"),
(@id, 3, "LV3", "LV3 line touching bone"),
(@id, 4, "LV4", "LV4 line touching bone"),
(@id, 5, "LV5", "LV5 line touching bone");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "90 Degree Corner";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "CR12", "CR12"),
(@id, 2, "CR5", "CR5");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Metal on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MET", "metal is present on the scan but not obstructing the field of view");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Motion on Scan";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "MS", "motion is present on the scan");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Not Usable";
INSERT IGNORE INTO code_type( code_group_id, rank, name, description ) VALUES
(@id, 1, "ART", "artifact is obstructing the field of view"),
(@id, 2, "NU", "the scan is not useable");


SELECT id INTO @scan_type_id FROM scan_type WHERE name = "carotid_intima" AND side = "left";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Carotid Artery Scanned";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "AR", 0, "Artery imaged"),
(@id, 2, "AS", -1, "Artery imaged but sliced and only half visible"),
(@id, 3, "NA", -5, "No artery visible, jugular vein imaged"),
(@id, 4, "NO", -5, "Nothing identifiable imaged");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Position of Carotid Artery";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "PI", 0, "Perpendicular to sound beam"),
(@id, 2, "AN", -1, "On an angle");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "IMT Visualization";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "CE", 0, "Clear cIMT on far wall - entire length"),
(@id, 2, "CS", -1, "Clear cIMT on far wall - reduced section"),
(@id, 3, "PC", -1, "cIMT present but poor contrast and therefore blurry image"),
(@id, 4, "NI", -5, "No far wall cIMT visible");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis Box Placement";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "SB", -4, "Analysis box is in correct location but is not a minimum of 250 points"),
(@id, 2, "SK", -1, "Analysis box is in correct location and has greater than 250 points but not 500 points"),
(@id, 3, "GB", 0, "Analysis box is in correct location and includes 500 points"),
(@id, 4, "LO", -1, "Location of analysis box is incorrect, should be moved to right or left");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "cIMT Analysis Accuracy";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "AM", 0, "Edge detection appears to be accurately following far wall cIMT"),
(@id, 2, "ME", -4, "Lines drop out at a small section therefore inaccurately measuring the cIMT"),
(@id, 3, "ZE", -5, "");

SELECT id INTO @scan_type_id FROM scan_type WHERE name = "carotid_intima" AND side = "right";

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Carotid Artery Scanned";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "AR", 0, "Artery imaged"),
(@id, 2, "AS", -1, "Artery imaged but sliced and only half visible"),
(@id, 3, "NA", -5, "No artery visible, jugular vein imaged"),
(@id, 4, "NO", -5, "Nothing identifiable imaged");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Position of Carotid Artery";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "PI", 0, "Perpendicular to sound beam"),
(@id, 2, "AN", -1, "On an angle");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "IMT Visualization";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "CE", 0, "Clear cIMT on far wall - entire length"),
(@id, 2, "CS", -1, "Clear cIMT on far wall - reduced section"),
(@id, 3, "PC", -1, "cIMT present but poor contrast and therefore blurry image"),
(@id, 4, "NI", -5, "No far wall cIMT visible");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "Analysis Box Placement";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "SB", -4, "Analysis box is in correct location but is not a minimum of 250 points"),
(@id, 2, "SK", -1, "Analysis box is in correct location and has greater than 250 points but not 500 points"),
(@id, 3, "GB", 0, "Analysis box is in correct location and includes 500 points"),
(@id, 4, "LO", -1, "Location of analysis box is incorrect, should be moved to right or left");

SELECT id INTO @id FROM code_group WHERE scan_type_id = @scan_type_id AND name = "cIMT Analysis Accuracy";
INSERT IGNORE INTO code_type( code_group_id, rank, name, value, description ) VALUES
(@id, 1, "AM", 0, "Edge detection appears to be accurately following far wall cIMT"),
(@id, 2, "ME", -4, "Lines drop out at a small section therefore inaccurately measuring the cIMT"),
(@id, 3, "ZE", -5, "");
