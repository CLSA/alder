CREATE TRIGGER analysis_has_code_AFTER_INSERT AFTER INSERT ON analysis_has_code FOR EACH ROW
BEGIN
  CALL calculate_rating(NEW.analysis_id);
END$$