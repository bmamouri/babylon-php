<?php
    $locale = 'fa';

    function t($msg) {
        global $locale;
        $strings = require_once('locale/' . $locale . '.php');
        return $strings[$msg];
    }
    
    echo t("Hello");
    
