<?php

/**
 * Stand alone app based on SlimPHP getting calendar from Viggo.
 */

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Kalendersiden\ViggoAdapter;
date_default_timezone_set('Europe/Copenhagen');

$options = array('debug' => true);

$app = new \Slim\Slim($options);
$app->get('/calendar/:name', function ($name) use ($app) {
    if ($name === 'vies') {
        $config = array("unique_id" => "vies.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vies.ics" );
        $headline = 'Vejle Idrætsefterskole';
    } else if ($name === 'vih') {
        $config = array("unique_id" => "vih.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vih.ics" );
        $headline = 'Vejle Idrætshøjskole';
    } else {
        $app->notFound();
    }

    $vcalendar = new vcalendar($config);
    $adapter = new ViggoAdapter($vcalendar);
    $event_data = $adapter->parse();

    $start_month = 8;
    $year = 2016;
    $months= 5;
    $pages = 1;
    $format = 'landscape';

    $url = 'https://kalendersiden.dk/generate.php';

    $client = new Client([
        // You can set any number of default request options.
        'timeout'  => 2.0,
    ]);

    $response = $client->post($url, [
        'form_params' => [
            'month' => $start_month,
            'year' => $year,
            'months' => $months,
            'pages' => $pages,
            'format' => $format,
            'head' => $headline,
            'info' => ['showyear', 'holidays', 'weeks'],
            'userdata' => $event_data
        ]
    ]);

    try {
        $app->response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $app->response->headers->set('Content-Type', 'application/pdf');
        $app->response->headers->set('Content-Disposition', 'attachment; filename="kalender.pdf"');
        print $response->getBody();
    } catch (HttpException $ex) {
        echo $ex;
    }
});
$app->get('/calendar/proxy/:name', function ($name) use ($app) {
    $app->response->headers->set('Content-Type', 'text/calendar');
    $app->response->headers->set('Content-Disposition', 'attachment');
    if ($name === 'vies.ics') {
        echo file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=1528&code=4d4e5cc9cc0a6e52360344f0508a22de8f420194');
    } else if ($name === 'vih.ics') {
        echo file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=298&code=17bca452d0b19b39a49d3ffdc1a77faabe5ae617');
    } else {
        $app->notFound();
    }
});
$app->run();
