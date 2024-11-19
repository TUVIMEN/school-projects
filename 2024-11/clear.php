<?php

function clear_string($val) {
    $val = trim($val);
    $val = addslashes($val);
    return $val;
}

function clear_text($val) {
    $val = trim($val);
    $val = filter_var($val,FILTER_UNSAFE_RAW);
    return $val;
}

function clear_email($val) {
    $val = clear_string($val);
    $val = strip_tags($val);
    if($val = filter_var($val,FILTER_VALIDATE_EMAIL))
        $val = htmlentities($val,ENT_QUOTES,'UTF-8');

    return $val;
}

function clear_html($val) {
    $val = clear_text($val);
    $val = strip_tags($val,"<b><i><sub><quote><br><u>");
    #$val = htmlentities($val, ENT_QUOTES,'UTF-8'); //for major security transform some other chars into html corrispective...

    return $val;
}

?>
