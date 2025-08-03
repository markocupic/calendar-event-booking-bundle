<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_KEY = 'markocupic_calendar_event_booking';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('auto_expire_reserved_bookings')
                    ->info('If set to true, unconfirmed bookings are expired after a configurable time has elapsed.')
                    ->defaultTrue()
                ->end()
                ->integerNode('auto_expire_time_limit')
                    ->info('The time in seconds Contao should wait until an unconfirmed booking is automatically expired by a cronjob.')
                    ->defaultValue(24 * 60 * 60)
                ->end()
                ->booleanNode('auto_delete_expired_bookings')
                    ->info('If set to true, expired bookings are deleted from the database automatically by a cronjob.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('auto_delete_canceled_bookings')
                    ->info('If set to true, canceled bookings are deleted from the database automatically by a cronjob.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('auto_delete_canceled_bookings')
                    ->info('If set to true, canceled bookings are deleted from the database automatically by a cronjob.')
                    ->defaultFalse()
                ->end()
                ->append($this->addRateLimitNode())
                ->append($this->addMemberListNode())
             ->end()
        ;

        return $treeBuilder;
    }

    private function addRateLimitNode(): NodeDefinition
    {
        return (new TreeBuilder('rate_limit'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('event_booking_form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('policy')->cannotBeEmpty()->defaultValue('fixed_window')->end()
                        ->integerNode('limit')->defaultValue(5)->end()
                        ->scalarNode('interval')->cannotBeEmpty()->defaultValue('15 minutes')->end()
                    ->end()
                ->end()
            ->end()
            ;
    }

    private function addMemberListNode(): NodeDefinition
    {
        return (new TreeBuilder('member_list_export'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enable_output_conversion')
                    ->defaultFalse()
                ->end()
                ->scalarNode('convert_from')
                    ->defaultValue('UTF-8')
                    ->cannotBeEmpty()
                    ->example('UTF-8')
                ->end()
                ->scalarNode('convert_to')
                    ->defaultValue('ISO-8859-1')
                    ->cannotBeEmpty()
                    ->info('Convert data uppon csv export to a specific charset e.g. ISO-8859-1.')
                    ->example('ISO-8859-1')
                ->end()
            ->end()
        ;
    }
}
