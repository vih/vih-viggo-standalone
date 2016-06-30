<?php

namespace Kalendersiden;

class ViggoAdapter
{
    public $vcalendar;

    public function __construct(\vcalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
    }

    public function parse()
    {
        $this->vcalendar->parse();
        $events = $this->vcalendar->selectComponents(date('Y'), date('m'), date('d'), date('Y') + 1, date('m'), date('d'), 'vevent');
        foreach ($events as $year => $year_arr) {
            foreach ($year_arr as $month => $month_arr) {
                foreach ($month_arr as $day => $day_arr) {
                    foreach($day_arr as $event) {
                        $startDate = $event->getProperty("dtstart");
                        $endDate = $event->getProperty("dtend");
                        $summary = $event->getProperty("summary");

                        if ($startDate['day'] . $startDate['month'] . $startDate['year'] != $endDate['day'] . $endDate['month'] . $endDate['year']) {
                            $event_text = 'fra ' . $startDate['day'] . '.' . $startDate['month'] . '.' . $startDate['year'] . ' til ' . $endDate['day']  . '.' . $endDate['month'] . '.' . $endDate['year'] . ': ' . $summary;
                            $vevents[$this->hash($event_text)] = $event_text;
                        } else {
                            $event_text = $startDate['day'] . '.' . $startDate['month'] . '.' . $startDate['year'] . ': ' . $summary;
                            $vevents[$this->hash($event_text)] = $event_text;
                        }
                    }
                }
            }
        }
        return implode("\n", $vevents);
    }

    protected function hash($text)
    {
        return $text;
    }
}
