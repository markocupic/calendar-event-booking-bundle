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

namespace Markocupic\CalendarEventBookingBundle\Exception;

class AbstractTranslatableException extends \RuntimeException implements TranslatableExceptionInterface
{
    protected string $translatedMessage = '';

    public function __construct(
        string $message,
        private SeverityLevel $severityLevel,
        protected readonly string $messageKey,
        protected readonly array $messageData = [],
        protected readonly string $messageDomain = 'contao_default',
        int $code = 0,
        \Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getSeverityLevel(): string
    {
        return $this->severityLevel->value;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getMessageData(): array
    {
        return $this->messageData;
    }

    public function getMessageDomain(): string
    {
        return $this->messageDomain;
    }

    public function setTranslatedMessage(string $translatedMessage): void
    {
        $this->translatedMessage = $translatedMessage;
    }

    public function getTranslatedMessage(): string
    {
        return $this->translatedMessage;
    }
}
