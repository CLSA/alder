-- Patch to upgrade database to version 2.10

SET AUTOCOMMIT=0;

SOURCE code_group.sql
SOURCE code.sql
SOURCE analysis.sql
SOURCE analysis_has_code.sql
SOURCE review.sql
SOURCE setting.sql
SOURCE calculate_rating.sql
SOURCE modality.sql
SOURCE scan_type.sql
SOURCE interview.sql

SOURCE apex_host.sql
SOURCE apex_review.sql
SOURCE apex_analysis.sql
SOURCE apex_analysis_has_code.sql
SOURCE import_salix_data.sql

SOURCE overview.sql
SOURCE application_type_has_overview.sql
SOURCE role_has_overview.sql

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
