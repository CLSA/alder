CREATE TABLE code (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  code_group_id int(10) unsigned NOT NULL,
  rank int(10) NOT NULL,
  name varchar(45) NOT NULL,
  value int(10) NOT NULL DEFAULT 0,
  description text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_code_group_id_rank (code_group_id,rank),
  UNIQUE KEY uq_code_group_id_name (code_group_id,name),
  KEY fk_code_group_id (code_group_id),
  CONSTRAINT fk_code_code_group_id
    FOREIGN KEY (code_group_id)
    REFERENCES code_group (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;