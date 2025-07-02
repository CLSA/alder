DROP PROCEDURE IF EXISTS patch_report;
  DELIMITER //
  CREATE PROCEDURE patch_report()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Removing all analysis reports from Salix" AS "";

    SET @sql = CONCAT(
      "DELETE FROM ", @cenozo, ".report ",
      "WHERE report_type_id = ( ",
        "SELECT id FROM ", @cenozo, ".report_type WHERE name = 'analysis' ",
      ") "
      "AND application_id = ( ",
        "SELECT id FROM ", @cenozo, ".application WHERE name = 'salix' ",
      ") "
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report();
DROP PROCEDURE IF EXISTS patch_report;
