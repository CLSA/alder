DROP PROCEDURE IF EXISTS patch_Rating;
DELIMITER //
CREATE PROCEDURE patch_Rating()
  BEGIN

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "Rating");

    IF @test THEN
      SELECT "Modifying Rating table referential actions" AS "";

      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE Rating DROP FOREIGN KEY fkRatingImageId;

      ALTER TABLE Rating ADD CONSTRAINT fkRatingImageId
        FOREIGN KEY (ImageId) REFERENCES Image (Id)
        ON DELETE NO ACTION ON UPDATE CASCADE;

      ALTER TABLE Rating DROP FOREIGN KEY fkRatingUserId;

      ALTER TABLE Rating ADD CONSTRAINT fkRatingUserId
        FOREIGN KEY (UserId) REFERENCES User (Id)
        ON DELETE NO ACTION ON UPDATE CASCADE;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

CALL patch_Rating();
DROP PROCEDURE IF EXISTS patch_Rating;
