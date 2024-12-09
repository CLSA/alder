DROP PROCEDURE IF EXISTS patch_analysis;
DELIMITER //
CREATE PROCEDURE patch_analysis()
  BEGIN

    SELECT "Adding note column to analysis table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "analysis"
    AND column_name = "note";

    IF @test = 0 THEN
      ALTER TABLE analysis ADD COLUMN note TEXT DEFAULT NULL AFTER rating;
      ALTER TABLE analysis ADD COLUMN quality ENUM("Good", "Re-analysable", "Not Usable")
      NOT NULL DEFAULT "Good" AFTER rating;
    END IF;

  END //
DELIMITER ;

CALL patch_analysis();
DROP PROCEDURE IF EXISTS patch_analysis;
