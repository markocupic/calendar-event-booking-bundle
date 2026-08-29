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

namespace Markocupic\CalendarEventBookingBundle\Tests\Util;

use Markocupic\CalendarEventBookingBundle\Util\LogBuilder;
use PHPUnit\Framework\TestCase;

class LogBuilderTest extends TestCase
{
    /**
     * 2026-08-29 21:10:59 UTC. The seconds are in there on purpose - they must not
     * show up in the line.
     */
    private const TIME = 1788037859;

    private string $timezone;

    /**
     * The lines are stamped in PHP's default timezone, which on a Contao
     * installation is the one configured in the system settings. Here it has to be
     * pinned, or the expected strings below would depend on the php.ini of whoever
     * runs the suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);

        parent::tearDown();
    }

    public function testTheFirstLineStandsOnItsOwn(): void
    {
        $this->assertSame(
            '2026-08-29 21:10 The fee is not known yet.',
            LogBuilder::append('', 'The fee is not known yet.', self::TIME),
        );
    }

    public function testTheNextLineGoesUnderneath(): void
    {
        $log = LogBuilder::append('', 'The fee is not known yet.', self::TIME);
        $log = LogBuilder::append($log, 'The fee has been reported.', self::TIME + 600);

        $this->assertSame(
            "2026-08-29 21:10 The fee is not known yet.\n2026-08-29 21:20 The fee has been reported.",
            $log,
        );
    }

    /**
     * The one guarantee the whole design rests on. Every line that was in the column
     * is still in it afterwards, in the order it was written.
     */
    public function testTheHistorySurvivesVerbatim(): void
    {
        $existing = "2026-08-01 09:00 First.\n2026-08-02 09:00 Second.";

        $this->assertStringStartsWith($existing, LogBuilder::append($existing, 'Third.', self::TIME));
    }

    /**
     * A column that arrived with blank lines at the end - from an earlier version of
     * this code, or from a hand-run update. They are the only thing that goes, and
     * only at the end, so the log does not drift down the field one gap at a time.
     */
    public function testTrailingWhitespaceIsAbsorbed(): void
    {
        $this->assertSame(
            "Checked.\n2026-08-29 21:10 Anything.",
            LogBuilder::append("Checked.\n\n  \n", 'Anything.', self::TIME),
        );
    }

    /**
     * One entry, one line. A message that arrives with newlines in it - because it
     * was built from an api response, or wrapped in the source - would otherwise put
     * untimestamped lines into the log that look like separate entries.
     */
    public function testAMessageIsFoldedOntoASingleLine(): void
    {
        $this->assertSame(
            '2026-08-29 21:10 One entry only.',
            LogBuilder::append('', "One\n\tentry    only.", self::TIME),
        );
    }

    /**
     * A caller that builds its message from data can end up with nothing to say. A
     * bare timestamp in the column would read as if something had happened.
     */
    public function testAnEmptyMessageWritesNothing(): void
    {
        $this->assertSame('Untouched.', LogBuilder::append('Untouched.', '   ', self::TIME));
        $this->assertSame('', LogBuilder::append('', '', self::TIME));
    }

    /**
     * Without an explicit time the line is stamped now - which is what every caller
     * in the bundles does.
     */
    public function testTheTimeDefaultsToNow(): void
    {
        $this->assertStringStartsWith(date('Y-m-d H:i'), LogBuilder::append('', 'Now.'));
    }
}
