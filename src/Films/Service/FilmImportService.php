<?php
namespace Films\Service;


class FilmImportService
{

    /** @var FilmService */
    private $filmService;

    public function __construct(FilmService $filmService)
    {
        $this->filmService = $filmService;
    }

    public function import($file)
    {
        if (isset($file['file']) && $file['file']['error'] != 4) {

            if ($file['file']['error'] != 1 && $file['file']['error'] != 0) {
                $error = $file['file']['error'];
                $errors [] = 'Error: File not uploads.' . ' Code error: ' . $error;
            }
        }

        $filesize = $file['file']['size'];
        if ($file['file']['error'] == 1 || $filesize > 3145728) {
            $filesize = ($filesize != 0) ? sprintf('(%.2f Мб)', $filesize / 1024) : '';
            die('Error: Size of attached file ' . $filesize . ' is larger than allowed (3 Mb).');
        }

        $filename = $file['file']['name'];
        $filepath = $file['file']['tmp_name'];
        $filetype = $file['file']['type'];
        if ($filetype == null || $filetype == '' || $filetype != 'text/plain') {
            die('Such type not support.');
        }

        $data = file_get_contents($filepath);
        $film = [];
        $length = strlen($data);
        $i = 0;
        while ($i != $length) {
            $title = stripos($data, 'title:', $i) + 6;
            $year = stripos($data, 'Release Year:', $i) + 13;
            $format = stripos($data, 'Format:', $i) + 7;
            $actors = stripos($data, 'Stars:', $i) + 6;

            $film['title'] = trim(substr($data, $title, $year - $title - 13));
            $film['year'] = trim(substr($data, $year, $format - $year - 7));
            $film['format'] = trim(substr($data, $format, $actors - $format - 6));

            $end = stripos($data, 'title:', $actors);
            if ($end != false and $end < $length) {
                $end -= $actors;
            } else {
                $end = $length - $actors;
            }
            $dataActors = trim(substr($data, $actors, $end));
            $dataActors = str_replace(',', ',|', $dataActors);
            $film['actors'] = explode('|', $dataActors);

            $this->filmService->addFilm($film);
            $film = [];
            $i = $actors + $end;
        }

        echo 'File upload ' . $filename;
    }

}