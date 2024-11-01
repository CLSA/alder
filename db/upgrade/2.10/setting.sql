DROP PROCEDURE IF EXISTS patch_review;
DELIMITER //
CREATE PROCEDURE patch_review()
  BEGIN

    SELECT "Dropping priority_apex_host_id column from setting table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "review"
    AND column_name = "priority_apex_host_id";

    IF @test THEN
      ALTER TABLE review DROP COLUMN priority_apex_host_id;
    END IF;

  END //
DELIMITER ;

CALL patch_review();
DROP PROCEDURE IF EXISTS patch_review;
