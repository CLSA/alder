CREATE TABLE analysis (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  review_id int(10) unsigned NOT NULL,
  image_id int(10) unsigned NOT NULL,
  rating int(10) NOT NULL DEFAULT 5,
  quality enum('Good','Re-analysable','Not Usable') NOT NULL DEFAULT 'Good',
  note text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_id_image_id (review_id,image_id),
  KEY fk_review_id (review_id),
  KEY fk_image_id (image_id),
  CONSTRAINT fk_analysis_image_id
    FOREIGN KEY (image_id)
    REFERENCES image (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_analysis_review_id
    FOREIGN KEY (review_id)
    REFERENCES review (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
