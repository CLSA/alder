CREATE TABLE apex_analysis (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  apex_review_id INT(10) UNSIGNED NOT NULL,
  image_id INT(10) UNSIGNED NOT NULL,
  pass TINYINT(1) NULL DEFAULT NULL,
  pfile_name VARCHAR(15) NULL DEFAULT NULL,
  upload_status VARCHAR(255) NULL DEFAULT NULL,
  upload_datetime DATETIME NULL DEFAULT NULL,
  download_datetime DATETIME NULL DEFAULT NULL,
  data LONGTEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NOT NULL DEFAULT 'null',
  note TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_image_id (image_id ASC),
  UNIQUE INDEX uq_apex_review_id_image_id (apex_review_id ASC, image_id ASC),
  CONSTRAINT fk_apex_analysis_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES alder.apex_review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_image_id
    FOREIGN KEY (image_id)
    REFERENCES alder.image (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
