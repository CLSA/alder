SELECT "Adding new Apex code groups" AS "";

INSERT IGNORE INTO code_group (scan_type_id, rank, name, value, description)
SELECT scan_type.id, temp.rank+1, "Apex", 0, "Codes used for during Apex re-analysis."
FROM scan_type
JOIN (
  SELECT scan_type_id, MAX(rank) AS rank
  FROM code_group
  GROUP BY scan_type_id
) AS temp ON scan_type.id = temp.scan_type_id
WHERE scan_type.name IN ("forearm", "hip", "lateral", "spine", "wbody");
