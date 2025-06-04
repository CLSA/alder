DROP PROCEDURE IF EXISTS patch_apex_host;
DELIMITER //
CREATE PROCEDURE patch_apex_host()
  BEGIN

    -- determine the cenozo database name
    SELECT unique_constraint_schema INTO @cenozo
    FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE()
    AND constraint_name = "fk_access_site_id";

    SELECT "Creating new apex_host table";

    CREATE TABLE IF NOT EXISTS apex_host (
      id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
      create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
      name VARCHAR(45) NOT NULL,
      ssh_address VARCHAR(255) NOT NULL,
      ssh_username VARCHAR(45) NOT NULL,
      db_address VARCHAR(255) NOT NULL,
      db_username VARCHAR(45) NOT NULL,
      PRIMARY KEY (id),
      UNIQUE INDEX uq_name (name ASC),
      UNIQUE INDEX uq_ssh_address (ssh_address ASC),
      UNIQUE INDEX uq_db_address (db_address ASC))
    ENGINE = InnoDB;

    -- add the new user_id column
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "apex_host"
    AND column_name = "user_id";

    IF @test = 0 THEN
      ALTER TABLE apex_host
      ADD COLUMN user_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER create_timestamp,
      ADD INDEX fk_user_id (user_id),
      ADD UNIQUE INDEX uq_user_id (user_id);

      SET @sql = CONCAT(
        "ALTER TABLE apex_host ",
        "ADD CONSTRAINT fk_apex_host_user_id ",
        "FOREIGN KEY (user_id) ",
        "REFERENCES ", @cenozo, ".user (id) ",
        "ON DELETE NO ACTION ",
        "ON UPDATE NO ACTION"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    -- remove the old name column
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "apex_host"
    AND column_name = "name";

    IF @test = 1 THEN
      ALTER TABLE apex_host
      DROP KEY uq_name,
      DROP COLUMN name;
    END IF;

  END //
DELIMITER ;

CALL patch_apex_host();
DROP PROCEDURE IF EXISTS patch_apex_host;
