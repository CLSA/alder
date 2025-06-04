DROP PROCEDURE IF EXISTS exam_effective_apex_review;
DELIMITER //
CREATE PROCEDURE exam_effective_apex_review()
  BEGIN

    SELECT COUNT(*) INTO @test FROM exam_effective_apex_review;

    IF @test = 0 THEN
      SELECT "Populating exam_effective_apex_review caching table" AS "";

      INSERT INTO exam_effective_apex_review (exam_id, apex_review_id)
      SELECT exam.id, MIN(apex_review.id)
      FROM exam
      JOIN scan_type ON exam.scan_type_id = scan_type.id
      JOIN modality ON scan_type.modality_id = modality.id
      LEFT JOIN apex_review ON exam.id = apex_review.exam_id
      WHERE modality.name = "dxa"
      GROUP BY exam.id;
    END IF;

  END //
DELIMITER ;

SELECT "Creating new exam_effective_apex_review table" AS "";

CREATE TABLE IF NOT EXISTS exam_effective_apex_review (
  exam_id INT(10) UNSIGNED NOT NULL,
  apex_review_id INT(10) UNSIGNED NULL DEFAULT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (exam_id),
  INDEX fk_exam_id (exam_id ASC),
  INDEX fk_apex_review_id (apex_review_id ASC),
  CONSTRAINT fk_exam_effective_apex_review_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES exam (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_exam_effective_apex_review_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES apex_review (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CALL exam_effective_apex_review();
DROP PROCEDURE IF EXISTS exam_effective_apex_review;
