-- Patch to upgrade database to version 2.9

SET AUTOCOMMIT=0;

SOURCE access.sql
SOURCE custom_report.sql
SOURCE role_has_custom_report.sql
SOURCE setting.sql
SOURCE writelog.sql
SOURCE modality.sql
SOURCE user_has_modality.sql
SOURCE scan_type.sql
SOURCE interview.sql
SOURCE exam.sql
SOURCE image.sql
SOURCE code_group.sql
SOURCE code_type.sql
SOURCE code.sql
SOURCE rating.sql
SOURCE arrow.sql
SOURCE ellipse.sql

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
