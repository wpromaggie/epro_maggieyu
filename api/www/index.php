<?php
require(dirname(__FILE__).'/protected/core/api.php');

$app = new api();
require(dirname(__FILE__).'/protected/core/route.php');
$app->run();
?>