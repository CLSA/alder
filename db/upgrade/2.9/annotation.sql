SELECT "Creating new annotation table" AS "";

CREATE TABLE IF NOT EXISTS annotation (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  analysis_id INT(10) UNSIGNED NOT NULL,
  type ENUM("arrow", "ellipse") NOT NULL,
  x0 FLOAT NOT NULL,
  y0 FLOAT NOT NULL,
  x1 FLOAT NOT NULL,
  y1 FLOAT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_analysis_id (analysis_id ASC),
  CONSTRAINT fk_annotation_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
