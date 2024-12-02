SELECT "Adding spirometry to scan_type table" AS "";

SELECT id INTO @spirometry_id FROM modality WHERE name = "spirometry";

INSERT IGNORE INTO scan_type( modality_id, name, side ) VALUES
(@spirometry_id, "spirometry", "none");
