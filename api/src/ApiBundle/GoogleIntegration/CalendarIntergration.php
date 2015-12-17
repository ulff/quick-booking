<?php

namespace ApiBundle\GoogleIntegration;

use ApiBundle\ApiBundle;
use ApiBundle\Entity as ApiEntity;
use Symfony\Component\HttpKernel\Exception\HttpException;

define('APPLICATION_NAME', 'QuickBooking');
define('CREDENTIALS_PATH', __DIR__ . '/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('SCOPES', implode(' ', array(
        \Google_Service_Calendar::CALENDAR)
));

class CalendarIntergration
{

    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_Service_Calendar
     */
    private $service;

    /**
     * Returns an authorized API client.
     * @return \Google_Client the authorized client object
     */
    public function __construct()
    {
        $client = new \Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        if (!file_exists(CREDENTIALS_PATH)) {
            throw new \Exception('No credentials set');
        }

        $accessToken = file_get_contents(CREDENTIALS_PATH);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents(CREDENTIALS_PATH, $client->getAccessToken());
        }

        $this->client = $client;

        $this->service = new \Google_Service_Calendar($client);
    }

    public function listCalendars()
    {
        $calendarList = $this->service->calendarList->listCalendarList();

        $stpGdanskWhiteList = [
            'Schopenhauer Conference Room',
            'Fahrenheit Conference Room',
            'Hevelius Conference Room'
        ];

        $calendars = [];
        foreach ($calendarList->getItems() as $calendarListEntry) {
            if(in_array($calendarListEntry->getSummary(), $stpGdanskWhiteList)) {
                $calendar = new ApiEntity\Calendar($calendarListEntry->getId(), $calendarListEntry->getSummary());
                $calendars[] = $calendar;
            }
        }

        return $calendars;
    }

    public function listCalendarEvents($calendarId)
    {
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );
        $results = $this->service->events->listEvents($calendarId, $optParams);

        $events = [];
        foreach ($results->getItems() as $event) {
            $apiEvent = new ApiEntity\Event(
                $event->getSummary(),
                new \DateTime('@'.strtotime($event->start->dateTime), new \DateTimeZone('Europe/Warsaw')),
                new \DateTime('@'.strtotime($event->end->dateTime), new \DateTimeZone('Europe/Warsaw'))
            );
            $apiEvent->setId($event->getId());
            $apiEvent->setCreatedTime(new \DateTime('@'.strtotime($event->created), new \DateTimeZone('Europe/Warsaw')));
            $apiEvent->setDescription($event->description);
            $apiEvent->setLocation($event->location);
            $events[] = $apiEvent;
        }

        return $events;
    }

    public function createEvent($calendarId, ApiEntity\Event $event)
    {
        $googleEvent = new \Google_Service_Calendar_Event(array(
            'summary' => $event->getTitle(),
            'location' => $event->getLocation(),
            'description' => $event->getDescription(),
            'start' => array(
                'dateTime' => $event->getStartTime()->format(\DateTime::ISO8601),
                'timeZone' => 'Europe/Warsaw',
            ),
            'end' => array(
                'dateTime' => $event->getEndTime()->format(\DateTime::ISO8601),
                'timeZone' => 'Europe/Warsaw',
            ),
            'recurrence' => array(
                'RRULE:FREQ=DAILY;COUNT=1'
            ),
            'attendees' => array(
                array('email' => $calendarId)
            ),
            'reminders' => array(
                'useDefault' => FALSE,
                'overrides' => array(
                    array('method' => 'popup', 'minutes' => 10),
                ),
            ),
        ));

        $this->service->events->insert('primary', $googleEvent);
    }
}