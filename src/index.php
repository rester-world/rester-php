<?php
include_once('./rester/common.php');
try
{
    rester::run();
}
catch (Exception $e)
{
    var_dump($e->getTrace());
}
