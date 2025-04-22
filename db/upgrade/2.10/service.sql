DROP PROCEDURE IF EXISTS convert_services;
DELIMITER //
CREATE PROCEDURE convert_services()
  BEGIN

    SELECT COUNT(*) INTO @test FROM service WHERE subject = "code_type"; 
    IF @test > 1 THEN
      DELETE FROM service WHERE subject = "code";
      UPDATE service SET subject = "code" WHERE subject = "code_type";
      UPDATE service SET restricted = 0 WHERE subject = "code" AND method = "GET";
    END IF;

  END //
DELIMITER ;

CALL convert_services();
DROP PROCEDURE IF EXISTS convert_services;

SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'analysis_selection', 'DELETE', 1, 1 ),
( 'analysis_selection', 'GET', 0, 0 ),
( 'analysis_selection', 'GET', 1, 0 ),
( 'analysis_selection', 'PATCH', 1, 1 ),
( 'analysis_selection', 'POST', 0, 1 ),
( 'apex_analysis', 'GET', 0, 0 ),
( 'apex_analysis', 'GET', 1, 0 ),
( 'apex_analysis', 'PATCH', 1, 1 ),
( 'apex_analysis_selection', 'DELETE', 1, 1 ),
( 'apex_analysis_selection', 'GET', 0, 0 ),
( 'apex_analysis_selection', 'GET', 1, 0 ),
( 'apex_analysis_selection', 'PATCH', 1, 1 ),
( 'apex_analysis_selection', 'POST', 0, 1 ),
( 'apex_host', 'DELETE', 1, 1 ),
( 'apex_host', 'GET', 0, 0 ),
( 'apex_host', 'GET', 1, 0 ),
( 'apex_host', 'PATCH', 1, 1 ),
( 'apex_host', 'POST', 0, 1 ),
( 'apex_review', 'DELETE', 1, 1 ),
( 'apex_review', 'GET', 0, 0 ),
( 'apex_review', 'GET', 1, 0 ),
( 'apex_review', 'PATCH', 1, 1 ),
( 'apex_review', 'POST', 0, 1 ),
( 'overview', 'GET', 0, 0 ),
( 'overview', 'GET', 1, 0 ),
( 'selection', 'DELETE', 1, 1 ),
( 'selection', 'GET', 0, 1 ),
( 'selection', 'GET', 1, 1 ),
( 'selection', 'PATCH', 1, 1 ),
( 'selection', 'POST', 0, 1 ),
( 'selection_option', 'DELETE', 1, 1 ),
( 'selection_option', 'GET', 0, 1 ),
( 'selection_option', 'GET', 1, 1 ),
( 'selection_option', 'PATCH', 1, 1 ),
( 'selection_option', 'POST', 0, 1 ),
( 'user_ip_address', 'GET', 0, 0 ),
( 'user_ip_address', 'GET', 1, 0 );
