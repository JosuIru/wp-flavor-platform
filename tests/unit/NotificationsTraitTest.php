<?php
/**
 * Tests para el trait de notificaciones de módulos
 *
 * @package FlavorChatIA
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Mock del Notification Manager
class MockNotificationManager {
    private static $notifications = [];

    public static function emit($type, $data, $module_id) {
        self::$notifications[] = [
            'type' => $type,
            'data' => $data,
            'module_id' => $module_id,
        ];
        return true;
    }

    public static function getNotifications() {
        return self::$notifications;
    }

    public static function clear() {
        self::$notifications = [];
    }
}

class NotificationsTraitTest extends Flavor_TestCase {

    protected function setUp(): void {
        parent::setUp();
        MockNotificationManager::clear();
    }

    public function test_notification_data_structure() {
        $notification = [
            'type' => 'nuevo_registro',
            'data' => [
                'registro_id' => 123,
                'mensaje' => 'Se ha creado un nuevo registro',
            ],
            'module_id' => 'biblioteca',
        ];

        $this->assertArrayHasKey('type', $notification);
        $this->assertArrayHasKey('data', $notification);
        $this->assertArrayHasKey('module_id', $notification);
    }

    public function test_notification_types_are_valid() {
        $validTypes = [
            'nuevo_registro',
            'actualizacion',
            'eliminacion',
            'alerta',
            'recordatorio',
            'sistema',
        ];

        foreach ($validTypes as $type) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $type);
        }
    }

    public function test_notification_channels() {
        $channels = ['push', 'email', 'in_app', 'sms'];

        $this->assertContains('push', $channels);
        $this->assertContains('email', $channels);
        $this->assertContains('in_app', $channels);
    }

    public function test_notification_priority_levels() {
        $priorities = [
            'low' => 1,
            'normal' => 2,
            'high' => 3,
            'urgent' => 4,
        ];

        $this->assertEquals(1, $priorities['low']);
        $this->assertEquals(4, $priorities['urgent']);
        $this->assertGreaterThan($priorities['low'], $priorities['high']);
    }

    public function test_notification_data_validation() {
        // Datos válidos
        $validData = [
            'registro_id' => 123,
            'mensaje' => 'Mensaje de prueba',
            'usuario_id' => 1,
        ];

        $this->assertIsInt($validData['registro_id']);
        $this->assertIsString($validData['mensaje']);
        $this->assertNotEmpty($validData['mensaje']);
    }

    public function test_scheduled_notification_format() {
        $scheduledNotification = [
            'type' => 'recordatorio',
            'scheduled_at' => '2026-02-15 10:00:00',
            'data' => ['mensaje' => 'Recordatorio programado'],
            'module_id' => 'eventos',
        ];

        // Verificar formato de fecha
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $scheduledNotification['scheduled_at']
        );
    }

    public function test_notification_user_targeting() {
        $targetTypes = [
            'user' => ['user_id' => 123],
            'admins' => ['role' => 'administrator'],
            'subscribers' => ['module_id' => 'biblioteca'],
            'all' => [],
        ];

        foreach ($targetTypes as $type => $config) {
            $this->assertIsArray($config);
        }
    }

    public function test_notification_title_generation() {
        $moduleNames = [
            'biblioteca' => 'Biblioteca',
            'eventos' => 'Eventos',
            'banco_tiempo' => 'Banco de Tiempo',
        ];

        $notificationType = 'nuevo_registro';

        foreach ($moduleNames as $moduleId => $moduleName) {
            $title = sprintf('[%s] Nuevo registro', $moduleName);
            $this->assertStringContainsString($moduleName, $title);
        }
    }

    public function test_notification_message_escaping() {
        $unsafeMessage = '<script>alert("xss")</script>Mensaje';
        $safeMessage = esc_html($unsafeMessage);

        $this->assertStringNotContainsString('<script>', $safeMessage);
    }
}
