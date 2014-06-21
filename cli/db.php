<?php
// Include Yaml directory in the path
set_include_path(__DIR__ . '/../lib/Yaml' . PATH_SEPARATOR . get_include_path());

// Autoload classes
spl_autoload_register(function ($class) {
    $file = explode("\\", $class);
    $file = $file[count($file) - 1];
    include($file . '.php');
});

use Symfony\Component\Yaml\Dumper as Dumper;
use Symfony\Component\Yaml\Parser as Parser;

class BlonData {

    private $data = array(); 
    
    /** 
    * Constructor
    * 
    */
    public function __construct() {
    
    }
    
    /**
    * Provide serialization to the class
    * @return string
    */
    public function __toString() {
        return var_export($this->data, true);
    }

    /**
    * Add file to the data structure
    * @return 
    */
    public function addFile($file) {
        $this->data[$file] = array();

        return $this->data[$file];
    }

    /**
    * Check whether the match already exis in the database
    * @return boolean
    */
    private function isMatchDuplicate($match) {
        foreach (array_keys($this->data) as $file) 
        {
            // for each maches of the file
            foreach ($this->data[$file] as $matches ) 
            {
                if ($matches['match'] == $match)
                    return true;
            }
        }

        return false;
    }

    /**
    * Add a match of t() function to the data structure
    * @return 
    */
    public function addMatch($file, $lineNo, $line, $match) {
        // if file array did not exist add it
        if (!isset($this->data[$file])) $this->addFile($file);

        // check if the match already exists in the database
        $isDuplicate = $this->isMatchDuplicate($match);

        array_push($this->data[$file],
            array(
                'lineNo'     => $lineNo,
                'line'       => $line,
                'match'      => $match,
                'duplicate'  => $isDuplicate,
            )
        );
    }

    /** 
    * Save database to yaml format
    * @return mixed
    */
    public function save($output) {

        // if the output file exist open and parse it
        if (file_exists($output)) {
            $yaml = new Parser();
            $locale = $yaml->parse(file_get_contents($output));
        }
        
        // array that contain merged elements of $locale and new data
        $merged = array();

        foreach (array_keys($this->data) as $file) 
        {
            // if the file did not exists in the merged add it
            if (!isset($merged[$file])) $merged[$file] = array();

            // for each maches of the file
            foreach ($this->data[$file] as $matches ) 
            {
                // do not write if the match is duplicated
                if ($matches['duplicate']) continue;

                // if locale file already exists find out the value of the  match
                $value = "";
                if (isset($locale))
                    foreach ($locale[$file] as $l)
                        if (key($l) == $matches['match']) $value = $l[key($l)];

                // write the values in the merged array
                array_push($merged[$file], array(
                    $matches['match'] => $value
                ));
            }
        }

        // dump yaml into the disk
        $dumper = new Dumper();
        $yaml = $dumper->dump($merged, 2, 0);

        file_put_contents($output, $yaml);
    }

    /** 
    * Print string with color information to the terminal
    * @return none
    */
    public function printc($str) {
        $colors = array(
                    ':Black'         => "0;30",
                    ':Blue'          => "0;34",
                    ':Green'         => "0;32",
                    ':Cyan'          => "0;36",
                    ':Red'           => "0;31",
                    ':Purple'        => "0;35",
                    ':Brown'         => "0;33",
                    ':LightGray'     => "0;37",
                    ':DarkGray'      => "1;30",
                    ':LightBlue'     => "1;34",
                    ':LightGreen'    => "1;32",
                    ':LightCyan'     => "1;36",
                    ':LightRed'      => "1;31",
                    ':LightPurple'   => "1;35",
                    ':Yellow'        => "33",
                    ':White'         => "1;37",
                    ':Default'       => "0",
                );

        foreach (array_keys($colors) as $color) {
            $str = str_ireplace("$color ", chr(27) . '[' . $colors[$color] . 'm', $str);
        }

        echo $str;
    }

    /** 
    * print a list of all matches that find to the terminal
    * @return none
    */
    public function printMatches() {
        foreach (array_keys($this->data) as $file) {
            $this->printc(":Green $file :Default \n");

            $matches = $this->data[$file];
            foreach ($matches as $match) {
                $lineNo = $match['lineNo'];
                $line   = $match['line'];
                $m      = $match['match'];

                
                $line = preg_replace('/t\(\s*[\'\"](.*)[\'\"]\s*\)/', ":Yellow $0 :Default ", $line);
                $this->printc(":Brown $lineNo:Default :$line\n");
            }

            echo "\n";
        }
    }
}
