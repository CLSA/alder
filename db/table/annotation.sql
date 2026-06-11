CREATE TABLE annotation (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  analysis_id int(10) unsigned NOT NULL,
  type enum('arrow','ellipse') NOT NULL,
  x0 float NOT NULL,
  y0 float NOT NULL,
  x1 float NOT NULL,
  y1 float NOT NULL,
  PRIMARY KEY (id),
  KEY fk_analysis_id (analysis_id),
  CONSTRAINT fk_annotation_analysis_id
    FOREIGN KEY (analysis_id)
    REFERENCES analysis (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;