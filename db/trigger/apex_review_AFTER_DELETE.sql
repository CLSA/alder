CREATE TRIGGER apex_review_AFTER_DELETE AFTER DELETE ON apex_review FOR EACH ROW
BEGIN
  CALL update_exam_effective_apex_review(OLD.exam_id);
END$$