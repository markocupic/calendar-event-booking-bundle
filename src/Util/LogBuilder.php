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

namespace Markocupic\CalendarEventBookingBundle\Util;

/**
 * Builds the log column of a record: what the system has to say about this one
 * booking, order or payment, in the order it had to say it.
 *
 * The records that carry a log carry two text columns, and the division between
 * them is the point. "notes" belongs to whoever opens the record - it is where
 * somebody writes "refunded by hand on 22.8., see the mail from the customer", and
 * no code in these bundles writes a character into it. "log" belongs to the system
 * and is read-only in the backend. Nobody has to share a field with a cron job, and
 * no cron job has to be careful about a sentence a person is in the middle of
 * writing.
 *
 * What belongs here rather than in Contao's own log: this is the story of one
 * record, for whoever opens that record later - opted in on the 12th, unsubscribed
 * on the 4th, fee reported six minutes after the booking. tl_log is for whoever is
 * watching the system today, and it is rotated away long before anyone asks. Lines
 * that only make sense in aggregate ("completed the settlement data of 50
 * payments") stay there and do not belong here.
 *
 * Four rules, and they are what makes a log worth reading rather than merely worth
 * writing:
 *
 * Appending is the only operation. A record that says the fee was unknown and then
 * says it arrived is the history of that payment; a record that only ever shows the
 * latest sentence is a status field, and there is one of those already in the row.
 *
 * Only a state change is worth a line. An opt-in, an unsubscription, a fee that
 * finally arrived. "Nothing has happened yet" is not news, and a job that runs every
 * five minutes would put four hundred lines a day into the record of one stuck
 * payment - which is how a log stops being read.
 *
 * Nothing ever reads this column back. Not a substring, not a regex, not "does it
 * still contain our sentence". Whatever a machine has to decide is decided from the
 * columns next to it, which are typed and unambiguous; the moment code recognises
 * its own wording here, this text can no longer be improved without breaking it.
 *
 * The lines are English, always, and not translated. This is stored data, not a
 * label: it is written once and shown unchanged forever after, so it cannot be
 * re-rendered in the reader's language the way a DCA label can. Taking the language
 * from the ambient locale would decide it by accident anyway - one line is written
 * in a frontend request, where the locale is the page language, and the next one by
 * a cron job, which has no page and falls back to the framework's default. One
 * record, two languages, and nobody able to say why.
 */
final class LogBuilder
{
    /**
     * Deliberately to the minute. The exact second of a log line is never the
     * question anyone has, and a shorter stamp keeps the line readable.
     */
    public const string TIME_FORMAT = 'Y-m-d H:i';

    private function __construct()
    {
    }

    /**
     * Return the log with one line appended.
     *
     * Deliberately a string in, a string out: it does not take the model and it does
     * not save. A helper that saved would decide for its caller when the write
     * happens, and inside a transaction that is not a detail.
     *
     * What is already there is kept verbatim apart from trailing whitespace, and the
     * new line goes underneath it: oldest at the top, like every other log. What one
     * wants from this column is the sequence - booked incomplete, completed six
     * minutes later, refunded in March - and a sequence read from the bottom up is a
     * sequence one has to reassemble.
     *
     * The message itself is folded onto a single line, so that one entry is one line
     * and a reader can see at a glance how often something happened.
     *
     * @param int|null $time the moment to stamp, defaults to now. The timezone is
     *                       PHP's default, which Contao sets from the system settings while the
     *                       framework boots - in a request and in the cron alike.
     */
    public static function append(string $log, string $message, int|null $time = null): string
    {
        $message = trim((string) preg_replace('/\s+/', ' ', $message));

        // Nothing to say, nothing to write. A caller that builds its message from
        // data must not be able to leave an empty timestamp behind.
        if ('' === $message) {
            return $log;
        }

        $line = date(self::TIME_FORMAT, $time ?? time()).' '.$message;
        $log = rtrim($log);

        return '' === $log ? $line : $log."\n".$line;
    }
}
