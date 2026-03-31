CREATE TABLE code (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  code_group_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) NOT NULL,
  name VARCHAR(45) NOT NULL,
  value INT(10) NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (id),
  INDEX fk_code_group_id (code_group_id ASC),
  UNIQUE INDEX uq_code_group_id_rank (code_group_id ASC, rank ASC),
  UNIQUE INDEX uq_code_group_id_name (code_group_id ASC, name ASC),
  CONSTRAINT fk_code_code_group_id
    FOREIGN KEY (code_group_id)
    REFERENCES alder.code_group (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
