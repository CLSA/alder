DROP PROCEDURE IF EXISTS rename_code_tables;
DELIMITER //
CREATE PROCEDURE rename_code_tables()
  BEGIN

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "code"
    AND column_name = "analysis_id";

    IF @test = 1 THEN
      SELECT "Renaming code table to code2" AS "";
      RENAME TABLE code TO code2;

      SELECT "Renaming code_type table to code" AS "";
      RENAME TABLE code_type TO code;

      ALTER TABLE code DROP CONSTRAINT fk_code_type_code_group_id;
      ALTER TABLE code
        ADD CONSTRAINT fk_code_code_group_id
        FOREIGN KEY (code_group_id)
        REFERENCES code_group (id)
        ON DELETE CASCADE ON UPDATE NO ACTION;
    END IF;

  END //
DELIMITER ;

CALL rename_code_tables();
DROP PROCEDURE IF EXISTS rename_code_tables;
