<?php
require dirname(__FILE__) . '/classes/Bootstrap.php';
Bootstrap::web();

class Engine
{
    protected $_path = null;

    public function __construct()
    {
        $this->_path = dirname(__FILE__) . '/pages';


    }

    public function show()
    {
        $page = 'index';
        $params = array();

        if (isset($_GET['page'])) {
            $page = preg_replace('/\W/', '', $_GET['page']);
            if (isset($_GET['params'])) {
                $params = (array) $_GET['params'];
            }
        }

        $this->showPage(
            $page,
            $this->executePage($page, $params)
        );
    }

    public function showPage($page, $vars)
    {
        var_export($page, $vars);
    }

    public function executePage($page, array $params = array())
    {
        $path = $this->_getPagePath($page);
        return include $path;
    }

    protected function _getPagePath($page)
    {
        $page = preg_replace('/\W/', '', $page);

        $path = $this->_page . '/' . $page . '.php';

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("Page <$page> not found!");
        }

        return $path;
    }

    protected function _getViewPath($page)
    {
        $page = preg_replace('/\W/', '', $page);

        $path = $this->_page . '/' . $page . '.html';

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("Template <$page> not found!");
        }

        return $path;
    }
}

$engine = new Engine();
$engine->show();