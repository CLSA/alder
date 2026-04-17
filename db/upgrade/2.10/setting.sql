DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN

    SELECT "Dropping priority_apex_host_id column from setting table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "setting"
    AND column_name = "priority_apex_host_id";

    IF @test THEN
      ALTER TABLE setting DROP COLUMN priority_apex_host_id;
    END IF;

  END //
DELIMITER ;

CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
