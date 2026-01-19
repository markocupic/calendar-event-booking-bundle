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
                    ->defaultValue(3600)
                ->end()
                ->booleanNode('auto_delete_expired_bookings')
                    ->info('If set to true, expired bookings are deleted from the database automatically by a cronjob.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('auto_delete_canceled_bookings')
                    ->info('If set to true, canceled bookings are deleted from the database automatically by a cronjob.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('auto_waiting_list_promotion')
                    ->info('If set to false, the automatic advancement from waiting list will be disabled.')
                    ->defaultTrue()
                ->end()
                ->append($this->addNotificationNode())
                ->append($this->addRateLimitNode())
                ->append($this->addMemberListNode())
             ->end()
        ;

        return $treeBuilder;
    }

    private function addNotificationNode(): NodeDefinition
    {
        return (new TreeBuilder('notification'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('log')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('exclude')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(static fn ($v) => !\is_string($v))
                                    ->thenInvalid('Each option must be a string.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
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
                        ->booleanNode('enable')->defaultTrue()->end()
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
                    ->info('Convert data upon csv export to a specific charset e.g. ISO-8859-1.')
                    ->example('ISO-8859-1')
                ->end()
            ->end()
        ;
    }
}
