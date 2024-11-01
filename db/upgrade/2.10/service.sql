DROP PROCEDURE IF EXISTS convert_services;
DELIMITER //
CREATE PROCEDURE convert_services()
  BEGIN

    SELECT COUNT(*) INTO @test FROM service WHERE subject = "code_type"; 
    IF @test > 1 THEN
      DELETE FROM service WHERE subject = "code";
      UPDATE service SET subject = "code" WHERE subject = "code_type";
    END IF;

  END //
DELIMITER ;

CALL convert_services();
DROP PROCEDURE IF EXISTS convert_services;

SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'apex_analysis', 'GET', 0, 0 ),
( 'apex_analysis', 'GET', 1, 0 ),
( 'apex_analysis', 'PATCH', 1, 1 ),
( 'apex_code', 'DELETE', 1, 1 ),
( 'apex_code', 'GET', 0, 0 ),
( 'apex_code', 'GET', 1, 0 ),
( 'apex_code', 'PATCH', 1, 1 ),
( 'apex_code', 'POST', 0, 1 ),
( 'apex_host', 'DELETE', 1, 1 ),
( 'apex_host', 'GET', 0, 0 ),
( 'apex_host', 'GET', 1, 0 ),
( 'apex_host', 'PATCH', 1, 1 ),
( 'apex_host', 'POST', 0, 1 ),
( 'apex_review', 'DELETE', 1, 1 ),
( 'apex_review', 'GET', 0, 0 ),
( 'apex_review', 'GET', 1, 0 ),
( 'apex_review', 'PATCH', 1, 1 ),
( 'apex_review', 'POST', 0, 1 );
