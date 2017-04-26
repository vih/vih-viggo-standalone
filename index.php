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

// Prepare the Pimple dependency injection container.
$container = new \Slim\Container();

// Add a Twig service to the container.
$container['twig'] = function($container) {
    $loader = new Twig_Loader_Filesystem('templates');
    return new Twig_Environment($loader, array('cache'));
};

// Create the Slim application using our container.
$app = new \Slim\App($container);

$app->get('/', function(Slim\Http\Request $request, Slim\Http\Response $response) {
    // Load the template through the Twig service in the DIC.
    $template = $this->get('twig')->loadTemplate('index.html');
    // Render the template using a simple content variable.
    return $response->write($template->render(['content' => 'Hello, world!']));
})->setName('hello-world');

$app->get('/calendar/{name}', function (Slim\Http\Request $request, Response $response) {
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

    $start_month = $request->getQueryParam('start_month');
    $year = $request->getQueryParam('year');
    $months= $request->getQueryParam('months');
    $pages = $request->getQueryParam('pages');
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
                foreach ($day_arr as $event) {
                    $startDate = $event->getProperty("dtstart");
                    $endDate = $event->getProperty("dtend");
                    $summary = $event->getProperty("summary");

                    $begin = new DateTime($startDate['year']  . '-' . $startDate['month'] . '-' . $startDate['day']);
                    $end   = new DateTime($endDate['year']  . '-' . $endDate['month'] . '-' . $endDate['day']);

                    for ($i = $begin; $begin <= $end; $i->modify('+1 day')) {
                        $event_text = $i->format("Y/m/d") . ', ' . $summary;
                        if (isset($vevents[$i->format("Ymd")])) {
                            if ($last_summary != $summary) {
                                $vevents[$i->format("Ymd")] .= ' - ' . $summary;
                            }
                        } else {
                            $vevents[$i->format("Ymd")] = $event_text;
                        }
                    }
                    $last_date = $i;
                    $last_summary = $summary;
                }
            }
        }
    }
    $output = implode("\n", $vevents);

    try {
        $response = $response->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response = $response->withHeader('Content-Type', 'application/excel');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="kalender.csv"');

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
