<?php


namespace Films\Component;


use Twig_Environment;
use Twig_Loader_Filesystem;

class Application
{
    private $actions;
    /** @var  Twig_Environment */
    private $templateEngine;

    public function __construct()
    {
        $this->actions = [
            'GET' => [],
            'POST' => []
        ];
    }

    public function get($pattern, $action)
    {
        $this->actions['GET'][$pattern] = $action;
    }

    public function post($pattern, $action)
    {
        $this->actions['POST'][$pattern] = $action;
    }

    public function run()
    {
        $this->setupTemplates();
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['PATH_INFO'] ? $_SERVER['PATH_INFO'] : '/';

        foreach ($this->actions[$method] as $pattern => $action) {
            if ($path === $pattern) {
                call_user_func($action);
            }
        }
    }

    /**
     * @param $template
     * @param $data
     * @return string
     */
    public function render($template, $data)
    {
        return $this->templateEngine->render($template, $data);
    }

    private function setupTemplates()
    {
        $loader = new Twig_Loader_Filesystem(ROOT_DIR . '/src/Films/Resources/views');
        $this->templateEngine = new Twig_Environment($loader, array('cache' => ROOT_DIR . '/cache', 'auto_reload' => true));
    }
}