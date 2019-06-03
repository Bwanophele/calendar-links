<?php

namespace Spatie\CalendarLinks\Generators;

use Spatie\CalendarLinks\Link;
use Spatie\CalendarLinks\Generator;

/**
 * @see https://icalendar.org/RFC-Specifications/iCalendar-RFC-5545/
 */
class IcsOrganizer implements Generator
{
	public function generate(Link $link): string
	{
		$url = [
			'BEGIN:VCALENDAR',
			'PRODID:-//Google Inc//Google Calendar 70.9054//EN',
			'VERSION:2.0',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-TIMEZONE:Europe/Paris',
			'BEGIN:VEVENT',
		];

		if ($link->allDay) {
			$dateTimeFormat = 'Ymd';
			$url[] = 'DTSTART:'.$link->from->format($dateTimeFormat);
			$url[] = 'DURATION:P1D';
		} else {
			$dateTimeFormat = "Ymd\THis\Z";
			$url[] = 'DTSTART:'.gmdate($dateTimeFormat,strtotime($link->from->format('Y-m-d H:i')));
			$url[] = 'DTEND:'.gmdate($dateTimeFormat,strtotime($link->to->format('Y-m-d H:i')));
			$url[] = 'DTSTAMP:'.date('Ymd\THis');
		}
		if ($link->organizer) {
			$url[] = 'ORGANIZER;CN=' . $this->escapeString($link->organizer) . ':mailto:' . $this->escapeString($link->organizer);
			$url[] = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=CHAIR;PARTSTAT=ACCEPTED;CN=' . $this->escapeString($link->organizer) . ';X-NUM-GUESTS=0:mailto:' . $this->escapeString($link->organizer);

		}
		$url[] = 'UID:'.$this->generateEventUid($link);
		if ($link->attendee) {

			foreach ($link->attendee as $attendee) {
				$url[] = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=' . $this->escapeString($attendee) . ';X-NUM-GUESTS=0:mailto:' . $this->escapeString($attendee);
			}

		}

		if ($link->description) {
			$url[] = 'DESCRIPTION:'.$this->escapeString($link->description);
		}

		if ($link->address) {
			$url[] = 'LOCATION:'.$this->escapeString($link->address);
		}

		$url[] = 'SEQUENCE:0';
		$url[] = 'STATUS:CONFIRMED';
		$url[] = 'SUMMARY:'.$link->title;
		$url[] = 'TRANSP:OPAQUE';
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
