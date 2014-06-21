#!/usr/bin/env php
<?php
require('consts.php');
require('db.php');

class Blon {
    private $options = array();
    private $files = array();
    private $ignorelist = array();

    private $db;

    /**
    * Read list of params form command line
    * 
    * @return array
    */
    private function readParams() {
        // read cli arguments
        $argc = $_SERVER["argc"]."\n";
        $argv = $_SERVER["argv"];

        // delete cli file from args list
        unset($argv[0]);

        // process args
        foreach($argv as $arg) {
            if ($arg[0] == '-') {
                $param = explode('=', $arg);
                $name = $param[0];
                $value = "";
                if (isset($param[1])) $value = $param[1];
                $this->options[$name] = $value;
            } else {
                array_push($this->files, $arg);
            }
        }

        $this->buildIgnoreList();
    }

    /**
    * Build an array of files that need to be ignored
    * 
    * @return array
    */
    private function buildIgnoreList() {
        // by default ignore files that started with . 
        array_push($this->ignorelist, '/(^|\/)\..+/');

        // push user specified ignore list into $ignorelist
        if ($this->isOptionIncluded('--ignore-dir')) {
            $idirs = explode(',', $this->options['--ignore-dir']);

            foreach ($idirs as $idir) 
                array_push($this->ignorelist, '/^' . str_replace('/', '\/', $idir) . '($|\/).*/');
        }
    }

    /**
    * Get list of the supported extension
    * 
    * @return array
    */
    private function getSupportedExtensions() {
        return array(
            "html"   => ".htm .html",
            "php"    => ".php .phpt .php3 .php4 .php5 .phtml",
            "volt"   => ".volt");
    }

    /**
    * Check if the file need to be ignored
    * 
    * @return boolean 
    */
    private function isFileIgnored($file) {
        // ignore if file included in ignore list
        foreach ($this->ignorelist as $i) 
            if (preg_match($i, $file)) return true;

        // ignore if file extension is not supported
        if (!is_dir($file)) {
            $file_ext = "." . pathinfo($file, PATHINFO_EXTENSION);
            $types = $this->getSupportedExtensions();

            $isExtSupported = false;
            foreach ($types as $t) {
                if (strpos($t, $file_ext) >= 0) { $isExtSupported = true; }
            }
            if (!$isExtSupported) return true;
        }

        // if we get here, it means file should not be ignored
        return false;
    }

    /**
    * Check if the option is included
    * 
    * @return array
    */
    private function isOptionIncluded() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (isset($this->options[$arg])) return true;
        }
        return false;
    }

    /**
    * Read file, find all matches of t('') function and writes them into the 
    * locale file.
    * 
    * @return array
    */
    private function processFile($file) {
        $pattern = "/.*\bt\([\'\"](.*)[\'\"]\).*/";

        $text = file_get_contents($file);
        $result = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        // if result not find return
        if (!$result) return;

        for ($i = 0; $i < count($matches[0]); $i++) {
            $charpos = $matches[0][$i][1];

            if ($charpos == 0) {
                $lineNo = 1;
            } else {
                // find line number
                list($before) = str_split($text, $charpos);
                $lineNo = strlen($before) - strlen(str_replace("\n", "", $before)) + 1;
            }

            $line = $matches[0][$i][0]; 
            $match = $matches[1][$i][0];
            
            $this->db->addMatch($file, $lineNo, $line, $match);
        }
    }

    /**
    * Process current directory recursively
    * 
    * @return array
    */
    public function process($dir) {
        if ($handle = opendir($dir)) {
            while (false != ($name = readdir($handle))) {
                
                // ignore . and ..
                if ($name == '.' || $name == '..') continue;
                
                // Build a path
                if ($dir != '.' && is_dir($dir)) $name = "$dir/$name";

                // ignore files that need to be ignored
                if ($this->isFileIgnored($name)) continue;

                if (is_dir($name)) $this->process("$name"); else $this->processFile($name);
            }

            closedir($handle);

        }
    }

    /**
    * Constructor 
    * 
    * @return
    */
    public function __construct() {
        $this->readParams();

        $this->db = new BlonData();

        if (count($this->options) == 0 && count($this->files) == 0) {
            echo constant('intro');
        } else {
            $this->process(".");
        }

        if ($this->isOptionIncluded('-v', '--verbose')) $this->db->printMatches();

        if ($this->isOptionIncluded('--locale')) $this->db->save($this->options['--locale']);
    }
}

$blon = new Blon();
