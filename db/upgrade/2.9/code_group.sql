SELECT "Creating new code_group table" AS "";

CREATE TABLE IF NOT EXISTS code_group (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  name VARCHAR(45) NOT NULL,
  value INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_name (name ASC))
ENGINE = InnoDB;

INSERT IGNORE INTO code_group( name, value ) VALUES
("Analysis 1", -1),
("Analysis 2", -1),
("Analysis 3", -1),
("Analysis 4", -1),
("Angulation", -1),
("Position 1", -1),
("Position 2", -1),
("Position 3", -1),
("Position 4", -1);
