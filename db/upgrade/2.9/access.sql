DROP PROCEDURE IF EXISTS patch_access;
DELIMITER //
CREATE PROCEDURE patch_access()
  BEGIN

    -- determine the cenozo database name (can't use fk_access_site_id since we'll be creating it)
    SELECT REPLACE( DATABASE(), "_alder", "_cenozo" ) INTO @cenozo;

    SELECT "Creating new access table" AS ""; 

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS access ( ",
        "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "user_id INT(10) UNSIGNED NOT NULL, ",
        "role_id INT(10) UNSIGNED NOT NULL, ",
        "site_id INT(10) UNSIGNED NOT NULL, ",
        "datetime DATETIME NULL DEFAULT NULL, ",
        "microtime DOUBLE NULL DEFAULT NULL, ",
        "PRIMARY KEY (id), ",
        "UNIQUE INDEX uq_user_id_role_id_site_id (user_id ASC, role_id ASC, site_id ASC), ",
        "INDEX fk_user_id (user_id ASC), ",
        "INDEX fk_role_id (role_id ASC), ",
        "INDEX fk_site_id (site_id ASC), ",
        "INDEX datetime_microtime (datetime ASC, microtime ASC), ",
        "CONSTRAINT fk_access_role_id ",
          "FOREIGN KEY (role_id) ",
          "REFERENCES ", @cenozo, ".role (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE, ",
        "CONSTRAINT fk_access_site_id ",
          "FOREIGN KEY (site_id) ",
          "REFERENCES ", @cenozo, ".site (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE, ",
        "CONSTRAINT fk_access_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_access();
DROP PROCEDURE IF EXISTS patch_access;
