<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MarkocupicCalendarEventBookingExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.yaml');

        // Cron settings: aut delete auto expire
        $container->setParameter($this->getAlias().'.auto_expire_reserved_bookings', $config['auto_expire_reserved_bookings']);
        $container->setParameter($this->getAlias().'.auto_expire_time_limit', $config['auto_expire_time_limit']);
        $container->setParameter($this->getAlias().'.auto_delete_expired_bookings', $config['auto_delete_expired_bookings']);
        $container->setParameter($this->getAlias().'.auto_delete_canceled_bookings', $config['auto_delete_canceled_bookings']);
        $container->setParameter($this->getAlias().'.auto_waiting_list_promotion', $config['auto_waiting_list_promotion']);

        // notification
        $container->setParameter($this->getAlias().'.notification.log.exclude', $config['notification']['log']['exclude']);

        // rate limit
        $container->setParameter($this->getAlias().'.rate_limit.event_booking_form.enable', $config['rate_limit']['event_booking_form']['enable']);
        $container->setParameter($this->getAlias().'.rate_limit.event_booking_form.policy', $config['rate_limit']['event_booking_form']['policy']);
        $container->setParameter($this->getAlias().'.rate_limit.event_booking_form.limit', $config['rate_limit']['event_booking_form']['limit']);
        $container->setParameter($this->getAlias().'.rate_limit.event_booking_form.interval', $config['rate_limit']['event_booking_form']['interval']);

        // member list export
        $container->setParameter($this->getAlias().'.member_list_export.enable_output_conversion', $config['member_list_export']['enable_output_conversion']);
        $container->setParameter($this->getAlias().'.member_list_export.convert_from', $config['member_list_export']['convert_from']);
        $container->setParameter($this->getAlias().'.member_list_export.convert_to', $config['member_list_export']['convert_to']);
    }
}
