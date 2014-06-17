<?php
define('intro', 
"Usage: gettext [OPTION]... [FILES OR DIRECTORIES]\n
Extract all strings that passed into t() function in the tree from current directory
and save them into the locale file. If the strings already exist in the locale file,
they will ignored.\n
Example: gettext --local=strings.en.php --ignore-dir=cache
OPTIONS:
    --local=filename                    File that extracted string will be saved in.
    --ignore-dir=name                   Add directory to list of ignored dirs. (Comma separated)
    -v, --verbose                       Show matches

Exit status is 0 if math, 1 if no match.\n");

?>
