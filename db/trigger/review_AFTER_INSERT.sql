CREATE TRIGGER review_AFTER_INSERT AFTER INSERT ON review FOR EACH ROW
BEGIN
  INSERT INTO analysis (review_id, image_id)
  SELECT NEW.id, image.id FROM image WHERE exam_id = NEW.exam_id;
END ;;
