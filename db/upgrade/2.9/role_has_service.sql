DROP PROCEDURE IF EXISTS patch_role_has_service;
DELIMITER //
CREATE PROCEDURE patch_role_has_service()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new role_has_service table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS role_has_service ( ",
        "role_id INT(10) UNSIGNED NOT NULL, ",
        "service_id INT(10) UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
        "create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "PRIMARY KEY (role_id, service_id), ",
        "INDEX fk_role_id (role_id ASC), ",
        "INDEX fk_service_id (service_id ASC), ",
        "CONSTRAINT fk_role_has_service_role_id ",
          "FOREIGN KEY (role_id) ",
          "REFERENCES ", @cenozo, ".role (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION, ",
        "CONSTRAINT fk_role_has_service_service_id ",
          "FOREIGN KEY (service_id) ",
          "REFERENCES service (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SELECT "Adding services to roles" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'administrator' ",
      "AND service.subject NOT IN ('arrow', 'ellipse') ",
      "AND service.restricted = 1"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO role_has_service( role_id, service_id ) ",
      "SELECT role.id, service.id ",
      "FROM ", @cenozo, ".role, service ",
      "WHERE role.name = 'typist' ",
      "AND service.subject IN ('arrow', 'ellipse') ",
      "AND service.restricted = 1"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_role_has_service();
DROP PROCEDURE IF EXISTS patch_role_has_service;
