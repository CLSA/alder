CREATE TABLE selection (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  scan_type_id int(10) unsigned NOT NULL,
  apex tinyint(1) NOT NULL DEFAULT 0,
  rank int(10) NOT NULL,
  name varchar(45) NOT NULL,
  description text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_scan_type_id_apex_rank (scan_type_id,apex,rank),
  UNIQUE KEY uq_scan_type_id_apex_name (scan_type_id,apex,name),
  KEY fk_scan_type_id (scan_type_id),
  CONSTRAINT fk_selection_scan_type_id
    FOREIGN KEY (scan_type_id)
    REFERENCES scan_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;