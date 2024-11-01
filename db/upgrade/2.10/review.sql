DROP PROCEDURE IF EXISTS patch_review;
DELIMITER //
CREATE PROCEDURE patch_review()
  BEGIN

    SELECT "Adding start_datetime column to review table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "review"
    AND column_name = "start_datetime";

    IF @test = 0 THEN
      ALTER TABLE review
      ADD COLUMN start_datetime DATETIME NOT NULL
      AFTER feedback;

      UPDATE review
      SET start_datetime = CONVERT_TZ( create_timestamp, "Canada/Eastern", "UTC" );
    END IF;

    SELECT "Adding end_datetime column to review table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "review"
    AND column_name = "end_datetime";

    IF @test = 0 THEN
      ALTER TABLE review
      ADD COLUMN end_datetime DATETIME NULL DEFAULT NULL
      AFTER start_datetime;

      UPDATE review
      SET end_datetime = CONVERT_TZ( update_timestamp, "Canada/Eastern", "UTC" )
      WHERE completed;
    END IF;

    SELECT "Dropping completed column from review table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "review"
    AND column_name = "completed";

    IF @test = 1 THEN
      ALTER TABLE review DROP COLUMN completed;
    END IF;

  END //
DELIMITER ;

CALL patch_review();
DROP PROCEDURE IF EXISTS patch_review;
