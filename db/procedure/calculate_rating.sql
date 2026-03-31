CREATE PROCEDURE calculate_rating (IN proc_analysis_id INT(10) UNSIGNED)
BEGIN
  SELECT IFNULL(SUM(value), 0) INTO @cg_rating
  FROM (
    SELECT code_group.value
    FROM analysis_has_code
    JOIN code ON analysis_has_code.code_id = code.id
    JOIN code_group ON code.code_group_id = code_group.id
    WHERE analysis_has_code.analysis_id = proc_analysis_id
    GROUP BY code_group.id
  ) AS temp;

  SELECT IFNULL(SUM(value), 0) INTO @ct_rating
  FROM (
    SELECT code.value
    FROM analysis_has_code
    JOIN code ON analysis_has_code.code_id = code.id
    JOIN code_group ON code.code_group_id = code_group.id
    WHERE analysis_has_code.analysis_id = proc_analysis_id
  ) AS temp;

  SELECT COUNT(*) INTO @not_usable_codes
  FROM analysis_has_code
  JOIN code ON analysis_has_code.code_id = code.id
  WHERE analysis_has_code.analysis_id = proc_analysis_id
  AND code.name IN ("NO", "NA", "NI");

  SELECT COUNT(*) INTO @re_analysable_codes
  FROM analysis_has_code
  JOIN code ON analysis_has_code.code_id = code.id
  WHERE analysis_has_code.analysis_id = proc_analysis_id
  AND code.name IN ("SB", "ME", "LO");

  SET @rating = 5 + @cg_rating + @ct_rating;
  SET @quality = IF(@not_usable_codes > 0, "Not Usable", (IF(@re_analysable_codes > 0, "Re-analysable", "Good")));

  UPDATE analysis
  SET rating = IF(1 > @rating, 1, IF(5 < @rating, 5, @rating)), quality = @quality
  WHERE id = proc_analysis_id;
END$$