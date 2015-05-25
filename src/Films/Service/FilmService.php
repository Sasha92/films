<?php
namespace Films\Service;

use Exception;
use PDO;

class FilmService
{

    /** @var  PDO */
    protected $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=films', 'root', 1111
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param $filmData
     *
     * @return string
     * @throws Exception
     */
    public function addFilm($filmData)
    {
        // start transaction
        $this->pdo->beginTransaction();
        try {
            $filmId = '';
            // find film format
            $stmt = $this->pdo->prepare(
                'SELECT id from film_formats WHERE name = ?'
            );
            $stmt->execute([$filmData['format']]);
            $result = $stmt->fetch();
            $formatId = $result['id'];

            //search in database films
            $stmt = $this->pdo->prepare('SELECT title from films');
            $stmt->execute();
            $films = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $searchFilm = array_search($filmData['title'], $films);

            // insert film to films table if not film in database
            if ($searchFilm === false) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO films (title, year, film_formats_id) VALUES (?, ?, ?)'
                );
                $stmt->execute(
                    [$filmData['title'], $filmData['year'], $formatId]
                );
                $filmId = $this->pdo->lastInsertId();

                // find actors ids by name
                foreach ($filmData['actors'] as $actor) {
                    $stmt = $this->pdo->prepare(
                        'SELECT id from actors WHERE full_name = ?'
                    );
                    $stmt->execute([$actor]);
                    $result = $stmt->fetch();
                    $actorId = isset($result['id']) ? $result['id'] : null;
                    // if no actor with such name insert in actors table
                    if (!$actorId) {
                        $stmt = $this->pdo->prepare(
                            'INSERT INTO actors (full_name) VALUES (?)'
                        );
                        $stmt->execute([$actor]);
                        $actorId = $this->pdo->lastInsertId();
                    }

                    // insert relations in films_has_actors table
                    $stmt = $this->pdo->prepare(
                        'INSERT INTO films_has_actors (films_id, actors_id) VALUES (?, ?)'
                    );
                    $stmt->execute([$filmId, $actorId]);

                }

            }

            // commit transaction
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $filmId;

    }

    public function deleteFilm($id)
    {
        $this->pdo->beginTransaction();
        $this->pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        //search actors in this film
        $stmt = $this->pdo->prepare(
            'SELECT actors.full_name from actors
           join films_has_actors  on films_has_actors.actors_id = actors.id
           join  films on films.id = films_has_actors.films_id
           where films.id = ?'

        );
        $stmt->execute([$id]);
        $actorsInFilm = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $numberActors = count($actorsInFilm);
        $i = 0;

        while ($i < $numberActors) {
            // check actor only in this film
            $titleFilms = $this->findFilmByActor($actorsInFilm[$i]);
            $numberFilms = count($titleFilms);

            if ($numberFilms == 1) {
                //delete rows from table actors
                if (!$this->pdo->inTransaction()) {
                    $this->pdo->beginTransaction();
                }
                $stmt = $this->pdo->prepare(
                    'DELETE from actors WHERE full_name = ?'
                );
                $stmt->execute([$actorsInFilm[$i]]);
                $this->pdo->commit();
            }
            $i++;

        }

        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
        $stmt = $this->pdo->prepare('DELETE from films WHERE id = :id');
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->pdo->prepare('DELETE from films_has_actors WHERE films_id = :id');
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();

        $this->pdo->query('SET FOREIGN_KEY_CHECKS = 1');
        $this->pdo->commit();
    }

    public function getFilm($id)
    {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare(
            'SELECT films.title, films.year, film_formats.name as format  from films
          join  film_formats on films.film_formats_id = film_formats.id
          where films.id = ?'
        );
        $stmt->execute([$id]);
        $resultFilm = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $this->pdo->prepare(
            'SELECT  actors.full_name  from actors
          join  films_has_actors on films_has_actors.actors_id = actors.id
          join  films on films.id = films_has_actors.films_id
          where films.id = ?'
        );
        $stmt->execute([$id]);
        $resultFilm[0]['actors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->commit();

        return $resultFilm[0];

    }

    public function numberPages($filmsPerPage)
    {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->query(
            'SELECT count(id) from films'
        );
        $stmt->execute();
        $numberFilms = $stmt->fetch();
        $this->pdo->commit();
        $pages = ceil($numberFilms['0'] / $filmsPerPage);
        return $pages;
    }

    public function getFilms($page, $filmsPerPage)
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->query(
            'SELECT id, title  from films order by title ASC'
        );
        $films = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filmsOnPage = array_chunk($films, $filmsPerPage);

        $this->pdo->commit();
        if ($filmsOnPage != Null) {
            return $filmsOnPage[$page - 1];
        }
    }

    public function findFilmByTitle($title)
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare(
            'SELECT  id, title  from films where title = ?'
        );
        $stmt->execute([$title]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->pdo->commit();
        return $result;
    }

    public function findFilmByActor($actorFullName)
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
        $stmt = $this->pdo->prepare(
            'SELECT  films.id, films.title from films
            join films_has_actors on films.id = films_has_actors.films_id
            join actors on films_has_actors.actors_id = actors.id
            where   actors.full_name = ?'
        );
        $stmt->execute([$actorFullName]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->pdo->commit();
        return $result;
    }
}