<?php

/**
 * Stand alone app based on SlimPHP getting calendar from Viggo.
 */

require 'vendor/autoload.php';

$options = array('debug' => true);

$app = new \Slim\Slim($options);
$app->get('/calendar/:name', function ($name) use ($app) {
    $app->response->headers->set('Content-Type', 'text/calendar');
    if ($name === 'vies') {
        echo file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=1528&code=4d4e5cc9cc0a6e52360344f0508a22de8f420194');
    } else if ($name === 'vih') {
        echo file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=298&code=17bca452d0b19b39a49d3ffdc1a77faabe5ae617');
    } else {
        $app->notFound();
    }
});
$app->run();
