CREATE TABLE apex_analysis (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  apex_review_id int(10) unsigned NOT NULL,
  image_id int(10) unsigned NOT NULL,
  pass tinyint(1) DEFAULT NULL,
  pfile_name varchar(15) DEFAULT NULL,
  upload_status varchar(255) DEFAULT NULL,
  upload_datetime datetime DEFAULT NULL,
  download_datetime datetime DEFAULT NULL,
  data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'null',
  note text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_apex_review_id_image_id (apex_review_id,image_id),
  KEY fk_image_id (image_id),
  CONSTRAINT fk_apex_analysis_apex_review_id
    FOREIGN KEY (apex_review_id)
    REFERENCES apex_review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_apex_analysis_image_id
    FOREIGN KEY (image_id)
    REFERENCES image (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
