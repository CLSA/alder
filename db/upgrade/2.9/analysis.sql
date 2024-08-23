SELECT "Creating new analysis table" AS ""; 

CREATE TABLE IF NOT EXISTS analysis (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  review_id INT(10) UNSIGNED NOT NULL,
  image_id INT(10) UNSIGNED NOT NULL,
  rating INT(10) NOT NULL DEFAULT 5,
  feedback TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_review_id (review_id ASC),
  INDEX fk_image_id (image_id ASC),
  UNIQUE INDEX uq_review_id_image_id (review_id ASC, image_id ASC),
  CONSTRAINT fk_analysis_review_id
    FOREIGN KEY (review_id)
    REFERENCES review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_image_id
    FOREIGN KEY (image_id)
    REFERENCES image (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
