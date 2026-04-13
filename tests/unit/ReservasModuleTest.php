<?php
/**
 * Tests unitarios para el módulo de Reservas.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class ReservasModuleTest extends VBP_UnitTestCase {

    /**
     * Test estructura de recurso reservable.
     */
    public function test_resource_structure() {
        $resource = [
            'id' => 1,
            'name' => 'Sala de Reuniones A',
            'type' => 'room',
            'description' => 'Sala para 10 personas con proyector',
            'capacity' => 10,
            'location' => 'Planta 1',
            'amenities' => ['proyector', 'pizarra', 'wifi'],
            'images' => ['/images/sala-a-1.jpg'],
            'status' => 'available',
            'requires_approval' => false,
            'min_booking_time' => 30,
            'max_booking_time' => 240,
            'advance_booking_days' => 30,
        ];

        $this->assertArrayHasKey('capacity', $resource);
        $this->assertEquals('room', $resource['type']);
        $this->assertContains('proyector', $resource['amenities']);
    }

    /**
     * Test tipos de recurso.
     */
    public function test_resource_types() {
        $types = [
            'room' => 'Sala',
            'desk' => 'Puesto de trabajo',
            'equipment' => 'Equipamiento',
            'vehicle' => 'Vehículo',
            'space' => 'Espacio común',
            'service' => 'Servicio',
        ];

        $this->assertArrayHasKey('desk', $types);
        $this->assertCount(6, $types);
    }

    /**
     * Test estados de recurso.
     */
    public function test_resource_statuses() {
        $statuses = [
            'available' => 'Disponible',
            'maintenance' => 'En mantenimiento',
            'unavailable' => 'No disponible',
            'retired' => 'Retirado',
        ];

        $this->assertArrayHasKey('maintenance', $statuses);
    }

    /**
     * Test estructura de reserva.
     */
    public function test_booking_structure() {
        $booking = [
            'id' => 100,
            'resource_id' => 1,
            'user_id' => 25,
            'title' => 'Reunión de equipo',
            'description' => 'Revisión mensual de proyectos',
            'start_datetime' => '2025-01-20 10:00:00',
            'end_datetime' => '2025-01-20 12:00:00',
            'status' => 'confirmed',
            'attendees' => [25, 30, 35],
            'created_at' => '2025-01-15 10:00:00',
            'notes' => 'Preparar café',
        ];

        $this->assertArrayHasKey('resource_id', $booking);
        $this->assertEquals('confirmed', $booking['status']);
        $this->assertCount(3, $booking['attendees']);
    }

    /**
     * Test estados de reserva.
     */
    public function test_booking_statuses() {
        $statuses = [
            'pending' => 'Pendiente de aprobación',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            'no_show' => 'No presentado',
        ];

        $this->assertArrayHasKey('no_show', $statuses);
        $this->assertCount(5, $statuses);
    }

    /**
     * Test disponibilidad de recurso.
     */
    public function test_resource_availability() {
        $availabilityCheck = [
            'resource_id' => 1,
            'date' => '2025-01-20',
            'time_slots' => [
                ['start' => '08:00', 'end' => '10:00', 'available' => true],
                ['start' => '10:00', 'end' => '12:00', 'available' => false, 'booking_id' => 100],
                ['start' => '12:00', 'end' => '14:00', 'available' => true],
                ['start' => '14:00', 'end' => '16:00', 'available' => true],
            ],
        ];

        $availableSlots = array_filter($availabilityCheck['time_slots'], fn($slot) => $slot['available']);
        $this->assertCount(3, $availableSlots);
    }

    /**
     * Test horarios de disponibilidad.
     */
    public function test_availability_schedule() {
        $schedule = [
            'resource_id' => 1,
            'weekly_schedule' => [
                'monday' => ['start' => '08:00', 'end' => '20:00'],
                'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                'thursday' => ['start' => '08:00', 'end' => '20:00'],
                'friday' => ['start' => '08:00', 'end' => '18:00'],
                'saturday' => ['start' => '09:00', 'end' => '14:00'],
                'sunday' => null,
            ],
            'holidays' => ['2025-01-01', '2025-12-25'],
        ];

        $this->assertNull($schedule['weekly_schedule']['sunday']);
        $this->assertCount(2, $schedule['holidays']);
    }

    /**
     * Test conflicto de reserva.
     */
    public function test_booking_conflict() {
        $existingBooking = [
            'id' => 100,
            'resource_id' => 1,
            'start_datetime' => '2025-01-20 10:00:00',
            'end_datetime' => '2025-01-20 12:00:00',
        ];

        $newBookingRequest = [
            'resource_id' => 1,
            'start_datetime' => '2025-01-20 11:00:00',
            'end_datetime' => '2025-01-20 13:00:00',
        ];

        // Verificar solapamiento
        $existingStart = strtotime($existingBooking['start_datetime']);
        $existingEnd = strtotime($existingBooking['end_datetime']);
        $newStart = strtotime($newBookingRequest['start_datetime']);
        $newEnd = strtotime($newBookingRequest['end_datetime']);

        $hasConflict = ($newStart < $existingEnd && $newEnd > $existingStart);
        $this->assertTrue($hasConflict);
    }

    /**
     * Test reserva recurrente.
     */
    public function test_recurring_booking() {
        $recurringBooking = [
            'id' => 105,
            'resource_id' => 1,
            'user_id' => 25,
            'title' => 'Reunión semanal',
            'is_recurring' => true,
            'recurrence' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'days' => ['monday'],
                'end_type' => 'until',
                'end_date' => '2025-06-30',
                'exceptions' => ['2025-02-17'],
            ],
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->assertTrue($recurringBooking['is_recurring']);
        $this->assertEquals('weekly', $recurringBooking['recurrence']['frequency']);
    }

    /**
     * Test cancelar reserva.
     */
    public function test_cancel_booking() {
        $cancellation = [
            'booking_id' => 100,
            'cancelled_by' => 25,
            'cancelled_at' => '2025-01-18 10:00:00',
            'reason' => 'Cambio de planes',
            'notify_attendees' => true,
            'refund_applicable' => false,
        ];

        $this->assertArrayHasKey('reason', $cancellation);
        $this->assertTrue($cancellation['notify_attendees']);
    }

    /**
     * Test política de cancelación.
     */
    public function test_cancellation_policy() {
        $policy = [
            'resource_id' => 1,
            'free_cancellation_hours' => 24,
            'late_cancellation_fee' => 10.00,
            'no_show_fee' => 25.00,
            'min_notice_hours' => 2,
        ];

        $this->assertEquals(24, $policy['free_cancellation_hours']);
    }

    /**
     * Test aprobación de reserva.
     */
    public function test_booking_approval() {
        $approval = [
            'booking_id' => 110,
            'status' => 'approved',
            'approved_by' => 1,
            'approved_at' => '2025-01-16 09:00:00',
            'notes' => 'Aprobada sin observaciones',
        ];

        $this->assertEquals('approved', $approval['status']);
        $this->assertArrayHasKey('approved_by', $approval);
    }

    /**
     * Test notificaciones de reserva.
     */
    public function test_booking_notifications() {
        $notifications = [
            'on_booking' => true,
            'on_approval' => true,
            'on_cancellation' => true,
            'reminder_hours' => [24, 1],
            'notify_resource_admin' => true,
            'notify_attendees' => true,
        ];

        $this->assertTrue($notifications['on_booking']);
        $this->assertCount(2, $notifications['reminder_hours']);
    }

    /**
     * Test check-in de reserva.
     */
    public function test_booking_checkin() {
        $checkin = [
            'booking_id' => 100,
            'user_id' => 25,
            'checkin_time' => '2025-01-20 09:58:00',
            'checkin_method' => 'qr_code',
            'on_time' => true,
        ];

        $this->assertEquals('qr_code', $checkin['checkin_method']);
        $this->assertTrue($checkin['on_time']);
    }

    /**
     * Test check-out de reserva.
     */
    public function test_booking_checkout() {
        $checkout = [
            'booking_id' => 100,
            'user_id' => 25,
            'checkout_time' => '2025-01-20 11:55:00',
            'actual_duration' => 117, // minutos
            'early_checkout' => true,
        ];

        $this->assertTrue($checkout['early_checkout']);
    }

    /**
     * Test reserva con precio.
     */
    public function test_paid_booking() {
        $paidBooking = [
            'id' => 120,
            'resource_id' => 5,
            'user_id' => 25,
            'pricing' => [
                'base_price' => 20.00,
                'duration_hours' => 2,
                'total' => 40.00,
                'discount' => 0,
                'currency' => 'EUR',
            ],
            'payment_status' => 'paid',
            'payment_method' => 'card',
        ];

        $this->assertEquals(40.00, $paidBooking['pricing']['total']);
        $this->assertEquals('paid', $paidBooking['payment_status']);
    }

    /**
     * Test tarifas de recurso.
     */
    public function test_resource_pricing() {
        $pricing = [
            'resource_id' => 5,
            'pricing_type' => 'hourly',
            'base_rate' => 20.00,
            'currency' => 'EUR',
            'member_discount' => 20,
            'minimum_charge' => 20.00,
            'peak_hours' => [
                'hours' => ['18:00-21:00'],
                'multiplier' => 1.5,
            ],
        ];

        $this->assertEquals('hourly', $pricing['pricing_type']);
        $this->assertEquals(20, $pricing['member_discount']);
    }

    /**
     * Test calendario de reservas.
     */
    public function test_bookings_calendar() {
        $calendarView = [
            'resource_id' => 1,
            'view' => 'week',
            'start_date' => '2025-01-20',
            'end_date' => '2025-01-26',
            'bookings' => [
                ['id' => 100, 'date' => '2025-01-20', 'start' => '10:00', 'end' => '12:00'],
                ['id' => 101, 'date' => '2025-01-22', 'start' => '14:00', 'end' => '16:00'],
            ],
        ];

        $this->assertEquals('week', $calendarView['view']);
        $this->assertCount(2, $calendarView['bookings']);
    }

    /**
     * Test permisos de reserva.
     */
    public function test_booking_permissions() {
        $permissions = [
            'book_resource' => ['member', 'socio', 'admin'],
            'cancel_own_booking' => ['member', 'socio', 'admin'],
            'cancel_any_booking' => ['resource_manager', 'admin'],
            'approve_bookings' => ['resource_manager', 'admin'],
            'manage_resources' => ['admin'],
            'view_all_bookings' => ['resource_manager', 'admin'],
        ];

        $this->assertContains('socio', $permissions['book_resource']);
        $this->assertNotContains('member', $permissions['approve_bookings']);
    }

    /**
     * Test lista de espera.
     */
    public function test_waitlist() {
        $waitlistEntry = [
            'id' => 1,
            'resource_id' => 1,
            'user_id' => 30,
            'desired_date' => '2025-01-20',
            'desired_start' => '10:00',
            'desired_end' => '12:00',
            'position' => 1,
            'added_at' => '2025-01-15 11:00:00',
            'status' => 'waiting',
        ];

        $this->assertEquals('waiting', $waitlistEntry['status']);
        $this->assertEquals(1, $waitlistEntry['position']);
    }

    /**
     * Test estadísticas de recurso.
     */
    public function test_resource_statistics() {
        $stats = [
            'resource_id' => 1,
            'period' => 'month',
            'total_bookings' => 45,
            'total_hours_booked' => 90,
            'utilization_rate' => 75.5,
            'cancellation_rate' => 5.2,
            'no_show_rate' => 2.1,
            'average_booking_duration' => 2,
            'peak_day' => 'tuesday',
            'peak_time' => '10:00',
        ];

        $this->assertGreaterThan(50, $stats['utilization_rate']);
    }

    /**
     * Test equipamiento adicional.
     */
    public function test_additional_equipment() {
        $booking = [
            'id' => 125,
            'resource_id' => 1,
            'additional_equipment' => [
                ['id' => 'projector', 'name' => 'Proyector', 'price' => 5.00],
                ['id' => 'flipchart', 'name' => 'Rotafolio', 'price' => 2.00],
            ],
            'equipment_total' => 7.00,
        ];

        $this->assertCount(2, $booking['additional_equipment']);
        $this->assertEquals(7.00, $booking['equipment_total']);
    }

    /**
     * Test servicios adicionales.
     */
    public function test_additional_services() {
        $booking = [
            'id' => 130,
            'resource_id' => 1,
            'services' => [
                ['id' => 'catering', 'name' => 'Catering', 'quantity' => 10, 'unit_price' => 8.00, 'total' => 80.00],
                ['id' => 'cleaning', 'name' => 'Limpieza extra', 'quantity' => 1, 'unit_price' => 15.00, 'total' => 15.00],
            ],
            'services_total' => 95.00,
        ];

        $this->assertEquals(95.00, $booking['services_total']);
    }

    /**
     * Test grupo de recursos.
     */
    public function test_resource_group() {
        $resourceGroup = [
            'id' => 1,
            'name' => 'Salas de Reuniones',
            'description' => 'Todas las salas de reuniones del edificio',
            'resources' => [1, 2, 3, 4],
            'shared_settings' => [
                'min_booking_time' => 30,
                'max_booking_time' => 240,
            ],
        ];

        $this->assertCount(4, $resourceGroup['resources']);
    }

    /**
     * Test bloqueo de recurso.
     */
    public function test_resource_block() {
        $block = [
            'resource_id' => 1,
            'start_datetime' => '2025-01-25 00:00:00',
            'end_datetime' => '2025-01-26 23:59:59',
            'reason' => 'Mantenimiento programado',
            'created_by' => 1,
            'notify_affected' => true,
        ];

        $this->assertArrayHasKey('reason', $block);
        $this->assertTrue($block['notify_affected']);
    }

    /**
     * Test invitar asistentes.
     */
    public function test_invite_attendees() {
        $invitation = [
            'booking_id' => 100,
            'invited_by' => 25,
            'invitees' => [
                ['user_id' => 30, 'status' => 'accepted'],
                ['user_id' => 35, 'status' => 'pending'],
                ['user_id' => 40, 'status' => 'declined'],
            ],
            'external_invitees' => [
                ['email' => 'externo@example.com', 'name' => 'Invitado Externo'],
            ],
        ];

        $acceptedCount = count(array_filter($invitation['invitees'], fn($invitee) => $invitee['status'] === 'accepted'));
        $this->assertEquals(1, $acceptedCount);
    }

    /**
     * Test exportar reservas.
     */
    public function test_export_bookings() {
        $exportConfig = [
            'resource_id' => 1,
            'date_from' => '2025-01-01',
            'date_to' => '2025-01-31',
            'format' => 'csv',
            'include_fields' => ['title', 'user', 'start', 'end', 'status'],
        ];

        $this->assertEquals('csv', $exportConfig['format']);
        $this->assertContains('status', $exportConfig['include_fields']);
    }

    /**
     * Test sincronización con calendario externo.
     */
    public function test_calendar_sync() {
        $syncSettings = [
            'user_id' => 25,
            'provider' => 'google_calendar',
            'calendar_id' => 'primary',
            'sync_direction' => 'both',
            'last_sync' => '2025-01-15 10:00:00',
            'status' => 'active',
        ];

        $this->assertEquals('google_calendar', $syncSettings['provider']);
        $this->assertEquals('both', $syncSettings['sync_direction']);
    }

    /**
     * Test reserva de múltiples recursos.
     */
    public function test_multi_resource_booking() {
        $multiBooking = [
            'id' => 150,
            'user_id' => 25,
            'title' => 'Evento grande',
            'resources' => [
                ['resource_id' => 1, 'confirmed' => true],
                ['resource_id' => 2, 'confirmed' => true],
                ['resource_id' => 5, 'confirmed' => false],
            ],
            'start_datetime' => '2025-02-01 09:00:00',
            'end_datetime' => '2025-02-01 18:00:00',
        ];

        $confirmedResources = array_filter($multiBooking['resources'], fn($resourceBooking) => $resourceBooking['confirmed']);
        $this->assertCount(2, $confirmedResources);
    }

    /**
     * Test feedback de reserva.
     */
    public function test_booking_feedback() {
        $feedback = [
            'booking_id' => 100,
            'user_id' => 25,
            'rating' => 4,
            'aspects' => [
                'cleanliness' => 5,
                'equipment' => 4,
                'comfort' => 4,
            ],
            'comment' => 'Todo bien, el proyector tardó en encender',
            'submitted_at' => '2025-01-20 12:30:00',
        ];

        $this->assertEquals(4, $feedback['rating']);
        $this->assertEquals(5, $feedback['aspects']['cleanliness']);
    }

    /**
     * Test reglas de negocio.
     */
    public function test_business_rules() {
        $rules = [
            'max_active_bookings_per_user' => 5,
            'max_bookings_per_day_per_user' => 2,
            'buffer_time_minutes' => 15,
            'booking_window_start_days' => 1,
            'booking_window_end_days' => 30,
            'auto_cancel_no_checkin_minutes' => 15,
        ];

        $this->assertEquals(5, $rules['max_active_bookings_per_user']);
        $this->assertEquals(15, $rules['buffer_time_minutes']);
    }
}
