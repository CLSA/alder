CREATE TRIGGER apex_analysis_AFTER_DELETE AFTER DELETE ON apex_analysis FOR EACH ROW
BEGIN
  CALL update_apex_review_effective_apex_analysis(OLD.apex_review_id);
END ;;
