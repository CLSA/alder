CREATE TABLE apex_host (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  user_id INT(10) UNSIGNED NULL DEFAULT NULL,
  ssh_address VARCHAR(255) NOT NULL,
  ssh_username VARCHAR(45) NOT NULL,
  db_address VARCHAR(255) NOT NULL,
  db_username VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_ssh_address (ssh_address ASC),
  UNIQUE INDEX uq_db_address (db_address ASC),
  INDEX fk_user_id (user_id ASC),
  UNIQUE INDEX uq_user_id (user_id ASC),
  CONSTRAINT fk_apex_host_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
