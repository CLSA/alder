DROP PROCEDURE IF EXISTS import_salix_data;
DELIMITER //
CREATE PROCEDURE import_salix_data()
  BEGIN

    -- determine the cenozo and salix database names
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );
    SELECT REPLACE( DATABASE(), "_alder", "_salix" ) INTO @salix;

    SELECT COUNT(*) INTO @test FROM apex_review;
    IF @test = 0 THEN
      SELECT "Importing apex_review data from Salix" AS "";

      -- create a temporary column to help with query performance
      ALTER TABLE apex_review
      ADD COLUMN apex_deployment_id INT(10) UNSIGNED NULL DEFAULT NULL;

      SET @sql = CONCAT(
        "INSERT INTO apex_review (apex_deployment_id, exam_id, user_id, start_datetime, end_datetime) ",
        "SELECT ",
          "apex_deployment.id, exam.id, apex_deployment.user_id, apex_deployment.import_datetime, ",
          "IFNULL( apex_deployment.export_datetime, apex_deployment.analysis_datetime ) ",
        "FROM ", @cenozo, ".study ",
        "CROSS JOIN ", @salix, ".apex_deployment ",
        "JOIN ", @salix, ".apex_scan ON apex_deployment.apex_scan_id = apex_scan.id ",
        "JOIN ", @salix, ".scan_type AS apex_scan_type ON apex_scan.scan_type_id = apex_scan_type.id ",
        "JOIN ", @salix, ".apex_exam ON apex_scan.apex_exam_id = apex_exam.id ",
        "JOIN ", @cenozo, ".study_phase ON apex_exam.rank = study_phase.rank AND study_phase.study_id = study.id ",
        "JOIN ", @salix, ".apex_baseline ON apex_exam.apex_baseline_id = apex_baseline.id ",
        "JOIN scan_type ON apex_scan_type.type = scan_type.name AND apex_scan_type.side = scan_type.side ",
        "JOIN interview ",
          "ON apex_baseline.participant_id = interview.participant_id ",
          "AND study_phase.id = interview.study_phase_id ",
        "JOIN exam ON interview.id = exam.interview_id AND scan_type.id = exam.scan_type_id ",
        "WHERE study.name = 'clsa' ",
        "AND apex_deployment.status IN( 'completed', 'exported' ) ",
        "AND apex_deployment.user_id IS NOT NULL ",
        "AND apex_deployment.import_datetime IS NOT NULL"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Importing apex_analysis data from Salix" AS "";

      SET @sql = CONCAT(
        "UPDATE apex_analysis ",
        "JOIN apex_review ON apex_analysis.apex_review_id = apex_review.id ",
        "JOIN ", @salix, ".apex_deployment ON apex_review.apex_deployment_id = apex_deployment.id ",
        "SET ",
          "apex_analysis.download_datetime = apex_deployment.analysis_datetime, ",
          "apex_analysis.pass = apex_deployment.pass, "
          "apex_analysis.note = apex_deployment.note"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Importing selected code data from Salix" AS "";

      SET @sql = CONCAT(
        "INSERT INTO apex_analysis_has_code (apex_analysis_id, code_id, update_timestamp, create_timestamp) ",
        "SELECT apex_analysis.id, code.id, apex_code.update_timestamp, apex_code.create_timestamp ",
        "FROM apex_analysis ",
        "JOIN apex_review ON apex_analysis.apex_review_id = apex_review.id ",
        "JOIN exam ON apex_review.exam_id = exam.id ",
        "JOIN scan_type ON exam.scan_type_id = scan_type.id ",
        "JOIN code_group ON scan_type.id = code_group.scan_type_id AND code_group.apex = true ",
        "JOIN ", @salix, ".apex_deployment ON apex_review.apex_deployment_id = apex_deployment.id ",
        "JOIN ", @salix, ".code AS apex_code ON apex_deployment.id = apex_code.apex_deployment_id ",
        "JOIN ", @salix, ".code_type ON apex_code.code_type_id = code_type.id ",
        "JOIN code ON code_group.id = code.code_group_id AND code.name = code_type.code"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- remove temporary column
      ALTER TABLE apex_review DROP COLUMN apex_deployment_id;

      -- remove category names from code names
      UPDATE code
      SET name = SUBSTR(
        name,
        LOCATE("(", name)+1,
        CHAR_LENGTH(name) - LOCATE("(", name) - 1
      )
      WHERE name LIKE "%(%)";

      -- shorten code names
      UPDATE code SET name = "high Z/T" WHERE name = "high Z/T score";
      UPDATE code SET name = "left half" WHERE name = "left half body";
      UPDATE code SET name = "right half" WHERE name = "right half body";

      -- reorder ROI codes
      UPDATE code SET rank = 105 WHERE name = "oversized";
      UPDATE code SET rank = 106 WHERE name = "undersized";
      UPDATE code SET rank = 4 WHERE name = "right" and rank = 5;
      UPDATE code SET rank = rank-100 WHERE rank > 100;

    END IF;

  END //
DELIMITER ;

CALL import_salix_data();
DROP PROCEDURE IF EXISTS import_salix_data;
