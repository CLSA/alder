SELECT "Creating new update_apex_review_effective_apex_analysis procedure" AS "";

DELIMITER $$

DROP PROCEDURE IF EXISTS update_exam_effective_apex_review;
CREATE PROCEDURE update_exam_effective_apex_review (IN proc_exam_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO exam_effective_apex_review (exam_id, apex_review_id)
  SELECT exam_id, id
  FROM apex_review
  WHERE exam_id = proc_exam_id
  ORDER BY end_datetime DESC, start_datetime DESC
  LIMIT 1;
END$$

DELIMITER ;
