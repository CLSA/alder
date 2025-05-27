DROP PROCEDURE IF EXISTS patch_apex_user;
DELIMITER //
CREATE PROCEDURE patch_apex_user()
  BEGIN

    -- determine the cenozo database name
    SELECT unique_constraint_schema INTO @cenozo
    FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE()
    AND constraint_name = "fk_access_site_id";

    SELECT "Creating new apex_user table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS apex_user ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_user_id (user_id ASC), ",
        "UNIQUE INDEX uq_user_id (user_id ASC), ",
        "CONSTRAINT fk_apex_user_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SELECT COUNT(*) INTO @test FROM apex_user;

    IF @test = 0 THEN
      -- pre-populate the table
      SET @sql = CONCAT(
        "INSERT INTO apex_user (user_id) ",
        "SELECT user.id ",
        "FROM ", @cenozo, ".user ",
        "WHERE name IN ( ",
          "'afcam', 'becam', 'cfarr', 'dean', 'djcam', 'dscam', 'efcam', 'gordonch', 'gordonr', ",
          "'gregory_g', 'lmurray', 'rpcam', 'saab', 'wasyleczkos' ",
        ")"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

CALL patch_apex_user();
DROP PROCEDURE IF EXISTS patch_apex_user;
