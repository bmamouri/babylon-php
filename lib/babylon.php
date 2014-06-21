<?php

// Include Yaml directory in the path
set_include_path(__DIR__ . '/Yaml' . PATH_SEPARATOR . get_include_path());

// Autoload classes
spl_autoload_register(function ($class) {
    $file = explode("\\", $class);
    $file = $file[count($file) - 1];
    include($file . '.php');
});

use Symfony\Component\Yaml\Parser as Parser;

class Babylon {

    protected static $instance = null;

    protected $locale;

    // make constructor protected
    protected function __construct() {

    }
    
    // stop cloning
    protected function __clone() {
    
    }

    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function setLocale($localeFile) {
        $yaml = new Parser();
        $this->locale = $yaml->parse(file_get_contents($localeFile));
    }

    private function recursiveFind(array $array, $needle)
    {
        $iterator  = new RecursiveArrayIterator($array);
        $recursive = new RecursiveIteratorIterator($iterator,
                            RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
        }
    }

    public function t($match) {
        $t = $this->recursiveFind($this->locale, $match);
        return ($t == "" ? $match : $t);
    }
}

function t($match) {
    $babylon = Babylon::getInstance();

    return $babylon->t($match);
}

Babylon::getInstance()->setLocale('/htdocs/bakdad/fa.php');
echo t('Courses');
