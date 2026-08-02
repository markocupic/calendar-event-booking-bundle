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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\OptIn\OptInTokenCreator;

class OptInTokenCreatorTest extends ContaoTestCase
{
    public function testGenerateTokenHasExpectedFormat(): void
    {
        $token = OptInTokenCreator::generateToken();

        $this->assertMatchesRegularExpression('/^cebb-[0-9a-f]{19}$/', $token);
        $this->assertSame(24, \strlen($token));
    }

    public function testGenerateTokenIsRandom(): void
    {
        $this->assertNotSame(OptInTokenCreator::generateToken(), OptInTokenCreator::generateToken());
    }

    public function testCreatePersistsOptInModelAndReturnsToken(): void
    {
        $related = [CalendarEventsMemberModel::getTable() => [7]];

        $model = $this->mockClassWithProperties(OptInModel::class, ['token' => 'cebb-000000000000000']);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $model
            ->expects($this->once())
            ->method('setRelatedRecords')
            ->with($related)
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->method('createInstance')
            ->with(OptInModel::class)
            ->willReturn($model)
        ;

        $creator = new OptInTokenCreator($framework);

        $result = $creator->create('cebb-000000000000000', 123456, 'a@example.com', 'Subject', 'Text', $related);

        $this->assertInstanceOf(OptInTokenInterface::class, $result);
        $this->assertSame('cebb-000000000000000', $model->token);
        $this->assertSame('a@example.com', $model->email);
        $this->assertSame('Subject', $model->emailSubject);
        $this->assertSame('Text', $model->emailText);
        $this->assertSame(123456, $model->removeOn);
    }
}
