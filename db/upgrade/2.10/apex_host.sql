DROP PROCEDURE IF EXISTS patch_apex_host;
DELIMITER //
CREATE PROCEDURE patch_apex_host()
  BEGIN

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

    -- add the initial warbler server if there are no hosts
    SELECT COUNT(*) INTO @test FROM apex_host;
    IF @test = 0 THEN
      INSERT INTO apex_host SET
        name = "Warbler",
        ssh_address = "10.10.255.50",
        ssh_username = "admin",
        db_address = "WARBLER1",
        db_username = "clsamssql";

    END IF;

  END //
DELIMITER ;

CALL patch_apex_host();
DROP PROCEDURE IF EXISTS patch_apex_host;





