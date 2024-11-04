DROP PROCEDURE IF EXISTS rename_code_tables;
DELIMITER //
CREATE PROCEDURE rename_code_tables()
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
    WHERE code_group.name = "Apex";

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
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'left' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- right forearm
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'forearm' ",
        "AND scan_type.side = 'right' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- left hip
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'left' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- right hip
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'hip' ",
        "AND scan_type.side = 'right' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- lateral
      SET @rank = 0;
      SET @sql = CONCAT(
        "INSERT INTO code(update_timestamp, create_timestamp, code_group_id, rank, name, description) ",
        "SELECT ",
          "code_type.update_timestamp, code_type.create_timestamp, ",
          "code_group.id, @rank := @rank+1, code_type.code, code_type.description ",
        "FROM scan_type ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'lateral' ",
        "AND scan_type.side = 'none' ",
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
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'spine' ",
        "AND scan_type.side = 'none' ",
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
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.name = 'Apex'",
        "JOIN ", @salix, ".scan_type AS salix_scan_type ",
          "ON scan_type.name = salix_scan_type.type ",
          "AND scan_type.side = salix_scan_type.side ",
        "JOIN ", @salix, ".scan_type_has_code_type ON salix_scan_type.id = scan_type_has_code_type.scan_type_id ",
        "JOIN ", @salix, ".code_type ON scan_type_has_code_type.code_type_id = code_type.id ",
        "WHERE scan_type.name = 'wbody' ",
        "AND scan_type.side = 'none' ",
        "ORDER BY code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL rename_code_tables();
DROP PROCEDURE IF EXISTS rename_code_tables;
