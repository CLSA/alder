DROP PROCEDURE IF EXISTS apex_review_effective_apex_analysis;
DELIMITER //
CREATE PROCEDURE apex_review_effective_apex_analysis()
  BEGIN

    SELECT COUNT(*) INTO @test FROM apex_review_effective_apex_analysis;

    IF @test = 0 THEN
      SELECT "Populating apex_review_effective_apex_analysis caching table" AS "";

      INSERT INTO apex_review_effective_apex_analysis (apex_review_id, apex_analysis_id)
      SELECT apex_review.id, MIN(apex_analysis.id)
      FROM apex_review
      JOIN apex_analysis ON apex_review.id = apex_analysis.apex_review_id
      GROUP BY apex_review.id;
    END IF;

  END //
DELIMITER ;

SELECT "Adding new apex_review_effective_apex_analysis table" AS "";

CREATE TABLE IF NOT EXISTS apex_review_effective_apex_analysis (
  apex_review_id INT(10) UNSIGNED NOT NULL,
  apex_analysis_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  INDEX fk_apex_review_id (apex_review_id ASC),
  INDEX fk_apex_analysis_id (apex_analysis_id ASC),
  PRIMARY KEY (apex_review_id, apex_analysis_id),
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES apex_review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_review_effective_apex_analysis_apex_analysis_id
    FOREIGN KEY (apex_analysis_id)
    REFERENCES apex_analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CALL apex_review_effective_apex_analysis();
DROP PROCEDURE IF EXISTS apex_review_effective_apex_analysis;
