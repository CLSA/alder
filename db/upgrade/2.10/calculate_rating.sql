SELECT "Creating new calculate_rating procedure" AS "";

DELIMITER $$

DROP PROCEDURE IF EXISTS calculate_rating$$
CREATE PROCEDURE calculate_rating (IN proc_analysis_id INT(10) UNSIGNED)
BEGIN
  SELECT IFNULL( SUM(value), 0 ) INTO @cg_rating
  FROM (
    SELECT code_group.value
    FROM analysis_has_code
    JOIN code ON analysis_has_code.code_id = code.id
    JOIN code_group ON code.code_group_id = code_group.id
    WHERE analysis_has_code.analysis_id = proc_analysis_id
    GROUP BY code_group.id
  ) AS temp;

  SELECT IFNULL( SUM(value), 0 ) INTO @ct_rating
  FROM (
    SELECT code.value
    FROM analysis_has_code
    JOIN code ON analysis_has_code.code_id = code.id
    JOIN code_group ON code.code_group_id = code_group.id
    WHERE analysis_has_code.analysis_id = proc_analysis_id
  ) AS temp;

  SET @rating = 5 + @cg_rating + @ct_rating;
  UPDATE analysis
  SET rating = IF( 1 > @rating, 1, IF( 5 < @rating, 5, @rating) )
  WHERE id = proc_analysis_id;
END$$

DELIMITER ;
