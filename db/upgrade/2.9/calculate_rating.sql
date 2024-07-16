SELECT "Creating new calculate_rating procedure" AS "";

DELIMITER $$

DROP PROCEDURE IF EXISTS calculate_rating;
CREATE DEFINER=CURRENT_USER PROCEDURE calculate_rating(IN proc_review_id INT(10) UNSIGNED)
BEGIN
  SELECT 5 + IFNULL(SUM(value), 0) INTO @rating
  FROM (
    SELECT code_group.value
    FROM code
    JOIN code_type ON code.code_type_id = code_type.id
    JOIN code_group ON code_type.code_group_id = code_group.id
    WHERE code.review_id = proc_review_id
    GROUP BY code_group.id
  ) AS temp;
  UPDATE review SET calculated_rating = IF( 1 > @rating, 1, IF( 5 < @rating, 5, @rating) );
END$$

DELIMITER ;
