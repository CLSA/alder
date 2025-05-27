SELECT "Creating new selection_option table" AS "";

CREATE TABLE IF NOT EXISTS selection_option (
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
    REFERENCES selection (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SELECT id INTO @selection_id FROM selection WHERE name = "Grading";
INSERT IGNORE INTO selection_option (selection_id, rank, name) VALUES
(@selection_id, 1, "A"),
(@selection_id, 2, "B"),
(@selection_id, 3, "C"),
(@selection_id, 4, "D"),
(@selection_id, 5, "E"),
(@selection_id, 6, "F");

SELECT id INTO @selection_id FROM selection WHERE name = "Best FEV1";
INSERT IGNORE INTO selection_option (selection_id, rank, name) VALUES
(@selection_id, 1, "Trial #1"),
(@selection_id, 2, "Trial #2"),
(@selection_id, 3, "Trial #3"),
(@selection_id, 4, "Trial #4"),
(@selection_id, 5, "Trial #5"),
(@selection_id, 6, "Trial #6"),
(@selection_id, 7, "Trial #7"),
(@selection_id, 8, "Trial #8");

SELECT id INTO @selection_id FROM selection WHERE name = "Best FVC";
INSERT IGNORE INTO selection_option (selection_id, rank, name) VALUES
(@selection_id, 1, "Trial #1"),
(@selection_id, 2, "Trial #2"),
(@selection_id, 3, "Trial #3"),
(@selection_id, 4, "Trial #4"),
(@selection_id, 5, "Trial #5"),
(@selection_id, 6, "Trial #6"),
(@selection_id, 7, "Trial #7"),
(@selection_id, 8, "Trial #8");

SELECT id INTO @selection_id FROM selection WHERE name = "Best PEF";
INSERT IGNORE INTO selection_option (selection_id, rank, name) VALUES
(@selection_id, 1, "Trial #1"),
(@selection_id, 2, "Trial #2"),
(@selection_id, 3, "Trial #3"),
(@selection_id, 4, "Trial #4"),
(@selection_id, 5, "Trial #5"),
(@selection_id, 6, "Trial #6"),
(@selection_id, 7, "Trial #7"),
(@selection_id, 8, "Trial #8");
