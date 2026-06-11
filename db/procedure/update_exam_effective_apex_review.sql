CREATE PROCEDURE update_exam_effective_apex_review(IN proc_exam_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO exam_effective_apex_review (exam_id, apex_review_id)
  SELECT exam.id, apex_review.id
  FROM exam
  LEFT JOIN apex_review ON exam.id = apex_review.exam_id
  WHERE exam.id = proc_exam_id
  ORDER BY apex_review.end_datetime DESC, apex_review.start_datetime DESC
  LIMIT 1;
END ;;
