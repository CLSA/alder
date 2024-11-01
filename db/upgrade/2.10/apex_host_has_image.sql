SELECT "Creating new apex_host_has_image table" AS "";

CREATE TABLE IF NOT EXISTS apex_host_has_image (
  apex_host_id INT(10) UNSIGNED NOT NULL,
  image_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (apex_host_id, image_id),
  INDEX fk_apex_host_has_image_image_id (image_id ASC),
  INDEX fk_apex_host_has_image_apex_host_id (apex_host_id ASC),
  CONSTRAINT fk_apex_host_has_image_apex_host_id
    FOREIGN KEY (apex_host_id)
    REFERENCES apex_host (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_host_has_image_image_id
    FOREIGN KEY (image_id)
    REFERENCES image (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
