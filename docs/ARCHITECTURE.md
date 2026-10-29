# Architecture and scope

Symfony 7.4 monolith; Doctrine ORM for content/accounts; DBAL for scheduling; Twig/EasyAdmin administration; self-hosted GrapesJS visual editor. SQLite-first local distribution. One fictional practice, one practitioner, bilingual content. Independent frontend communicates through a versioned public read API.

Booking stores UTC timestamps and presents Europe/Vienna (configurable BOOKING_TIMEZONE). Availability is weekly working days and one opening interval, plus blocked dates. Two fixed appointment types use 15-minute slot increments, one-hour lead time and 60-day horizon. Appointment duration and interval overlap are checked again server-side while a database write lock serializes booking transactions. Cancelled appointments no longer occupy time. Cancellation links use random 256-bit tokens with only SHA-256 hashes stored; GET displays a form, CSRF-protected POST changes state.

Appointments are immediately confirmed demo bookings, not verified patient requests. Confirmation email is sent after commit. If mail fails the booking remains recorded: the UI explicitly warns not to retry blindly. There is no production email outbox/retry worker yet. Calendar and attendee details are restricted to administrators; editors can manage content only. No public appointment list/API exists.

No medical records, symptom intake, payments, prescriptions, Google Calendar OAuth, multiple practitioners, recurring appointments, reminder jobs, holiday calendars, rescheduling, or multi-tenant SaaS. Existing future bookings remain when availability changes; administrators must review/cancel these manually. Administrator cancellation currently does not send an email. The interface identifies this as a fictional demo.

This is a portfolio foundation, not a certified medical scheduling system. A real deployment requires privacy/legal review, retention policy, backups/restore tests, monitoring, verified booking/email flows, deliverability, dependency maintenance, stronger abuse controls and hosting review. Security is layered and testable, not guaranteed unbreakable.
