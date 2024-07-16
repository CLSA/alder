SELECT "Creating new service table" AS "";

CREATE TABLE IF NOT EXISTS service (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  method ENUM('DELETE', 'GET', 'PATCH', 'POST', 'PUT') NOT NULL,
  subject VARCHAR(45) NOT NULL,
  resource TINYINT(1) NOT NULL DEFAULT 0,
  restricted TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_method_subject_resource (method ASC, subject ASC, resource ASC))
ENGINE = InnoDB;

SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'access', 'DELETE', 1, 1 ),
( 'access', 'GET', 0, 1 ),
( 'access', 'POST', 0, 1 ),
( 'activity', 'GET', 0, 1 ),
( 'application', 'GET', 0, 1 ),
( 'application', 'GET', 1, 0 ),
( 'application', 'PATCH', 1, 1 ),
( 'application_type', 'GET', 0, 0 ),
( 'application_type', 'GET', 1, 0 ),
( 'arrow', 'DELETE', 1, 1 ),
( 'arrow', 'GET', 0, 0 ),
( 'arrow', 'GET', 1, 0 ),
( 'arrow', 'PATCH', 1, 1 ),
( 'arrow', 'POST', 0, 1 ),
( 'code', 'DELETE', 1, 1 ),
( 'code', 'GET', 0, 0 ),
( 'code', 'GET', 1, 0 ),
( 'code', 'POST', 0, 1 ),
( 'code_group', 'DELETE', 1, 1 ),
( 'code_group', 'GET', 0, 0 ),
( 'code_group', 'GET', 1, 0 ),
( 'code_group', 'PATCH', 1, 1 ),
( 'code_group', 'POST', 0, 1 ),
( 'code_type', 'DELETE', 1, 1 ),
( 'code_type', 'GET', 0, 0 ),
( 'code_type', 'GET', 1, 0 ),
( 'code_type', 'PATCH', 1, 1 ),
( 'code_type', 'POST', 0, 1 ),
( 'custom_report', 'DELETE', 1, 1 ),
( 'custom_report', 'GET', 0, 0 ),
( 'custom_report', 'GET', 1, 0 ),
( 'custom_report', 'PATCH', 1, 1 ),
( 'custom_report', 'POST', 0, 1 ),
( 'ellipse', 'DELETE', 1, 1 ),
( 'ellipse', 'GET', 0, 0 ),
( 'ellipse', 'GET', 1, 0 ),
( 'ellipse', 'PATCH', 1, 1 ),
( 'ellipse', 'POST', 0, 1 ),
( 'exam', 'GET', 0, 0 ),
( 'exam', 'GET', 1, 0 ),
( 'exam', 'PATCH', 1, 1 ),
( 'failed_login', 'GET', 0, 1 ),
( 'image', 'GET', 0, 0 ),
( 'image', 'GET', 1, 0 ),
( 'image', 'PATCH', 1, 1 ),
( 'interview', 'GET', 0, 0 ),
( 'interview', 'GET', 1, 0 ),
( 'language', 'GET', 0, 0 ),
( 'language', 'GET', 1, 0 ),
( 'log_entry', 'GET', 0, 1 ),
( 'log_entry', 'GET', 1, 1 ),
( 'modality', 'GET', 0, 0 ),
( 'modality', 'GET', 1, 0 ),
( 'review', 'DELETE', 1, 1 ),
( 'review', 'GET', 0, 0 ),
( 'review', 'GET', 1, 0 ),
( 'review', 'PATCH', 1, 1 ),
( 'review', 'POST', 0, 1 ),
( 'report', 'DELETE', 1, 1 ),
( 'report', 'GET', 0, 1 ),
( 'report', 'GET', 1, 1 ),
( 'report', 'PATCH', 1, 1 ),
( 'report', 'POST', 0, 1 ),
( 'report_restriction', 'GET', 0, 1 ),
( 'report_restriction', 'GET', 1, 1 ),
( 'report_schedule', 'DELETE', 1, 1 ),
( 'report_schedule', 'GET', 0, 1 ),
( 'report_schedule', 'GET', 1, 1 ),
( 'report_schedule', 'PATCH', 1, 1 ),
( 'report_schedule', 'POST', 0, 1 ),
( 'report_type', 'GET', 0, 1 ),
( 'report_type', 'GET', 1, 1 ),
( 'report_type', 'PATCH', 1, 1 ),
( 'role', 'GET', 0, 0 ),
( 'scan_type', 'GET', 0, 0 ),
( 'scan_type', 'GET', 1, 0 ),
( 'scan_type', 'PATCH', 1, 1 ),
( 'self', 'DELETE', 1, 0 ),
( 'self', 'GET', 1, 0 ),
( 'self', 'PATCH', 1, 0 ),
( 'self', 'POST', 1, 0 ),
( 'site', 'GET', 0, 0 ),
( 'site', 'GET', 1, 0 ),
( 'system_message', 'DELETE', 1, 1 ),
( 'system_message', 'GET', 0, 0 ),
( 'system_message', 'GET', 1, 1 ),
( 'system_message', 'PATCH', 1, 1 ),
( 'system_message', 'POST', 0, 1 ),
( 'user', 'DELETE', 1, 1 ),
( 'user', 'GET', 0, 0 ),
( 'user', 'GET', 1, 0 ),
( 'user', 'PATCH', 1, 1 ),
( 'user', 'POST', 0, 1 ),
( 'study_phase', 'GET', 0, 1 ),
( 'study_phase', 'GET', 1, 1 );
