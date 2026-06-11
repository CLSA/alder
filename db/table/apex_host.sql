CREATE TABLE apex_host (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  user_id int(10) unsigned DEFAULT NULL,
  ssh_address varchar(255) NOT NULL,
  ssh_username varchar(45) NOT NULL,
  db_address varchar(255) NOT NULL,
  db_username varchar(45) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ssh_address (ssh_address),
  UNIQUE KEY uq_db_address (db_address),
  UNIQUE KEY uq_user_id (user_id),
  KEY fk_user_id (user_id),
  CONSTRAINT fk_apex_host_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;