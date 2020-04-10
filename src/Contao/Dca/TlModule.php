<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Contao\Dca;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class TlModule
 * @package Markocupic\CalendarEventBookingBundle\Contao\Dca
 */
class TlModule extends Backend
{

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * TlModule constructor.
     * @param ContaoFramework $framework
     * @param Connection $connection
     * @param string $projectDir
     * @param Security $security
     * @param RequestStack $requestStack
     * @param ScopeMatcher $scopeMatcher
     */
    public function __construct(ContaoFramework $framework, Connection $connection, string $projectDir, Security $security, RequestStack $requestStack, ScopeMatcher $scopeMatcher)
    {
        parent::__construct();

        $this->framework = $framework;
        $this->connection = $connection;
        $this->projectDir = $projectDir;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * @return array
     */
    public function getCalendarEventBookingMemberListTemplate(): array
    {
        return $this->getTemplateGroup('mod_calendar_event_booking_member_list');
    }

    /**
     * @return array
     */
    public function getCalendarEventBookingMemberListPartialTemplate(): array
    {
        return $this->getTemplateGroup('calendar_event_booking_member_list_partial');
    }

}
