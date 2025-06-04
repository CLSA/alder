SELECT "Creating new update_apex_review_effective_apex_analysis procedure" AS "";

DELIMITER $$

DROP PROCEDURE IF EXISTS update_apex_review_effective_apex_analysis;
CREATE PROCEDURE update_apex_review_effective_apex_analysis (IN proc_apex_review_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO apex_review_effective_apex_analysis (apex_review_id, apex_analysis_id)
  SELECT apex_review_id, id
  FROM apex_analysis
  WHERE apex_review_id = proc_apex_review_id
  ORDER BY download_datetime DESC, upload_datetime DESC
  LIMIT 1;
END$$

DELIMITER ;
