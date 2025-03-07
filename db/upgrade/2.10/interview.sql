DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Adding alcohol column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "alcohol";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN alcohol TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding secondary_osteoporosis column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "secondary_osteoporosis";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN secondary_osteoporosis TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding rheumatoid_arthritis column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "rheumatoid_arthritis";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN rheumatoid_arthritis TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding glucocorticoid column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "glucocorticoid";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN glucocorticoid TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding current_smoker column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "current_smoker";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN current_smoker TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding parent_hip_fracture column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "parent_hip_fracture";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN parent_hip_fracture TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

    SELECT "Adding previous_fracture column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "previous_fracture";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN previous_fracture TINYINT(1) NULL DEFAULT NULL AFTER token;
    END IF;

  END //
DELIMITER ;

CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
