<?php if(!defined('__RESTER__')) exit;

verify::param('user-func', function($value) {
    return $value;
});

verify::header('user-func', function($value){
    return $value;
});


