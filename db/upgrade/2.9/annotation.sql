SELECT "Creating new annotation table" AS "";

CREATE TABLE IF NOT EXISTS annotation (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  review_id INT(10) UNSIGNED NOT NULL,
  type ENUM("arrow", "ellipse") NOT NULL,
  x0 FLOAT NOT NULL,
  y0 FLOAT NOT NULL,
  x1 FLOAT NOT NULL,
  y1 FLOAT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_review_id (review_id ASC),
  CONSTRAINT fk_annotation_review_id
    FOREIGN KEY (review_id)
    REFERENCES review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
