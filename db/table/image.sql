CREATE TABLE image (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  exam_id int(10) unsigned NOT NULL,
  filename varchar(45) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exam_id_path (exam_id,filename),
  KEY fk_exam_id (exam_id),
  CONSTRAINT fk_image_exam_id
    FOREIGN KEY (exam_id)
    REFERENCES exam (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;