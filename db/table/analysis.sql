CREATE TABLE analysis (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  review_id INT(10) UNSIGNED NOT NULL,
  image_id INT(10) UNSIGNED NOT NULL,
  rating INT(10) NOT NULL DEFAULT 5,
  quality ENUM("Good", "Re-analysable", "Not Usable") NOT NULL DEFAULT 'Good',
  note TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_review_id (review_id ASC),
  INDEX fk_image_id (image_id ASC),
  UNIQUE INDEX uq_review_id_image_id (review_id ASC, image_id ASC),
  CONSTRAINT fk_analysis_review_id
    FOREIGN KEY (review_id)
    REFERENCES alder.review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_image_id
    FOREIGN KEY (image_id)
    REFERENCES alder.image (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
