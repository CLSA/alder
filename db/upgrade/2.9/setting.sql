DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );  

    SELECT "Creating new setting table" AS ""; 

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS setting ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "site_id INT(10) UNSIGNED NOT NULL, ",
        "priority_apex_host_id INT(10) UNSIGNED NOT NULL, ",
        "PRIMARY KEY (id), ",
        "UNIQUE INDEX uq_site_id (site_id ASC), ",
        "INDEX fk_site_id (site_id ASC), ",
        "CONSTRAINT fk_setting_site_id ",
          "FOREIGN KEY (site_id) ",
          "REFERENCES ", @cenozo, ".site (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
