CREATE TRIGGER analysis_has_code_AFTER_DELETE AFTER DELETE ON analysis_has_code FOR EACH ROW
BEGIN
  CALL calculate_rating(OLD.analysis_id);
END ;;
