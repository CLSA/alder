CREATE TABLE review (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  exam_id INT(10) UNSIGNED NOT NULL,
  user_id INT(10) UNSIGNED NOT NULL,
  notification ENUM("alert", "read") NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NULL,
  feedback TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_exam_id (exam_id ASC),
  INDEX fk_user_id (user_id ASC),
  UNIQUE INDEX uq_exam_id_user_id (exam_id ASC, user_id ASC),
  CONSTRAINT fk_review_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES alder.exam (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_review_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
