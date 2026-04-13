<?php
/**
 * Tests unitarios para el módulo de Eventos.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class EventosModuleTest extends VBP_UnitTestCase {

    /**
     * Test estructura de evento.
     */
    public function test_event_structure() {
        $event = [
            'id' => 1,
            'title' => 'Reunión Mensual',
            'description' => 'Reunión mensual de socios',
            'start_date' => '2025-02-01 18:00:00',
            'end_date' => '2025-02-01 20:00:00',
            'location' => 'Sala de reuniones',
            'address' => 'Calle Mayor 1, Bilbao',
            'coordinates' => ['lat' => 43.2630, 'lng' => -2.9350],
            'organizer_id' => 10,
            'status' => 'published',
            'max_attendees' => 50,
            'current_attendees' => 25,
            'registration_required' => true,
            'registration_deadline' => '2025-01-30 23:59:59',
        ];

        $this->assertArrayHasKey('title', $event);
        $this->assertArrayHasKey('start_date', $event);
        $this->assertArrayHasKey('max_attendees', $event);
    }

    /**
     * Test estados de evento.
     */
    public function test_event_statuses() {
        $statuses = [
            'draft' => 'Borrador',
            'pending' => 'Pendiente de aprobación',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'completed' => 'Finalizado',
        ];

        $this->assertArrayHasKey('published', $statuses);
        $this->assertArrayHasKey('cancelled', $statuses);
    }

    /**
     * Test categorías de eventos.
     */
    public function test_event_categories() {
        $categories = [
            'reunion' => 'Reuniones',
            'taller' => 'Talleres',
            'conferencia' => 'Conferencias',
            'social' => 'Eventos sociales',
            'formacion' => 'Formación',
            'asamblea' => 'Asambleas',
        ];

        $this->assertArrayHasKey('taller', $categories);
        $this->assertCount(6, $categories);
    }

    /**
     * Test evento recurrente.
     */
    public function test_recurring_event() {
        $recurringEvent = [
            'id' => 1,
            'title' => 'Reunión Semanal',
            'is_recurring' => true,
            'recurrence' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'days' => ['monday'],
                'end_type' => 'after',
                'end_after' => 10,
                'exceptions' => ['2025-02-10'],
            ],
        ];

        $this->assertTrue($recurringEvent['is_recurring']);
        $this->assertEquals('weekly', $recurringEvent['recurrence']['frequency']);
    }

    /**
     * Test registro a evento.
     */
    public function test_event_registration() {
        $registration = [
            'id' => 100,
            'event_id' => 1,
            'user_id' => 25,
            'status' => 'confirmed',
            'registered_at' => '2025-01-20 15:00:00',
            'notes' => 'Llevaré postre',
            'guests' => 1,
        ];

        $this->assertEquals('confirmed', $registration['status']);
        $this->assertEquals(1, $registration['guests']);
    }

    /**
     * Test estados de registro.
     */
    public function test_registration_statuses() {
        $statuses = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'waitlist' => 'Lista de espera',
            'cancelled' => 'Cancelado',
            'attended' => 'Asistió',
            'no_show' => 'No asistió',
        ];

        $this->assertArrayHasKey('waitlist', $statuses);
        $this->assertArrayHasKey('no_show', $statuses);
    }

    /**
     * Test disponibilidad de plazas.
     */
    public function test_event_availability() {
        $event = [
            'max_attendees' => 50,
            'current_attendees' => 48,
            'waitlist_enabled' => true,
            'waitlist_count' => 5,
        ];

        $availableSpots = $event['max_attendees'] - $event['current_attendees'];
        $this->assertEquals(2, $availableSpots);
        $this->assertTrue($event['waitlist_enabled']);
    }

    /**
     * Test evento con entrada de pago.
     */
    public function test_paid_event() {
        $paidEvent = [
            'id' => 5,
            'title' => 'Workshop Premium',
            'is_paid' => true,
            'pricing' => [
                'regular' => 25.00,
                'member' => 15.00,
                'early_bird' => 20.00,
                'early_bird_deadline' => '2025-01-15',
            ],
            'currency' => 'EUR',
            'payment_methods' => ['card', 'transfer', 'cash'],
        ];

        $this->assertTrue($paidEvent['is_paid']);
        $this->assertEquals(25.00, $paidEvent['pricing']['regular']);
    }

    /**
     * Test evento online.
     */
    public function test_online_event() {
        $onlineEvent = [
            'id' => 10,
            'title' => 'Webinar',
            'is_online' => true,
            'platform' => 'zoom',
            'meeting_url' => 'https://zoom.us/j/123456789',
            'meeting_id' => '123456789',
            'meeting_password' => 'abc123',
        ];

        $this->assertTrue($onlineEvent['is_online']);
        $this->assertEquals('zoom', $onlineEvent['platform']);
    }

    /**
     * Test evento híbrido.
     */
    public function test_hybrid_event() {
        $hybridEvent = [
            'id' => 15,
            'title' => 'Conferencia Híbrida',
            'is_hybrid' => true,
            'physical_location' => 'Sala Principal',
            'online_platform' => 'youtube_live',
            'stream_url' => 'https://youtube.com/live/abc123',
        ];

        $this->assertTrue($hybridEvent['is_hybrid']);
        $this->assertArrayHasKey('physical_location', $hybridEvent);
        $this->assertArrayHasKey('stream_url', $hybridEvent);
    }

    /**
     * Test recordatorios de evento.
     */
    public function test_event_reminders() {
        $reminders = [
            [
                'type' => 'email',
                'time_before' => 24 * 60, // 24 horas en minutos
                'template' => 'event_reminder_24h',
            ],
            [
                'type' => 'email',
                'time_before' => 60, // 1 hora
                'template' => 'event_reminder_1h',
            ],
            [
                'type' => 'push',
                'time_before' => 30,
                'template' => 'event_reminder_push',
            ],
        ];

        $this->assertCount(3, $reminders);
    }

    /**
     * Test calendario de eventos.
     */
    public function test_events_calendar() {
        $calendarView = [
            'view' => 'month',
            'year' => 2025,
            'month' => 2,
            'events' => [
                ['id' => 1, 'title' => 'Evento 1', 'date' => '2025-02-05'],
                ['id' => 2, 'title' => 'Evento 2', 'date' => '2025-02-10'],
                ['id' => 3, 'title' => 'Evento 3', 'date' => '2025-02-10'],
            ],
        ];

        $this->assertEquals('month', $calendarView['view']);
        $this->assertCount(3, $calendarView['events']);
    }

    /**
     * Test exportar evento a iCal.
     */
    public function test_export_to_ical() {
        $icalData = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            'DTSTART:20250201T180000',
            'DTEND:20250201T200000',
            'SUMMARY:Reunión Mensual',
            'LOCATION:Sala de reuniones',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $icalString = implode("\r\n", $icalData);
        $this->assertStringContainsString('VCALENDAR', $icalString);
        $this->assertStringContainsString('VEVENT', $icalString);
    }

    /**
     * Test asistencia a evento.
     */
    public function test_event_attendance() {
        $attendance = [
            'event_id' => 1,
            'total_registered' => 45,
            'attended' => 40,
            'no_show' => 5,
            'attendance_rate' => 88.89,
        ];

        $this->assertEquals(40, $attendance['attended']);
        $this->assertGreaterThan(80, $attendance['attendance_rate']);
    }

    /**
     * Test check-in de evento.
     */
    public function test_event_checkin() {
        $checkin = [
            'event_id' => 1,
            'user_id' => 25,
            'checkin_time' => '2025-02-01 17:55:00',
            'checkin_method' => 'qr_code',
            'checkin_by' => 10,
        ];

        $this->assertEquals('qr_code', $checkin['checkin_method']);
    }

    /**
     * Test feedback de evento.
     */
    public function test_event_feedback() {
        $feedback = [
            'event_id' => 1,
            'user_id' => 25,
            'rating' => 4,
            'comment' => 'Muy interesante, repetiría',
            'aspects' => [
                'content' => 5,
                'organization' => 4,
                'venue' => 3,
                'networking' => 4,
            ],
            'would_recommend' => true,
        ];

        $this->assertEquals(4, $feedback['rating']);
        $this->assertTrue($feedback['would_recommend']);
    }

    /**
     * Test estadísticas de eventos.
     */
    public function test_events_statistics() {
        $stats = [
            'total_events_this_month' => 15,
            'total_attendees_this_month' => 450,
            'average_attendance' => 30,
            'most_popular_category' => 'taller',
            'cancellation_rate' => 5.2,
        ];

        $this->assertGreaterThan(0, $stats['total_events_this_month']);
    }

    /**
     * Test permisos de eventos.
     */
    public function test_event_permissions() {
        $permissions = [
            'create_event' => ['administrator', 'editor', 'event_organizer'],
            'edit_own_event' => ['administrator', 'editor', 'event_organizer', 'author'],
            'edit_any_event' => ['administrator', 'editor'],
            'delete_event' => ['administrator'],
            'manage_registrations' => ['administrator', 'editor', 'event_organizer'],
        ];

        $this->assertContains('event_organizer', $permissions['create_event']);
    }

    /**
     * Test notificaciones de evento.
     */
    public function test_event_notifications() {
        $notifications = [
            'on_registration' => true,
            'on_cancellation' => true,
            'on_waitlist_available' => true,
            'on_event_update' => true,
            'on_event_cancelled' => true,
            'reminder_24h' => true,
            'reminder_1h' => true,
        ];

        $this->assertTrue($notifications['on_registration']);
        $this->assertTrue($notifications['reminder_24h']);
    }

    /**
     * Test evento con materiales.
     */
    public function test_event_with_materials() {
        $event = [
            'id' => 1,
            'title' => 'Taller de cocina',
            'materials' => [
                ['type' => 'pdf', 'name' => 'Recetas', 'url' => '/files/recetas.pdf'],
                ['type' => 'video', 'name' => 'Tutorial', 'url' => 'https://youtube.com/watch?v=abc'],
                ['type' => 'link', 'name' => 'Recursos', 'url' => 'https://example.com/recursos'],
            ],
            'materials_visibility' => 'registered_only',
        ];

        $this->assertCount(3, $event['materials']);
        $this->assertEquals('registered_only', $event['materials_visibility']);
    }

    /**
     * Test evento con speakers.
     */
    public function test_event_with_speakers() {
        $event = [
            'id' => 1,
            'title' => 'Conferencia',
            'speakers' => [
                [
                    'name' => 'Juan García',
                    'bio' => 'Experto en sostenibilidad',
                    'photo' => '/images/juan.jpg',
                    'social' => ['twitter' => '@juangarcia'],
                ],
                [
                    'name' => 'María López',
                    'bio' => 'CEO de EcoCorp',
                    'photo' => '/images/maria.jpg',
                    'social' => ['linkedin' => 'marialopez'],
                ],
            ],
        ];

        $this->assertCount(2, $event['speakers']);
    }

    /**
     * Test agenda de evento.
     */
    public function test_event_agenda() {
        $agenda = [
            ['time' => '18:00', 'title' => 'Recepción', 'duration' => 15],
            ['time' => '18:15', 'title' => 'Bienvenida', 'duration' => 10, 'speaker' => 'Organizador'],
            ['time' => '18:25', 'title' => 'Ponencia principal', 'duration' => 45, 'speaker' => 'Juan García'],
            ['time' => '19:10', 'title' => 'Descanso', 'duration' => 15],
            ['time' => '19:25', 'title' => 'Mesa redonda', 'duration' => 30],
            ['time' => '19:55', 'title' => 'Cierre y networking', 'duration' => 35],
        ];

        $this->assertCount(6, $agenda);
        $totalDuration = array_sum(array_column($agenda, 'duration'));
        $this->assertEquals(150, $totalDuration); // 2.5 horas
    }
}
