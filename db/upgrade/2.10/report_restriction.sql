DROP PROCEDURE IF EXISTS patch_report_restriction;
  DELIMITER //
  CREATE PROCEDURE patch_report_restriction()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Replacing records in report_restriction table" AS "";

    SET @sql = CONCAT(
      "SELECT COUNT(*) INTO @count ",
      "FROM ", @cenozo, ".report_restriction ",
      "WHERE report_type_id = ( ",
        "SELECT id FROM ", @cenozo, ".report_type WHERE name = 'analysis' ",
      ") "
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    IF @count = 4 THEN
      SET @sql = CONCAT(
        "DELETE FROM ", @cenozo, ".report_restriction ",
        "WHERE report_type_id = ( ",
          "SELECT id FROM ", @cenozo, ".report_type WHERE name = 'analysis' ",
        ") "
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, restriction_type, custom, subject, enum_list, description ) ",
      'SELECT report_type.id, 1, "scan_type", "Scan Type", 1, "enum", 1, "scan_type", ',
        '\'"DXA forearm","DXA hip","DXA lateral","DXA spine","DXA wbody","Retinal","Carotid Intima","Spirometry"\', ',
        '"Defines which scan type to include in the generated report" ',
      "FROM ", @cenozo, ".report_type ",
      "WHERE report_type.name = 'analysis'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, restriction_type, custom, subject, enum_list, description ) ",
      "SELECT report_type.id, 2, 'study_phase', 'Study Phase', 1, 'enum', 1, 'study_phase', ",
        '\'"Baseline","Follow-up 1","Follow-up 2","Follow-up 3","Follow-up 4"\', ',
        '"Defines which study-phase to include in the generated report" ',
      "FROM ", @cenozo, ".report_type ",
      "WHERE report_type.name = 'analysis'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_restriction();
DROP PROCEDURE IF EXISTS patch_report_restriction;
