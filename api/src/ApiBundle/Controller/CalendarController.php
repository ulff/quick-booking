<?php

namespace ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ApiBundle\GoogleIntegration\CalendarIntergration;
use ApiBundle\Entity\Event;

class CalendarController extends FOSRestController
{
    /**
     * Returns collection of STP calendars.
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns collection of STP calendars",
     *  statusCodes={
     *      200="Returned when successful",
     *  }
     * )
     */
    public function getCalendarsAction()
    {
        $calendarIntegration = new CalendarIntergration();
        $calendars = $calendarIntegration->listCalendars();

        $view = $this->view($calendars, 200);
        return $this->handleView($view);
    }

    /**
     * Returns collection of events for selected room.
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns collection of events for selected room",
     *  statusCodes={
     *      200="Returned when successful",
     *      404="Returned when room with given id does not exist"
     *  }
     * )
     */
    public function getCalendarsEventsAction($calendarId)
    {
        $calendarIntegration = new CalendarIntergration();

        // todo: check if calendar exists, else 404

        $events = $calendarIntegration->listCalendarEvents($calendarId);

        $view = $this->view($events, 200);
        return $this->handleView($view);
    }

    /**
     * Creates new event in given calendar.
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Creates new event in given calendar",
     *   parameters={
     *      {"name"="title", "dataType"="string", "description"="event title", "required"=true},
     *      {"name"="startTime", "dataType"="timestamp", "description"="event start time", "required"=true},
     *      {"name"="endTime", "dataType"="timestamp", "description"="event end time", "required"=true},
     *      {"name"="description", "dataType"="string", "description"="event description", "required"=false},
     *      {"name"="location", "dataType"="string", "description"="event location", "required"=false}
     *   },
     *   statusCodes={
     *      200="Returned when successful",
     *      400="Returned on invalid request",
     *      404="Returned when room with given id does not exist"
     *   }
     * )
     */
    public function postCalendarsEventAction($calendarId, Request $request)
    {
        $calendarIntegration = new CalendarIntergration();

        // todo: check if calendar exists, else 404

        $eventData = $this->retrieveEventData($request);
        $this->validateEventData($eventData);

        $event = new Event(
            $eventData['title'],
            new \DateTime('@'.$eventData['startTime'], new \DateTimeZone('Europe/Warsaw')),
            new \DateTime('@'.$eventData['endTime'], new \DateTimeZone('Europe/Warsaw'))
        );
        if(!empty($eventData['description'])) {
            $event->setLocation($eventData['description']);
        }
        if(!empty($eventData['location'])) {
            $event->setLocation($eventData['location']);
        }

        try {
            $calendarIntegration->createEvent($calendarId, $event);
        } catch(\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

        $view = $this->view(null, 204);
        return $this->handleView($view);
    }

    private function retrieveEventData(Request $request)
    {
        return [
            'title' => $request->get('title'),
            'startTime' => $request->get('startTime'),
            'endTime' => $request->get('endTime'),
            'description' => $request->get('description'),
            'location' => $request->get('location'),
        ];
    }

    private function validateEventData($eventData)
    {
        if (empty($eventData['title'])) {
            throw new HttpException(400, 'Missing required parameters: title');
        }
        if (empty($eventData['startTime'])) {
            throw new HttpException(400, 'Missing required parameters: startTime');
        }
        if (empty($eventData['endTime'])) {
            throw new HttpException(400, 'Missing required parameters: endTime');
        }
    }
}