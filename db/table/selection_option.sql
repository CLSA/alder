CREATE TABLE selection_option (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  selection_id int(10) unsigned NOT NULL,
  rank int(10) NOT NULL,
  name varchar(45) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_selection_id_rank (selection_id,rank),
  UNIQUE KEY uq_selection_id_name (selection_id,name),
  KEY fk_selection_id (selection_id),
  CONSTRAINT fk_selection_option_selection_id
    FOREIGN KEY (selection_id)
    REFERENCES selection (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;