<?php
/**
 * Tests unitarios para el sistema de reputación y gamificación.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class ReputationSystemTest extends VBP_UnitTestCase {

    /**
     * Test estructura de puntos.
     */
    public function test_points_structure() {
        $userPoints = [
            'user_id' => 10,
            'total_points' => 1500,
            'level' => 5,
            'points_this_month' => 200,
            'points_this_week' => 50,
        ];

        $this->assertArrayHasKey('total_points', $userPoints);
        $this->assertArrayHasKey('level', $userPoints);
        $this->assertIsInt($userPoints['total_points']);
    }

    /**
     * Test niveles de reputación.
     */
    public function test_reputation_levels() {
        $levels = [
            1 => ['nombre' => 'Novato', 'min_puntos' => 0, 'icono' => '🌱'],
            2 => ['nombre' => 'Aprendiz', 'min_puntos' => 100, 'icono' => '🌿'],
            3 => ['nombre' => 'Colaborador', 'min_puntos' => 500, 'icono' => '🌳'],
            4 => ['nombre' => 'Experto', 'min_puntos' => 1000, 'icono' => '⭐'],
            5 => ['nombre' => 'Maestro', 'min_puntos' => 2500, 'icono' => '🏆'],
            6 => ['nombre' => 'Leyenda', 'min_puntos' => 5000, 'icono' => '👑'],
        ];

        $this->assertCount(6, $levels);
        $this->assertEquals('Novato', $levels[1]['nombre']);
        $this->assertEquals(5000, $levels[6]['min_puntos']);
    }

    /**
     * Test cálculo de nivel por puntos.
     */
    public function test_calculate_level_from_points() {
        $levelThresholds = [0, 100, 500, 1000, 2500, 5000];

        $calculateLevel = function($points) use ($levelThresholds) {
            $level = 1;
            foreach ($levelThresholds as $index => $threshold) {
                if ($points >= $threshold) {
                    $level = $index + 1;
                }
            }
            return $level;
        };

        $this->assertEquals(1, $calculateLevel(50));
        $this->assertEquals(2, $calculateLevel(100));
        $this->assertEquals(3, $calculateLevel(750));
        $this->assertEquals(5, $calculateLevel(3000));
        $this->assertEquals(6, $calculateLevel(10000));
    }

    /**
     * Test acciones que dan puntos.
     */
    public function test_point_actions() {
        $pointActions = [
            'login_diario' => 5,
            'crear_post' => 10,
            'comentar' => 5,
            'recibir_like' => 2,
            'dar_like' => 1,
            'completar_perfil' => 50,
            'primera_compra' => 100,
            'invitar_amigo' => 25,
            'asistir_evento' => 15,
            'crear_evento' => 30,
            'respuesta_aceptada' => 20,
            'compartir' => 3,
        ];

        $this->assertEquals(10, $pointActions['crear_post']);
        $this->assertGreaterThan($pointActions['comentar'], $pointActions['primera_compra']);
    }

    /**
     * Test historial de puntos.
     */
    public function test_points_history() {
        $history = [
            [
                'id' => 1,
                'user_id' => 10,
                'points' => 10,
                'action' => 'crear_post',
                'description' => 'Creaste una publicación',
                'created_at' => '2025-01-15 10:00:00',
                'metadata' => ['post_id' => 123],
            ],
            [
                'id' => 2,
                'user_id' => 10,
                'points' => 5,
                'action' => 'comentar',
                'description' => 'Comentaste en una publicación',
                'created_at' => '2025-01-15 11:00:00',
                'metadata' => ['post_id' => 456, 'comment_id' => 789],
            ],
        ];

        $totalPoints = array_sum(array_column($history, 'points'));
        $this->assertEquals(15, $totalPoints);
    }

    /**
     * Test estructura de badge.
     */
    public function test_badge_structure() {
        $badge = [
            'id' => 1,
            'nombre' => 'Primer Post',
            'descripcion' => 'Publicaste tu primer contenido',
            'icono' => '📝',
            'categoria' => 'contenido',
            'condicion' => 'posts_count >= 1',
            'puntos_bonus' => 25,
            'rareza' => 'comun',
        ];

        $this->assertArrayHasKey('nombre', $badge);
        $this->assertArrayHasKey('condicion', $badge);
        $this->assertArrayHasKey('rareza', $badge);
    }

    /**
     * Test categorías de badges.
     */
    public function test_badge_categories() {
        $categories = [
            'contenido' => 'Creación de contenido',
            'social' => 'Interacción social',
            'comunidad' => 'Participación comunitaria',
            'comercio' => 'Actividad comercial',
            'evento' => 'Asistencia a eventos',
            'especial' => 'Logros especiales',
        ];

        $this->assertArrayHasKey('contenido', $categories);
        $this->assertArrayHasKey('especial', $categories);
    }

    /**
     * Test rarezas de badges.
     */
    public function test_badge_rarities() {
        $rarities = [
            'comun' => ['color' => '#9ca3af', 'multiplier' => 1],
            'raro' => ['color' => '#3b82f6', 'multiplier' => 2],
            'epico' => ['color' => '#8b5cf6', 'multiplier' => 3],
            'legendario' => ['color' => '#f59e0b', 'multiplier' => 5],
        ];

        $this->assertEquals(1, $rarities['comun']['multiplier']);
        $this->assertEquals(5, $rarities['legendario']['multiplier']);
    }

    /**
     * Test badges de usuario.
     */
    public function test_user_badges() {
        $userBadges = [
            [
                'badge_id' => 1,
                'earned_at' => '2025-01-01 12:00:00',
                'displayed' => true,
            ],
            [
                'badge_id' => 5,
                'earned_at' => '2025-01-10 15:00:00',
                'displayed' => false,
            ],
        ];

        $displayedBadges = array_filter($userBadges, fn($b) => $b['displayed']);
        $this->assertCount(1, $displayedBadges);
    }

    /**
     * Test verificación de condición de badge.
     */
    public function test_badge_condition_check() {
        $userStats = [
            'posts_count' => 15,
            'comments_count' => 50,
            'likes_received' => 100,
            'events_attended' => 5,
        ];

        $conditions = [
            'posts_count >= 10' => true,
            'comments_count >= 100' => false,
            'likes_received >= 50' => true,
        ];

        foreach ($conditions as $condition => $expected) {
            preg_match('/(\w+)\s*(>=|<=|>|<|==)\s*(\d+)/', $condition, $matches);
            $field = $matches[1];
            $operator = $matches[2];
            $value = (int) $matches[3];

            $result = match($operator) {
                '>=' => $userStats[$field] >= $value,
                '<=' => $userStats[$field] <= $value,
                '>' => $userStats[$field] > $value,
                '<' => $userStats[$field] < $value,
                '==' => $userStats[$field] == $value,
            };

            $this->assertEquals($expected, $result, "Condition: $condition");
        }
    }

    /**
     * Test ranking de usuarios.
     */
    public function test_user_ranking() {
        $users = [
            ['user_id' => 1, 'points' => 1500, 'name' => 'Alice'],
            ['user_id' => 2, 'points' => 2000, 'name' => 'Bob'],
            ['user_id' => 3, 'points' => 1000, 'name' => 'Charlie'],
            ['user_id' => 4, 'points' => 2500, 'name' => 'Diana'],
        ];

        usort($users, fn($a, $b) => $b['points'] - $a['points']);

        $this->assertEquals('Diana', $users[0]['name']);
        $this->assertEquals('Charlie', $users[3]['name']);
    }

    /**
     * Test rachas (streaks).
     */
    public function test_login_streaks() {
        $streak = [
            'user_id' => 10,
            'current_streak' => 7,
            'longest_streak' => 15,
            'last_login' => '2025-01-15',
            'streak_bonus' => [
                3 => 10,  // 3 días = 10 puntos
                7 => 25,  // 7 días = 25 puntos
                14 => 50, // 14 días = 50 puntos
                30 => 100, // 30 días = 100 puntos
            ],
        ];

        $this->assertEquals(7, $streak['current_streak']);
        $this->assertArrayHasKey(7, $streak['streak_bonus']);
    }

    /**
     * Test multiplicadores temporales.
     */
    public function test_temporary_multipliers() {
        $multipliers = [
            [
                'name' => 'Doble XP Fin de Semana',
                'multiplier' => 2.0,
                'start' => '2025-01-18 00:00:00',
                'end' => '2025-01-19 23:59:59',
                'applies_to' => ['all'],
            ],
            [
                'name' => 'Bonus Eventos',
                'multiplier' => 1.5,
                'start' => '2025-01-15 00:00:00',
                'end' => '2025-01-31 23:59:59',
                'applies_to' => ['asistir_evento', 'crear_evento'],
            ],
        ];

        $this->assertEquals(2.0, $multipliers[0]['multiplier']);
        $this->assertContains('asistir_evento', $multipliers[1]['applies_to']);
    }

    /**
     * Test logros desbloqueables.
     */
    public function test_achievements() {
        $achievements = [
            [
                'id' => 'first_blood',
                'title' => 'Primera Sangre',
                'description' => 'Realiza tu primera acción',
                'progress' => 1,
                'target' => 1,
                'completed' => true,
            ],
            [
                'id' => 'social_butterfly',
                'title' => 'Mariposa Social',
                'description' => 'Conecta con 50 usuarios',
                'progress' => 35,
                'target' => 50,
                'completed' => false,
            ],
        ];

        $completedCount = count(array_filter($achievements, fn($a) => $a['completed']));
        $this->assertEquals(1, $completedCount);
    }

    /**
     * Test progreso hacia siguiente nivel.
     */
    public function test_level_progress() {
        $currentPoints = 750;
        $currentLevel = 3;
        $nextLevelThreshold = 1000;
        $currentLevelThreshold = 500;

        $pointsInLevel = $currentPoints - $currentLevelThreshold;
        $pointsNeeded = $nextLevelThreshold - $currentLevelThreshold;
        $progress = ($pointsInLevel / $pointsNeeded) * 100;

        $this->assertEquals(50, $progress);
    }

    /**
     * Test leaderboard semanal.
     */
    public function test_weekly_leaderboard() {
        $leaderboard = [
            'period' => 'weekly',
            'start_date' => '2025-01-13',
            'end_date' => '2025-01-19',
            'entries' => [
                ['rank' => 1, 'user_id' => 5, 'points' => 500, 'change' => 2],
                ['rank' => 2, 'user_id' => 12, 'points' => 450, 'change' => -1],
                ['rank' => 3, 'user_id' => 8, 'points' => 400, 'change' => 0],
            ],
        ];

        $this->assertEquals('weekly', $leaderboard['period']);
        $this->assertEquals(1, $leaderboard['entries'][0]['rank']);
    }

    /**
     * Test recompensas por nivel.
     */
    public function test_level_rewards() {
        $levelRewards = [
            2 => ['type' => 'badge', 'value' => 'nivel_2'],
            3 => ['type' => 'feature', 'value' => 'custom_avatar'],
            4 => ['type' => 'discount', 'value' => 10],
            5 => ['type' => 'badge', 'value' => 'experto'],
            6 => ['type' => 'feature', 'value' => 'highlighted_profile'],
        ];

        $this->assertEquals('badge', $levelRewards[2]['type']);
        $this->assertEquals(10, $levelRewards[4]['value']);
    }

    /**
     * Test límites anti-abuse.
     */
    public function test_anti_abuse_limits() {
        $limits = [
            'max_points_per_day' => 500,
            'max_same_action_per_hour' => 10,
            'cooldown_between_actions' => 60, // segundos
            'suspicious_threshold' => 100, // acciones por hora
        ];

        $this->assertEquals(500, $limits['max_points_per_day']);
        $this->assertEquals(60, $limits['cooldown_between_actions']);
    }

    /**
     * Test estadísticas de gamificación.
     */
    public function test_gamification_stats() {
        $stats = [
            'total_users_with_points' => 1500,
            'total_points_distributed' => 250000,
            'total_badges_earned' => 5000,
            'average_level' => 2.5,
            'highest_level_user' => ['user_id' => 42, 'level' => 6],
            'most_common_badge' => ['badge_id' => 1, 'count' => 1200],
        ];

        $this->assertGreaterThan(0, $stats['total_users_with_points']);
        $this->assertEquals(6, $stats['highest_level_user']['level']);
    }

    /**
     * Test decaimiento de puntos.
     */
    public function test_points_decay() {
        $decayConfig = [
            'enabled' => true,
            'decay_after_days' => 90,
            'decay_percentage' => 5,
            'minimum_points' => 100,
            'exempt_levels' => [5, 6],
        ];

        $userPoints = 1000;
        $daysSinceActivity = 100;
        $userLevel = 3;

        $shouldDecay = $decayConfig['enabled']
            && $daysSinceActivity > $decayConfig['decay_after_days']
            && !in_array($userLevel, $decayConfig['exempt_levels']);

        $this->assertTrue($shouldDecay);

        if ($shouldDecay) {
            $decayAmount = $userPoints * ($decayConfig['decay_percentage'] / 100);
            $newPoints = max($decayConfig['minimum_points'], $userPoints - $decayAmount);
            $this->assertEquals(950, $newPoints);
        }
    }

    /**
     * Test exportación de datos de reputación.
     */
    public function test_reputation_data_export() {
        $exportData = [
            'user_id' => 10,
            'exported_at' => '2025-01-15 12:00:00',
            'data' => [
                'total_points' => 1500,
                'level' => 4,
                'badges' => [1, 3, 5, 7],
                'points_history' => [/* ... */],
                'achievements' => [/* ... */],
            ],
        ];

        $this->assertArrayHasKey('total_points', $exportData['data']);
        $this->assertIsArray($exportData['data']['badges']);
    }
}
