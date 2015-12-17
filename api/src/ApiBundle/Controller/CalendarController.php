<?php

namespace ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\QueryParam;

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
        $calendars = ['Schopenhauer', 'Hevelius', 'Farenheit'];

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
        $events['hev'] = ['Rekrutacja', 'Daily Lendo', 'Daily NWT'];
        $events['far'] = ['Fifka', 'Management Board', 'Breakfast'];
        $events['sch'] = ['Wypowiedzenie', 'Blackboard session'];

        if(!in_array($calendarId, ['hev', 'far', 'sch'])) {
            throw new HttpException(404, 'Room does not exist');
        }

        $view = $this->view($events[$calendarId], 200);
        return $this->handleView($view);
    }

}