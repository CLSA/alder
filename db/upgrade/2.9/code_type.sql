SELECT "Creating new code_type table" AS "";

CREATE TABLE IF NOT EXISTS code_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  code_group_id INT(10) UNSIGNED NULL DEFAULT NULL,
  name VARCHAR(45) NOT NULL DEFAULT 0,
  value INT(10) NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_code_group_id (code_group_id ASC),
  UNIQUE INDEX uq_code_group_id_name (code_group_id ASC, name ASC),
  CONSTRAINT fk_code_type_code_group_id
    FOREIGN KEY (code_group_id)
    REFERENCES code_group (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

TODO ...

INSERT IGNORE INTO code_type( code_group_id, name, value ) VALUES
( ...
