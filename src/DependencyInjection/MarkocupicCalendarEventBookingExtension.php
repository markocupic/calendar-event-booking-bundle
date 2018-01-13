<?php

/**
 * @copyright  Marko Cupic 2018
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

namespace Markocupic\CalendarEventBookingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


/**
 * Class MarkocupicCalendarEventBookingExtension
 * @package Markocupic\CalendarEventBookingBundle\DependencyInjection
 */
class MarkocupicCalendarEventBookingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        // Load Sensitive outsourced data
        //require_once (__DIR__.'/../../../../../constants.php');

        $loader->load('parameters.yml');
        $loader->load('listener.yml');
        $loader->load('services.yml');
    }
}
