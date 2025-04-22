DROP PROCEDURE IF EXISTS patch_code_group;
DELIMITER //
CREATE PROCEDURE patch_code_group()
  BEGIN

    SELECT "Adding new apex column to code_group table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "code_group"
    AND column_name = "apex";

    IF @test = 0 THEN
      ALTER TABLE code_group
      ADD COLUMN apex TINYINT(1) NOT NULL DEFAULT 0
      AFTER scan_type_id;

      ALTER TABLE code_group
        DROP INDEX uq_scan_type_id_rank,
        DROP INDEX uq_scan_type_id_name;

      ALTER TABLE code_group
        ADD UNIQUE INDEX uq_scan_type_id_apex_rank (scan_type_id ASC, apex ASC, rank ASC),
        ADD UNIQUE INDEX uq_scan_type_id_apex_name (scan_type_id ASC, apex ASC, name ASC);

      SELECT "Adding new Apex code groups" AS "";

      SELECT id INTO @id FROM scan_type WHERE name = "forearm" AND side = "left";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "Angulation", 0, "Whether the forearm is abducted or adducted."),
      (@id, 1, 2, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 3, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 4, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "forearm" AND side = "right";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "Angulation", 0, "Whether the forearm is abducted or adducted."),
      (@id, 1, 2, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 3, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 4, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "hip" AND side = "left";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "Angulation", 0, "Whether the femur is abducted or adducted."),
      (@id, 1, 2, "Rotation", 0, "Whether the femur is rotated externally or internally."),
      (@id, 1, 3, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 4, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 5, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "hip" AND side = "right";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "Angulation", 0, "Whether the femur is abducted or adducted."),
      (@id, 1, 2, "Rotation", 0, "Whether the femur is rotated externally or internally."),
      (@id, 1, 3, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 4, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 5, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "lateral" AND side = "none";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 2, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 3, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "spine" AND side = "none";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 2, "Line Placement", 0, "Placement of guide lines."),
      (@id, 1, 3, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 4, "Other", 0, "");

      SELECT id INTO @id FROM scan_type WHERE name = "wbody" AND side = "none";
      INSERT IGNORE INTO code_group (scan_type_id, apex, rank, name, value, description) VALUES
      (@id, 1, 1, "ROI Placement", 0, "Placement of the region of interest (ROI)."),
      (@id, 1, 2, "Line Placement", 0, "Placement of guide lines."),
      (@id, 1, 3, "Artifacts", 0, "Whether there are artifacts present on the scan."),
      (@id, 1, 4, "Symmetry Computations", 0, "Missing symmetry computations."),
      (@id, 1, 5, "Other", 0, "");

    END IF;

  END //
DELIMITER ;

CALL patch_code_group();
DROP PROCEDURE IF EXISTS patch_code_group;


SELECT "Adding new Spirometry code_group" AS "";

INSERT IGNORE INTO code_group(scan_type_id, apex, rank, name, value, description)
SELECT scan_type.id, 0, 1, "Graph Measurements", 0, "Validation of graph display measurements."
FROM scan_type
WHERE name = "spirometry";
