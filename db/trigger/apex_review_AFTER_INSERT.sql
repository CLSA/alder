CREATE TRIGGER apex_review_AFTER_INSERT AFTER INSERT ON apex_review FOR EACH ROW
BEGIN
  INSERT INTO apex_analysis (apex_review_id, image_id)
  SELECT NEW.id, image.id
  FROM image
  WHERE exam_id = NEW.exam_id
  AND image.filename != "dxa_wbody_bca.dcm";

  CALL update_exam_effective_apex_review(NEW.exam_id);
END ;;