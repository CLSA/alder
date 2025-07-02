DROP PROCEDURE IF EXISTS patch_report_type;
  DELIMITER //
  CREATE PROCEDURE patch_report_type()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Adding new Data Release Update report" AS "";

    SET @sql = CONCAT(
      "UPDATE ", @cenozo, ".report_type SET ",
        "title = 'Analysis Data', ",
        "subject = 'analysis', ",
        "description = 'Provides an export of all analysis data for a given modality and study-phase.' ",
      "Where name = 'analysis'"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_type();
DROP PROCEDURE IF EXISTS patch_report_type;
