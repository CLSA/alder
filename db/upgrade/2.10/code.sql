DROP PROCEDURE IF EXISTS rebuild_code_tables;
DELIMITER //
CREATE PROCEDURE rebuild_code_tables()
  BEGIN

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "code"
    AND column_name = "analysis_id";

    IF @test = 1 THEN
      SELECT "Renaming code table to code2" AS "";
      RENAME TABLE code TO code2;

      SELECT "Renaming code_type table to code" AS "";
      RENAME TABLE code_type TO code;

      ALTER TABLE code DROP CONSTRAINT fk_code_type_code_group_id;
      ALTER TABLE code
        ADD CONSTRAINT fk_code_code_group_id
        FOREIGN KEY (code_group_id)
        REFERENCES code_group (id)
        ON DELETE CASCADE ON UPDATE NO ACTION;
    END IF;

    SELECT COUNT(*) INTO @test
    FROM code_group
    JOIN code ON code_group.id = code.code_group_id
    WHERE code_group.name IN ("Angulation", "Rotation", "ROI Placement", "Artifacts", "Other");

    IF @test = 0 THEN
      SELECT "Importing Apex codes from Salix" AS "";

      -- determine the salix database name
      SELECT REPLACE( DATABASE(), "_alder", "_salix" ) INTO @salix;

      -- left forearm
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Angulation' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code IN ('Ab', 'Ad') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'ROI Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code IN ('high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Artifacts' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Other' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code NOT IN ('Ab', 'Ad', 'high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "AND code_type.code NOT LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- copy left codes to the right
      INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description)
      SELECT t.update_timestamp, t.create_timestamp, code_group.id, t.rank, t.name, t.description
      FROM (
        SELECT code_group.name AS code_group_name, code.*
        FROM code
        JOIN code_group ON code.code_group_id = code_group.id
        JOIN scan_type ON code_group.scan_type_id = scan_type.id
        WHERE scan_type.name = "forearm"
        AND scan_type.side = "left"
        AND code_group.apex = 1
      ) AS t
      CROSS JOIN scan_type
      JOIN code_group on scan_type.id = code_group.scan_type_id
      WHERE scan_type.name = "forearm"
      AND scan_type.side = "right"
      AND t.code_group_name = code_group.name;

      -- left hip
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Angulation' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code IN ('Ab', 'Ad') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Rotation' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code IN ('Erot', 'Irot') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'ROI Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code IN ('high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Artifacts' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Other' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "AND code_type.code NOT IN ('Ab', 'Ad', 'Erot', 'Irot', 'high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "AND code_type.code NOT LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- copy left codes to the right
      INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description)
      SELECT t.update_timestamp, t.create_timestamp, code_group.id, t.rank, t.name, t.description
      FROM (
        SELECT code_group.name AS code_group_name, code.*
        FROM code
        JOIN code_group ON code.code_group_id = code_group.id
        JOIN scan_type ON code_group.scan_type_id = scan_type.id
        WHERE scan_type.name = "hip"
        AND scan_type.side = "left"
        AND code_group.apex = 1
      ) AS t
      CROSS JOIN scan_type
      JOIN code_group on scan_type.id = code_group.scan_type_id
      WHERE scan_type.name = "hip"
      AND scan_type.side = "right"
      AND t.code_group_name = code_group.name;

      -- lateral
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'ROI Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'lateral' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code IN ('high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Artifacts' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'lateral' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Other' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'lateral' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code NOT IN ('high', 'low', 'left', 'right', 'oversized', 'undersized') ",
        "AND code_type.code NOT LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- spine
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'ROI Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'spine' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code IN ('high', 'low', 'left', 'right', 'undersized', 'oversized') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Artifacts' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'spine' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Line Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'spine' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'lines(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Other' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'spine' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code NOT IN ('high', 'low', 'left', 'right', 'undersized', 'oversized') ",
        "AND code_type.code NOT LIKE 'art(%' ",
        "AND code_type.code NOT LIKE 'lines(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- wbody
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'ROI Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code IN ('high', 'low', 'left', 'right', 'undersized', 'oversized') ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Artifacts' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'art(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Line Placement' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'lines(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Symmetry Computations' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code LIKE 'sym(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Other' ",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "AND code_type.code NOT IN ('high', 'low', 'left', 'right', 'undersized', 'oversized') ",
        "AND code_type.code NOT LIKE 'art(%' ",
        "AND code_type.code NOT LIKE 'lines(%' ",
        "AND code_type.code NOT LIKE 'sym(%' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;

  END //
DELIMITER ;

CALL rebuild_code_tables();
DROP PROCEDURE IF EXISTS rebuild_code_tables;


SELECT "Adding new Spirometry codes" AS "";

SELECT code_group.id INTO @code_group_id
FROM code_group
JOIN scan_type on code_group.scan_type_id = scan_type.id
WHERE scan_type.name = "spirometry"
AND code_group.rank = 1;

INSERT IGNORE INTO code(code_group_id, rank, name, value, description) VALUES
(@code_group_id, 1, "start", 0, "There is not a good start."),
(@code_group_id, 2, "peak", 0, "There is not a good peak."),
(@code_group_id, 3, "early", 0, "There is early termination."),
(@code_group_id, 4, "effort", 0, "There is not effort for more than 6 seconds."),
(@code_group_id, 5, "cough", 0, "There is a cough."),
(@code_group_id, 6, "end", 0, "There is not a proper end criteria."),
(@code_group_id, 7, "curves", 0, "There are no acceptable curves."),
(@code_group_id, 8, "repro", 0, "The reproducibility is <200 or >200.");
