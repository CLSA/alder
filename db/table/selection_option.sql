CREATE TABLE selection_option (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  selection_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) NOT NULL,
  name VARCHAR(45) NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_selection_id (selection_id ASC),
  UNIQUE INDEX uq_selection_id_rank (selection_id ASC, rank ASC),
  UNIQUE INDEX uq_selection_id_name (selection_id ASC, name ASC),
  CONSTRAINT fk_selection_option_selection_id
    FOREIGN KEY (selection_id)
    REFERENCES alder.selection (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
