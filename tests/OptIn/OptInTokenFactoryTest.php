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

namespace Markocupic\CalendarEventBookingBundle\Tests\OptIn;

use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\OptIn\OptInTokenFactory;

class OptInTokenFactoryTest extends ContaoTestCase
{
    public function testCreateReturnsOptInTokenForModel(): void
    {
        $framework = $this->createContaoFrameworkStub();
        $model = $this->createClassWithPropertiesStub(OptInModel::class, ['token' => 'cebb-abcdef']);

        $factory = new OptInTokenFactory($framework);
        $token = $factory->create($model);

        $this->assertInstanceOf(OptInTokenInterface::class, $token);
        $this->assertSame('cebb-abcdef', $token->getIdentifier());
    }
}
