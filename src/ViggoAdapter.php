<?php

namespace Kalendersiden;

class ViggoAdapter
{
    public $vcalendar;

    public function __construct(\Kigkonsult\Icalcreator\Vcalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
    }

    public function parse()
    {
        $events = $this->vcalendar->selectComponents(date('Y'), date('m'), date('d'), date('Y') + 1, date('m'), date('d'), 'vevent');
        foreach ($events as $year => $year_arr) {
            foreach ($year_arr as $month => $month_arr) {
                foreach ($month_arr as $day => $day_arr) {
                    foreach($day_arr as $event) {
                        $startDate = $event->getDtstart();
                        $endDate = $event->getDtend();
                        $summary = $event->getSummary();

                        if ($startDate->format('d-m-Y') != $endDate->format('d-m-Y')) {
                            $event_text = 'fra ' . $startDate->format('d.m.Y') . ' til ' . $endDate->format('d.m.Y') . ': ' . $summary;
                            $vevents[$this->hash($event_text)] = $event_text;
                        } else {
                            $event_text = $startDate->format('d.m.Y') . ': ' . $summary;
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
