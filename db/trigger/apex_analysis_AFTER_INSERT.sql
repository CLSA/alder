CREATE TRIGGER apex_analysis_AFTER_INSERT AFTER INSERT ON apex_analysis FOR EACH ROW
BEGIN
  CALL update_apex_review_effective_apex_analysis(NEW.apex_review_id);
END$$