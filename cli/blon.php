#!/usr/bin/php -q
<?php
    require('consts.php');

    $options = array();
    $files = array();
    $ignorelist = array();
    $locale_content = "";

    /**
    * Show introduction message
    * 
    * @return array
    */
    function intro() {
        echo constant('intro');
    }

    /**
    * Read list of params form command line
    * 
    * @return array
    */
    function readParams() {
        global $options;
        global $files;

        // read cli arguments
        $argc = $_SERVER["argc"]."\n";
        $argv = $_SERVER["argv"];

        // delete cli file from args list
        unset($argv[0]);

        // process args
        foreach($argv as $a) {
            $p = explode("=", $a);
            if ($p[0][0] == '-') {
                $options[$p[0]] = $p[1];
            } else {
                array_push($files, $a);
            }
        }
        buildIgnoreList();
    }

    /**
    * Build an array of files that need to be ignored
    * 
    * @return array
    */
    function buildIgnoreList() {
        global $ignorelist;
        global $options;

        // by default ignore files that started with . 
        array_push($ignorelist, '/(^|\/)\..+/');

        // push user specified ignore list into $ignorelist
        if (isOptionIncluded('--ignore-dir')) {
            $idirs = explode(',', $options['--ignore-dir']);

            foreach ($idirs as $idir) 
                array_push($ignorelist, '/^' . str_replace('/', '\/', $idir) . '($|\/).*/');
        }
    }

    /**
    * Get list of the supported extension
    * 
    * @return array
    */
    function getSupportedExtensions() {
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
    function isFileIgnored($file) {
        // ignore if file included in ignore list
        global $ignorelist;
        foreach ($ignorelist as $i) 
            if (preg_match($i, $file)) return true;

        // ignore if file extension is not supported
        if (!is_dir($file)) {
            $file_ext = "." . pathinfo($file, PATHINFO_EXTENSION);
            $types = getSupportedExtensions();

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
    function isOptionIncluded() {
        global $options;

        $args = func_get_args();
        foreach ($args as $arg) {
            if (isset($arg, $options)) {
                return true;
            }
        }
        return false;
    }

    /**
    * Check if the option is included
    * 
    * @return array
    */
    function getOptionValue($p) {
        global $options;
        return $options[$p];
    }

    /**
    * Print list of matches
    * 
    * @return array
    */
    function printMatches($matches, $file) {
        echo "\033[32m$file\n\033[37m";

        // load content of the file because we need it to find find number of match
        $content = file_get_contents($file);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $charpos = $matches[0][$i][1];

            if ($charpos == 0) {
                $lineno = 1;
            } else {
                // find line number
                list($before) = str_split($content, $charpos);
                $lineno = strlen($before) - strlen(str_replace("\n", "", $before)) + 1;
            }

            $line = $matches[0][$i][0]; 
            $match = $matches[1][$i][0];
            
            $line = preg_replace('/t\(\s*[\'\"](.*)[\'\"]\s*\)/', "\033[30;43m$0\033[0m", $line);
            echo "\033[33m$lineno\033[0m:$line\n";
        }

        echo "\n";
    }

    /**
    * Write matches to locale file
    * 
    * @return array
    */
    function writeLocaleHeader() {
        global $locale_content;
        $locale_content .= "<?php\n\treturn array(\n";
    }

    /**
    * Write matches to locale file
    * 
    * @return array
    */
    function writeLocaleFooter() {
        global $locale_content;
        $locale_content .= ");\n";
    }

    /**
    * Write matches to locale file
    * 
    * @return array
    */
    function writeLocaleMatches($matches, $file) {
        global $locale_content;
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        $locale_content .= "\t\t/**\n\t\t* $file\n\t\t*/\n";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $match = $matches[1][$i][0];
            $locale_content .= "\t\t\"$match\" => \"\",\n";
        }
        $locale_content .= "\n";
    }

    /**
    * Write matches to locale file
    * 
    * @return array
    */
    function saveMatches() {
        global $locale_content;
        file_put_contents(getOptionValue('--locale'), $locale_content);
    }

    /**
    * Read file, find all matches of t('') function and writes them into the 
    * locale file.
    * 
    * @return array
    */
    function processFile($file) {
        $pattern = "/.*\bt\([\'\"](.*)[\'\"]\).*/";

        $text = file_get_contents($file);
        $result = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        if (!$result) return;

        // Show result if verbose option is set
        //if (isOptionIncluded('-v', '--verbose')) printMatches($matches, $file);

        // Create locale file
        if (isOptionIncluded('--locale')) writeLocaleMatches($matches, $file);
    }

    /**
    * Process current directory recursively
    * 
    * @return array
    */
    function process($dir) {
        if ($handle = opendir($dir)) {
            while (false != ($name = readdir($handle))) {
                
                // ignore . and ..
                if ($name == '.' || $name == '..') continue;
                
                // Build a path
                if ($dir != '.' && is_dir($dir)) $name = "$dir/$name";

                // ignore files that need to be ignored
                if (isFileIgnored($name)) continue;

                if (is_dir($name)) process("$name"); else processFile($name);
            }

            closedir($handle);

        }
    }

    /**
    * Main function
    * 
    * @return
    */
    function main() {
        global $options;
        global $files;

        writeLocaleHeader();

        readParams();
        if (count($options) == 0 && count($files) == 0) {
            intro();
        } else {
            process('.');
        }

        writeLocaleFooter();

        saveMatches();
    }

    // call the main function
    main();
