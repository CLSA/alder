CREATE TRIGGER apex_review_AFTER_UPDATE AFTER UPDATE ON apex_review FOR EACH ROW
BEGIN
  CALL update_exam_effective_apex_review(NEW.exam_id);
END ;;