INSERT INTO `tl_form_field` (`pid`, `sorting`, `tstamp`, `extensions`, `type`, `label`, `name`, `options`, `mandatory`, `rgxp`, `maxlength`, `size`, `class`, `tabindex`, `slabel`) VALUES
(##pid##, 128, ##tstamp##, '##extensions##', 'text', 'Vorname', 'firstname', NULL, '1', 'alpha', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-firstname', 1, ''),
(##pid##, 256, ##tstamp##, '##extensions##', 'text', 'Name', 'lastname', NULL, '1', 'alpha', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-lastname', 2, ''),
(##pid##, 456, ##tstamp##, '##extensions##', 'text', 'Geburtsdatum', 'dateOfBirth', NULL, '1', 'date', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-dateOfBirth', 6, ''),
(##pid##, 464, ##tstamp##, '##extensions##', 'text', 'E-Mail-Adresse', 'email', NULL, '1', 'email', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-email', 7, ''),
(##pid##, 512, ##tstamp##, '##extensions##', 'submit', '', '', NULL, '', '', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-submit', 11, 'Buchungsanfrage absenden'),
(##pid##, 64, ##tstamp##, '##extensions##', 'select', 'Anrede', 'gender', 0x613a323a7b693a303b613a333a7b733a353a2276616c7565223b733a363a2266656d616c65223b733a353a226c6162656c223b733a343a2246726175223b733a373a2264656661756c74223b733a313a2231223b7d693a313b613a323a7b733a353a2276616c7565223b733a343a226d616c65223b733a353a226c6162656c223b733a343a2248657272223b7d7d, '1', '', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-gender', 0, ''),
(##pid##, 352, ##tstamp##, '##extensions##', 'text', 'Strasse', 'street', NULL, '1', 'alnum', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-street', 3, ''),
(##pid##, 400, ##tstamp##, '##extensions##', 'text', 'PLZ', 'postal', NULL, '1', 'digit', 99999, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-postal', 4, ''),
(##pid##, 448, ##tstamp##, '##extensions##', 'text', 'Ort', 'city', NULL, '1', 'alnum', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-city', 5, ''),
(##pid##, 504, ##tstamp##, '##extensions##', 'textarea', 'Anmerkungen', 'notes', NULL, '', '', 0, 'a:2:{i:0;s:1:\"8\";i:1;s:2:\"40\";}', 'form-cal-event-booking-notes', 10, ''),
(##pid##, 480, ##tstamp##, '##extensions##', 'text', 'Telefonnummer', 'phone', NULL, '1', 'phone', 0, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-phone', 8, ''),
(##pid##, 496, ##tstamp##, '##extensions##', 'text', 'Anzahl Begleitpersonen', 'escorts', NULL, '1', 'digit', 3, 'a:2:{i:0;i:4;i:1;i:40;}', 'form-cal-event-booking-escorts', 9, '');
