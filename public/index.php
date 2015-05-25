<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

use Films\Component\Application;
use Films\Service\FilmService;
use Films\Service\FilmImportService;

require_once __DIR__ . '/../src/autoload.php';

$app = new Application();

$app->get('/', function () use ($app) {
    header('Location: ' . 'http://' . $_SERVER['HTTP_HOST'] . '/films/list', true, 301);
    die();
});

$app->get('/films/list', function () use ($app) {
    $filmService = new FilmService();
    $filmsOnPage = 20;
    $pages = $filmService->numberPages($filmsOnPage);
    $page = (empty($_GET['page']) or $_GET['page'] > $pages or $_GET['page'] < 1) ? 1 : $_GET['page'];
    $films = $filmService->getFilms($page, $filmsOnPage);
    if (!empty($_GET['search']) and ($_GET['search_type'] === 'film' or $_GET['search_type'] === 'actor')) {
        $search = strip_tags(trim($_GET['search']));
        $searchType = strip_tags(trim($_GET['search_type']));

        if ($searchType === 'film') {
            $searchFilms = [];
            $searchFilm = $filmService->findFilmByTitle($search);
            array_push($searchFilms, $searchFilm);
        }
        if ($searchType === 'actor') {
            $searchFilms = $filmService->findFilmByActor($search);
        }

        echo $app->render('list.html.twig', ['searchFilms' => $searchFilms]);
    } else {
        echo $app->render('list.html.twig', ['films' => $films, 'pages' => $pages, 'page' => $page]);
    }


});

$app->get('/films/show', function () use ($app) {
    $filmService = new FilmService();
    $film = $filmService->getFilm($_GET['id']);

    echo $app->render('show.html.twig', ['film' => $film]);
});

$app->get('/films/add', function () use ($app) {

    echo $app->render('add.html.twig', []);

});

$app->post('/films/add', function () use ($app) {
    if (!empty($_POST['title']) and !empty($_POST['year']) and !empty($_POST['format']) and !empty($_POST['actors'])) {
        $title = strip_tags(trim($_POST['title']));
        $year = strip_tags(trim($_POST['year']));
        if ((int)$year < 1895 and (int)$year > (int)date('Y') + 5) {
            throw new InvalidArgumentException('Wrong format year ' . $year);
        }
        $format = strip_tags(trim($_POST['format']));
        $actors = explode(',', strip_tags(trim($_POST['actors'])));
        $actors = array_diff($actors, ['']);

        $length = count($actors);
        for ($i = 0; $i < $length-1; $i++){
            $actors[$i] .= ',';
        }
    }

    $filmService = new FilmService();
    $filmService->addFilm(
        [
            'title' => $title,
            'year' => $year,
            'format' => $format,
            'actors' => $actors
        ]
    );
    echo $app->render('list.html.twig', []);

});

$app->get('/films/delete', function () use ($app) {
    $filmService = new FilmService();
    $filmService->deleteFilm($_GET['id']);

    echo $app->render('list.html.twig', []);

});

$app->get('/films/import', function () use ($app) {
    echo $app->render('import.html.twig', []);

});

$app->post('/films/import', function () use ($app) {
    $filmService = new FilmService();
    $filmImportService = new FilmImportService($filmService);
    $filmImportService->import($_FILES);

    echo $app->render('list.html.twig', []);

});

$app->run();