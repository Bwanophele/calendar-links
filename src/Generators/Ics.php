<?php

namespace Spatie\CalendarLinks\Generators;

use Spatie\CalendarLinks\Link;
use Spatie\CalendarLinks\Generator;

/**
 * @see https://icalendar.org/RFC-Specifications/iCalendar-RFC-5545/
 */
class Ics implements Generator
{
    public function generate(Link $link): string
    {
        $url = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
			'METHOD:REQUEST',
			'BEGIN:VEVENT',
            'UID:'.$this->generateEventUid($link),
            'SUMMARY:'.$link->title,
        ];

        if ($link->allDay) {
            $dateTimeFormat = 'Ymd';
            $url[] = 'DTSTART:'.$link->from->format($dateTimeFormat);
            $url[] = 'DURATION:P1D';
        } else {
            $dateTimeFormat = "e:Ymd\THis";
            $url[] = 'DTSTART;TZID='.$link->from->format($dateTimeFormat);
            $url[] = 'DTEND;TZID='.$link->to->format($dateTimeFormat);
        }

        if ($link->description) {
            $url[] = 'DESCRIPTION:'.$this->escapeString($link->description);
        }
		if ($link->organizer) {
			$url[] = 'ORGANIZER:' . $this->escapeString($link->organizer) . ':mailto:' . $this->escapeString($link->organizer);
		}

		if ($link->attendee) {

			foreach ($link->attendee as $attendee) {
				$url[] = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=
 						TRUE;CN=' . $this->escapeString($attendee) . ';X-NUM-GUESTS=0:mailto:' . $this->escapeString($attendee);
			}

		}
        if ($link->address) {
            $url[] = 'LOCATION:'.$this->escapeString($link->address);
        }

        $url[] = 'END:VEVENT';
        $url[] = 'END:VCALENDAR';
        $redirectLink = implode('%0d%0a', $url);

        return 'data:text/calendar;charset=utf8,'.$redirectLink;
    }

    /** @see https://tools.ietf.org/html/rfc5545.html#section-3.3.11 */
    protected function escapeString(string $field): string
    {
        return addcslashes($field, "\r\n,;");
    }

    /** @see https://tools.ietf.org/html/rfc5545#section-3.8.4.7 */
    protected function generateEventUid(Link $link): string
    {
        return md5($link->from->format(\DateTime::ATOM).$link->to->format(\DateTime::ATOM).$link->title.$link->address);
    }
}
