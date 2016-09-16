<?php

/**
 * Stand alone app based on SlimPHP getting calendar from Viggo.
 */

require 'vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;
use Kalendersiden\ViggoAdapter;
date_default_timezone_set('Europe/Copenhagen');

$options = array('debug' => true);

$app = new \Slim\App($options);
$app->get('/calendar/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    if ($name === 'vies') {
        $config = array("unique_id" => "vies.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vies.ics" );
        $headline = 'Vejle Idrætsefterskole';
    } else if ($name === 'vih') {
        $config = array("unique_id" => "vih.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vih.ics" );
        $headline = 'Vejle Idrætshøjskole';
    } else {
        return $response->withStatus(404);
    }

    $vcalendar = new vcalendar($config);
    $adapter = new ViggoAdapter($vcalendar);
    $event_data = $adapter->parse();

    $start_month = 1;
    $year = 2017;
    $months= 6;
    $pages = 1;
    $format = 'landscape';

    $url = 'https://kalendersiden.dk/generate.php';

    $client = new Client([
        // You can set any number of default request options.
        'timeout'  => 2.0,
    ]);

    $guzzle_response = $client->post($url, [
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
        $response = $response->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response = $response->withHeader('Content-Type', 'application/pdf');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="kalender.pdf"');
        $response = $response->write($guzzle_response->getBody());
        return $response;
    } catch (HttpException $ex) {
        echo $ex;
    }
});

$app->get('/calendar/csv/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    if ($name === 'vies') {
        $config = array("unique_id" => "vies.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vies.ics" );
        $headline = 'Vejle Idrætsefterskole';
    } else if ($name === 'vih') {
        $config = array("unique_id" => "vih.dk",
                        "URL"       => "https://vih-calendar.herokuapp.com/calendar/proxy/vih.ics" );
        $headline = 'Vejle Idrætshøjskole';
    } else {
        return $response->withStatus(404);
    }

    $vcalendar = new vcalendar($config);
    $vcalendar->parse();
    $events = $vcalendar->selectComponents(date('Y'), date('m'), date('d'), date('Y') + 1, date('m'), date('d'), 'vevent');
    foreach ($events as $year => $year_arr) {
        foreach ($year_arr as $month => $month_arr) {
            foreach ($month_arr as $day => $day_arr) {
                foreach($day_arr as $event) {
                    $startDate = $event->getProperty("dtstart");
                    $endDate = $event->getProperty("dtend");
                    $summary = $event->getProperty("summary");

                    if ($startDate['day'] . $startDate['month'] . $startDate['year'] != $endDate['day'] . $endDate['month'] . $endDate['year']) {
                        $event_text =  $endDate['year']  . '/' . $endDate['month'] . '/' .  $endDate['day'] . ', ' . $summary;
                        $vevents[md5($event_text )] = $event_text;
                    } else {
                        $event_text = $startDate['year']  . '/' . $startDate['month'] . '/' . $startDate['day'] . ', ' . $summary;
                        $vevents[md5($event_text )] = $event_text;
                    }
                }
            }
        }
    }
    $output = implode("\n", $vevents);

    try {
        $response = $response->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response = $response->withHeader('Content-Type', 'application/excel');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="sample.csv"');

        $response->getBody()->write($output);
        return $response;
    } catch (HttpException $ex) {
        echo $ex;
    }
});

$app->get('/calendar/proxy/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->withHeader('Content-Type', 'text/calendar');
    $response->withHeader('Content-Disposition', 'attachment');
    if ($name === 'vies.ics') {
        $response->getBody()->write(file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=1528&code=4d4e5cc9cc0a6e52360344f0508a22de8f420194'));
    } else if ($name === 'vih.ics') {
        $response->getBody()->write(file_get_contents('https://vejle.viggo.dk/ExportCalendar/?ViggoId=87&UserId=298&code=17bca452d0b19b39a49d3ffdc1a77faabe5ae617'));
    } else {
        return $response->withStatus(404);
    }
    return $response;
});
$app->run();
