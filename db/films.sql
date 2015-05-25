SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `film_formats`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `film_formats` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `films`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `films` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(255) NULL ,
  `year` INT NULL ,
  `film_formats_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_films_film_formats_idx` (`film_formats_id` ASC) ,
  CONSTRAINT `fk_films_film_formats`
    FOREIGN KEY (`film_formats_id` )
    REFERENCES `film_formats` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `actors`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `actors` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `full_name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `films_has_actors`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `films_has_actors` (
  `films_id` INT NOT NULL ,
  `actors_id` INT NOT NULL ,
  PRIMARY KEY (`films_id`, `actors_id`) ,
  INDEX `fk_films_has_actors_actors1_idx` (`actors_id` ASC) ,
  INDEX `fk_films_has_actors_films1_idx` (`films_id` ASC) ,
  CONSTRAINT `fk_films_has_actors_films1`
    FOREIGN KEY (`films_id` )
    REFERENCES `films` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_films_has_actors_actors1`
    FOREIGN KEY (`actors_id` )
    REFERENCES `actors` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

INSERT INTO film_formats (name) VALUES ('DVD');
INSERT INTO film_formats (name) VALUES ('VHS');
INSERT INTO film_formats (name) VALUES ('Blu-Ray');


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
