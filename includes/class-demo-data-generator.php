<?php
/**
 * Generador de Datos de Prueba para Flavor Chat IA
 *
 * Genera datos ficticios para todos los módulos.
 * Los nombres de columnas coinciden con class-database-installer.php
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Flavor_Demo_Data_Generator', false)) {
    return;
}

class Flavor_Demo_Data_Generator {

    private static $instance = null;
    private $wpdb;
    private $prefix;

    // Datos base para generar contenido realista
    private $nombres = ['María', 'Juan', 'Ana', 'Carlos', 'Laura', 'Pedro', 'Sofía', 'Diego', 'Elena', 'Miguel', 'Carmen', 'José', 'Lucía', 'Antonio', 'Paula'];
    private $apellidos = ['García', 'Martínez', 'López', 'Sánchez', 'González', 'Rodríguez', 'Fernández', 'Pérez', 'Gómez', 'Ruiz'];
    private $calles = ['Calle Mayor', 'Avenida de la Paz', 'Plaza del Sol', 'Calle del Prado', 'Paseo de la Castellana', 'Calle Luna', 'Avenida Central', 'Calle del Mar', 'Plaza Mayor', 'Calle Nueva'];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
    }

    /**
     * Genera todos los datos de prueba
     */
    public function generate_all() {
        $results = [];

        // Crear usuarios de prueba si no existen
        $results['usuarios'] = $this->create_test_users();

        // Generar datos para cada módulo
        $results['eventos'] = $this->generate_eventos();
        $results['espacios'] = $this->generate_espacios();
        $results['cursos'] = $this->generate_cursos();
        $results['talleres'] = $this->generate_talleres();
        $results['avisos'] = $this->generate_avisos();
        $results['chat_grupos'] = $this->generate_chat_grupos();
        $results['foros'] = $this->generate_foros();
        $results['huertos'] = $this->generate_huertos();
        $results['reciclaje'] = $this->generate_reciclaje();
        $results['marketplace'] = $this->generate_marketplace();
        $results['tienda'] = $this->generate_tienda();
        $results['banco_tiempo'] = $this->generate_banco_tiempo();
        $results['colectivos'] = $this->generate_colectivos();
        $results['socios'] = $this->generate_socios();
        $results['incidencias'] = $this->generate_incidencias();
        $results['tramites'] = $this->generate_tramites();
        $results['participacion'] = $this->generate_participacion();
        $results['presupuestos'] = $this->generate_presupuestos();
        $results['biblioteca'] = $this->generate_biblioteca();
        $results['bares'] = $this->generate_bares();
        $results['podcast'] = $this->generate_podcast();
        $results['carpooling'] = $this->generate_carpooling();
        $results['grupos_consumo'] = $this->generate_grupos_consumo();
        $results['compostaje'] = $this->generate_compostaje();
        $results['ayuda_vecinal'] = $this->generate_ayuda_vecinal();
        $results['recursos'] = $this->generate_recursos();
        $results['bicicletas'] = $this->generate_bicicletas();

        return $results;
    }

    /**
     * Configura usuarios para datos demo
     * Usa el usuario actual (admin) y otros administradores existentes
     */
    private function create_test_users() {
        $user_ids = $this->get_demo_users();
        update_option('flavor_demo_user_ids', $user_ids);
        return count($user_ids);
    }

    /**
     * Obtiene IDs de usuarios para datos demo
     * Prioriza: usuario actual, administradores, cualquier usuario existente
     */
    private function get_demo_users() {
        $ids = [];

        // 1. Usuario actual (admin logueado)
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            $ids[] = $current_user_id;
        }

        // 2. Otros administradores
        $admins = get_users([
            'role' => 'administrator',
            'fields' => 'ID',
            'number' => 5,
            'exclude' => $ids,
        ]);
        foreach ($admins as $admin_id) {
            if (!in_array($admin_id, $ids)) {
                $ids[] = (int)$admin_id;
            }
        }

        // 3. Si no hay suficientes, agregar otros usuarios
        if (count($ids) < 3) {
            $otros = get_users([
                'fields' => 'ID',
                'number' => 5,
                'exclude' => $ids,
            ]);
            foreach ($otros as $user_id) {
                if (!in_array($user_id, $ids)) {
                    $ids[] = (int)$user_id;
                }
            }
        }

        // 4. Fallback al ID 1 si no hay usuarios
        if (empty($ids)) {
            $ids = [1];
        }

        return $ids;
    }

    /**
     * Helper: Fecha aleatoria
     */
    private function random_date($start = '-30 days', $end = '+60 days') {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        return date('Y-m-d H:i:s', rand($start_ts, $end_ts));
    }

    /**
     * Helper: Dirección aleatoria
     */
    private function random_address() {
        return $this->calles[array_rand($this->calles)] . ', ' . rand(1, 150);
    }

    /**
     * Genera eventos
     * Tabla: eventos (titulo, descripcion, fecha_inicio, fecha_fin, lugar, capacidad, precio, imagen, estado)
     */
    private function generate_eventos() {
        $table = $this->prefix . 'eventos';
        if (!$this->table_exists($table)) return 0;

        $eventos = [
            ['Taller de Huerto Urbano', 'Aprende a cultivar tus propias verduras en espacios reducidos'],
            ['Fiesta del Barrio 2024', 'Celebración anual con música, comida y actividades para todos'],
            ['Mercadillo de Intercambio', 'Trae lo que no uses y llévate lo que necesites'],
            ['Clase de Yoga al Aire Libre', 'Sesión gratuita de yoga en el parque'],
            ['Charla: Economía Circular', 'Conferencia sobre sostenibilidad y consumo responsable'],
            ['Cine de Verano', 'Proyección de película familiar en la plaza'],
            ['Ruta en Bicicleta', 'Paseo guiado por los parques del barrio'],
            ['Taller de Reciclaje Creativo', 'Crea arte con materiales reciclados'],
            ['Asamblea Vecinal', 'Reunión mensual de vecinos'],
            ['Concierto Solidario', 'Música en vivo a beneficio del banco de alimentos'],
        ];

        $inserted = 0;

        foreach ($eventos as $evento) {
            $fecha_inicio = $this->random_date('+1 day', '+90 days');

            $result = $this->wpdb->insert($table, [
                'titulo' => $evento[0],
                'descripcion' => $evento[1],
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => date('Y-m-d H:i:s', strtotime($fecha_inicio) + 7200),
                'lugar' => $this->random_address(),
                'capacidad' => rand(20, 100),
                'precio' => rand(0, 1) ? 0 : rand(5, 25),
                'estado' => 'publicado',
            ]);

            if ($result) $inserted++;
        }

        // Generar inscripciones
        $this->generate_inscripciones_eventos();

        return $inserted;
    }

    /**
     * Genera inscripciones a eventos
     * Tabla: eventos_inscripciones (evento_id, usuario_id, estado, fecha_inscripcion)
     */
    private function generate_inscripciones_eventos() {
        $table = $this->prefix . 'eventos_inscripciones';
        if (!$this->table_exists($table)) return;

        $eventos = $this->wpdb->get_col("SELECT id FROM {$this->prefix}eventos LIMIT 10");
        $users = $this->get_demo_users();

        foreach ($eventos as $evento_id) {
            $num_inscritos = rand(2, 4);
            $inscritos = array_rand(array_flip($users), min($num_inscritos, count($users)));
            if (!is_array($inscritos)) $inscritos = [$inscritos];

            foreach ($inscritos as $user_id) {
                $estado = rand(0, 10) > 2 ? 'confirmada' : 'pendiente';
                $fecha = $this->random_date('-30 days', 'now');

                // Usar INSERT IGNORE para evitar errores de duplicado
                $this->wpdb->query($this->wpdb->prepare(
                    "INSERT IGNORE INTO $table (evento_id, usuario_id, estado, fecha_inscripcion) VALUES (%d, %d, %s, %s)",
                    $evento_id, $user_id, $estado, $fecha
                ));
            }
        }
    }

    /**
     * Genera espacios
     * Tabla: espacios (nombre, descripcion, capacidad, ubicacion, imagen, estado)
     */
    private function generate_espacios() {
        $table = $this->prefix . 'espacios';
        if (!$this->table_exists($table)) return 0;

        $espacios = [
            ['Sala Multiusos A', 'Sala amplia para reuniones y talleres', 50],
            ['Sala de Reuniones B', 'Espacio íntimo para grupos pequeños', 15],
            ['Patio Comunitario', 'Espacio exterior con jardín', 100],
            ['Aula Digital', 'Equipada con ordenadores y proyector', 25],
            ['Cocina Compartida', 'Cocina totalmente equipada', 10],
            ['Sala de Lectura', 'Espacio tranquilo para estudiar', 20],
            ['Gimnasio', 'Equipamiento básico de fitness', 30],
            ['Terraza', 'Terraza con vistas', 40],
        ];

        $inserted = 0;

        foreach ($espacios as $espacio) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $espacio[0],
                'descripcion' => $espacio[1],
                'capacidad' => $espacio[2],
                'ubicacion' => $this->random_address(),
                'estado' => 'activo',
            ]);

            if ($result) $inserted++;
        }

        // Generar reservas
        $this->generate_reservas();

        return $inserted;
    }

    /**
     * Genera reservas
     * Tabla: reservas (espacio_id, usuario_id, fecha_inicio, fecha_fin, estado, notas)
     */
    private function generate_reservas() {
        $table = $this->prefix . 'reservas';
        if (!$this->table_exists($table)) return;

        $espacios = $this->wpdb->get_col("SELECT id FROM {$this->prefix}espacios LIMIT 8");
        $users = $this->get_demo_users();

        foreach ($espacios as $espacio_id) {
            for ($i = 0; $i < rand(2, 4); $i++) {
                $fecha_base = $this->random_date('+1 day', '+30 days');
                $hora_inicio = rand(9, 18);

                $this->wpdb->insert($table, [
                    'espacio_id' => $espacio_id,
                    'usuario_id' => $users[array_rand($users)],
                    'fecha_inicio' => date('Y-m-d', strtotime($fecha_base)) . sprintf(' %02d:00:00', $hora_inicio),
                    'fecha_fin' => date('Y-m-d', strtotime($fecha_base)) . sprintf(' %02d:00:00', $hora_inicio + rand(1, 3)),
                    'estado' => ['pendiente', 'confirmada', 'confirmada', 'confirmada'][rand(0, 3)],
                    'notas' => '',
                ]);
            }
        }
    }

    /**
     * Genera cursos
     * Tabla: cursos (titulo, descripcion, duracion_horas, precio, imagen, estado)
     */
    private function generate_cursos() {
        $table = $this->prefix . 'cursos';
        if (!$this->table_exists($table)) return 0;

        $cursos = [
            ['Iniciación al Huerto Urbano', 'Aprende las bases del cultivo en ciudad', 24],
            ['Fotografía con Smartphone', 'Mejora tus fotos con el móvil', 18],
            ['Cocina Vegetariana', 'Recetas saludables y deliciosas', 12],
            ['Reparación de Bicicletas', 'Mantenimiento básico', 9],
            ['Inglés Conversacional', 'Practica tu inglés hablando', 36],
            ['Costura Básica', 'Aprende a arreglar tu ropa', 18],
            ['Informática para Mayores', 'Internet y redes sociales', 24],
            ['Yoga y Meditación', 'Bienestar cuerpo y mente', 30],
        ];

        $inserted = 0;

        foreach ($cursos as $curso) {
            $result = $this->wpdb->insert($table, [
                'titulo' => $curso[0],
                'descripcion' => $curso[1],
                'duracion_horas' => $curso[2],
                'precio' => rand(0, 1) ? 0 : rand(20, 80),
                'estado' => 'activo',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera talleres
     * Tabla: talleres (titulo, descripcion, fecha, duracion_minutos, plazas_disponibles, precio, lugar, estado)
     */
    private function generate_talleres() {
        $table = $this->prefix . 'talleres';
        if (!$this->table_exists($table)) return 0;

        $talleres = [
            ['Taller de Jabones Naturales', 'Crea tus propios jabones artesanales'],
            ['Encuadernación Artesanal', 'Haz tu propio cuaderno'],
            ['Compostaje Doméstico', 'Aprende a compostar en casa'],
            ['Fermentación de Vegetales', 'Kimchi, chucrut y más'],
            ['Cerámica para Principiantes', 'Modelado con barro'],
            ['Carpintería Básica', 'Construye una estantería'],
        ];

        $inserted = 0;

        foreach ($talleres as $taller) {
            $result = $this->wpdb->insert($table, [
                'titulo' => $taller[0],
                'descripcion' => $taller[1],
                'fecha' => $this->random_date('+5 days', '+45 days'),
                'duracion_minutos' => rand(2, 4) * 60,
                'plazas_disponibles' => rand(8, 20),
                'precio' => rand(10, 35),
                'lugar' => $this->random_address(),
                'estado' => 'publicado',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera avisos
     * Tabla: avisos (titulo, contenido, urgente, fecha_publicacion, fecha_expiracion, estado)
     */
    private function generate_avisos() {
        $table = $this->prefix . 'avisos';
        if (!$this->table_exists($table)) return 0;

        $avisos = [
            ['Corte de agua programado', 'El próximo martes habrá corte de agua de 10:00 a 14:00', 1],
            ['Nuevo horario del centro cívico', 'A partir del lunes, abrimos de 9:00 a 21:00', 0],
            ['Recogida de enseres voluminosos', 'Solicita cita para la recogida gratuita', 0],
            ['Campaña de vacunación antigripal', 'Centro de salud del barrio', 0],
            ['Obras en Calle Mayor', 'Desvío de tráfico durante 2 semanas', 1],
            ['Apertura de piscina municipal', 'Temporada de verano desde el 15 de junio', 0],
            ['Limpieza de contenedores', 'Este sábado, por favor no depositar basura de 8:00 a 12:00', 0],
            ['Nuevo punto de reciclaje', 'Instalado en Plaza del Sol', 0],
        ];

        $inserted = 0;

        foreach ($avisos as $aviso) {
            $result = $this->wpdb->insert($table, [
                'titulo' => $aviso[0],
                'contenido' => $aviso[1],
                'urgente' => $aviso[2],
                'fecha_publicacion' => $this->random_date('-15 days', '+5 days'),
                'fecha_expiracion' => $this->random_date('+10 days', '+60 days'),
                'estado' => 'publicado',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera grupos de chat
     * Tabla: chat_grupos (nombre, descripcion, imagen, tipo, estado)
     * Tabla: chat_grupos_miembros (grupo_id, usuario_id, rol, fecha_union) - UNIQUE KEY (grupo_id, usuario_id)
     */
    private function generate_chat_grupos() {
        $table = $this->prefix . 'chat_grupos';
        if (!$this->table_exists($table)) return 0;

        $grupos = [
            ['Vecinos Bloque 5', 'Grupo del bloque 5 de la comunidad'],
            ['Padres y Madres CEIP Sol', 'AMPA del colegio'],
            ['Club de Lectura', 'Comentamos libros cada mes'],
            ['Runners del Barrio', 'Quedadas para correr'],
            ['Intercambio de Plantas', 'Compartimos esquejes y semillas'],
            ['Compras Colectivas', 'Organizamos compras a productores'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();
        $miembros_table = $this->prefix . 'chat_grupos_miembros';

        foreach ($grupos as $grupo) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $grupo[0],
                'descripcion' => $grupo[1],
                'tipo' => rand(0, 1) ? 'publico' : 'privado',
                'estado' => 'activo',
            ]);

            if ($result) {
                $inserted++;
                $grupo_id = $this->wpdb->insert_id;

                // Agregar miembros usando INSERT IGNORE para evitar errores de duplicado
                $num_miembros = rand(3, 5);
                $miembros = array_rand(array_flip($users), min($num_miembros, count($users)));
                if (!is_array($miembros)) $miembros = [$miembros];

                $is_first = true;
                foreach ($miembros as $user_id) {
                    $rol = $is_first ? 'admin' : 'miembro';
                    $fecha = current_time('mysql');

                    // Usar INSERT IGNORE para evitar errores de UNIQUE KEY
                    $this->wpdb->query($this->wpdb->prepare(
                        "INSERT IGNORE INTO $miembros_table (grupo_id, usuario_id, rol, fecha_union) VALUES (%d, %d, %s, %s)",
                        $grupo_id, $user_id, $rol, $fecha
                    ));
                    $is_first = false;
                }
            }
        }

        return $inserted;
    }

    /**
     * Genera temas de foro
     * Tabla: foros_temas (titulo, contenido, autor_id, respuestas, vistas, estado)
     */
    private function generate_foros() {
        $table = $this->prefix . 'foros_temas';
        if (!$this->table_exists($table)) return 0;

        $temas = [
            ['¿Alguien sabe de un buen electricista?', 'Busco recomendaciones para arreglar unos enchufes'],
            ['Propuesta: más bancos en el parque', 'Creo que hacen falta más lugares para sentarse'],
            ['Quedada de limpieza del barrio', 'Organizamos una jornada de limpieza voluntaria'],
            ['Ruidos nocturnos calle Luna', '¿Alguien más afectado por los ruidos?'],
            ['Recomendaciones de restaurantes', 'Comparte tus sitios favoritos del barrio'],
            ['Perdido gato naranja', 'Se llama Garfield, tiene collar azul'],
            ['Clases de español para vecinos nuevos', 'Ofrezco clases gratuitas'],
            ['Problema con el contenedor orgánico', 'Siempre está lleno, ¿podemos pedir otro?'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($temas as $tema) {
            $result = $this->wpdb->insert($table, [
                'titulo' => $tema[0],
                'contenido' => $tema[1],
                'autor_id' => $users[array_rand($users)],
                'respuestas' => rand(0, 15),
                'vistas' => rand(10, 200),
                'estado' => 'abierto',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera huertos (parcelas)
     * Tabla: huertos_parcelas (nombre, superficie, ubicacion, estado)
     * Tabla: huertos_asignaciones (parcela_id, usuario_id, fecha_inicio, fecha_fin, estado)
     */
    private function generate_huertos() {
        $table = $this->prefix . 'huertos_parcelas';
        if (!$this->table_exists($table)) return 0;

        // Intentar eliminar índice problemático si existe
        $index_exists = $this->wpdb->get_var("SHOW INDEX FROM $table WHERE Key_name = 'huerto_numero'");
        if ($index_exists) {
            $this->wpdb->query("ALTER TABLE $table DROP INDEX huerto_numero");
        }

        $inserted = 0;
        $users = $this->get_demo_users();

        $zonas = ['Norte', 'Sur', 'Este', 'Oeste'];

        for ($i = 1; $i <= 20; $i++) {
            $zona = $zonas[($i - 1) % 4];
            $estado = rand(0, 3) > 0 ? 'asignada' : 'disponible';
            $nombre_parcela = 'Parcela Demo ' . $i . ' - ' . uniqid();

            // Usar INSERT IGNORE para evitar errores de duplicado
            $sql = $this->wpdb->prepare(
                "INSERT IGNORE INTO $table (nombre, superficie, ubicacion, estado) VALUES (%s, %d, %s, %s)",
                $nombre_parcela,
                rand(15, 40),
                'Huerto Comunitario ' . $zona,
                $estado
            );

            $result = $this->wpdb->query($sql);

            if ($result && $this->wpdb->insert_id) {
                $inserted++;
                $parcela_id = $this->wpdb->insert_id;

                // Asignar algunas parcelas
                if ($estado === 'asignada') {
                    $this->wpdb->insert($this->prefix . 'huertos_asignaciones', [
                        'parcela_id' => $parcela_id,
                        'usuario_id' => $users[array_rand($users)],
                        'fecha_inicio' => date('Y-m-d', strtotime('-' . rand(30, 180) . ' days')),
                        'fecha_fin' => date('Y-m-d', strtotime('+' . rand(180, 365) . ' days')),
                        'estado' => 'activo',
                    ]);
                }
            }
        }

        return $inserted;
    }

    /**
     * Genera puntos de reciclaje (son puntos de usuario, no ubicaciones)
     * Tabla: reciclaje_puntos (usuario_id, puntos, concepto, fecha)
     */
    private function generate_reciclaje() {
        $table = $this->prefix . 'reciclaje_puntos';
        if (!$this->table_exists($table)) return 0;

        $conceptos = [
            'Reciclaje de papel',
            'Reciclaje de vidrio',
            'Reciclaje de plástico',
            'Reciclaje de pilas',
            'Reciclaje de ropa',
            'Reciclaje de aceite',
            'Reciclaje de electrónicos',
            'Compostaje doméstico',
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($users as $user_id) {
            // Cada usuario tiene varios registros de puntos
            for ($i = 0; $i < rand(3, 8); $i++) {
                $result = $this->wpdb->insert($table, [
                    'usuario_id' => $user_id,
                    'puntos' => rand(10, 100),
                    'concepto' => $conceptos[array_rand($conceptos)],
                    'fecha' => $this->random_date('-60 days', 'now'),
                ]);

                if ($result) $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Genera anuncios de marketplace
     * Tabla: marketplace (usuario_id, titulo, descripcion, precio, imagen, categoria, estado)
     */
    private function generate_marketplace() {
        $table = $this->prefix . 'marketplace';
        if (!$this->table_exists($table)) return 0;

        $anuncios = [
            ['Bicicleta de paseo', 'En buen estado, poco uso', 'Vehículos', 80],
            ['Sofá 3 plazas', 'Color gris, muy cómodo', 'Hogar', 150],
            ['Libros de universidad', 'Derecho y económicas', 'Libros', 25],
            ['Mesa de comedor', 'Madera maciza, 6 sillas', 'Hogar', 200],
            ['Patinete eléctrico', 'Batería nueva', 'Vehículos', 120],
            ['Ropa bebé 0-12 meses', 'Lote completo, niño', 'Ropa', 40],
            ['Guitarra española', 'Para principiantes', 'Música', 60],
            ['Plantas de interior', 'Varias macetas', 'Jardín', 15],
            ['Microondas Samsung', 'Funciona perfecto', 'Electrodomésticos', 35],
            ['Juegos de mesa', 'Catan, Carcassonne...', 'Ocio', 50],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($anuncios as $anuncio) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'titulo' => $anuncio[0],
                'descripcion' => $anuncio[1],
                'categoria' => $anuncio[2],
                'precio' => $anuncio[3],
                'estado' => 'activo',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera productos de tienda
     * Tabla: tienda_productos (nombre, descripcion, precio, stock, imagen, categoria, estado)
     */
    private function generate_tienda() {
        $table = $this->prefix . 'tienda_productos';
        if (!$this->table_exists($table)) return 0;

        $productos = [
            ['Miel de la Sierra', 'Miel artesanal 500g', 8.50, 'Alimentación'],
            ['Aceite de Oliva Virgen', 'Botella 1L primera prensada', 12.00, 'Alimentación'],
            ['Jabón de Lavanda', 'Artesanal, 100g', 4.50, 'Higiene'],
            ['Cesta de Mimbre', 'Hecha a mano', 18.00, 'Artesanía'],
            ['Mermelada de Higos', 'Sin azúcar añadido', 5.00, 'Alimentación'],
            ['Velas de Cera', 'Pack de 4 unidades', 12.00, 'Hogar'],
            ['Bolsa de Tela', 'Con diseño del barrio', 6.00, 'Complementos'],
            ['Queso Curado', 'De oveja, 250g', 9.00, 'Alimentación'],
        ];

        $inserted = 0;

        foreach ($productos as $producto) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $producto[0],
                'descripcion' => $producto[1],
                'precio' => $producto[2],
                'categoria' => $producto[3],
                'stock' => rand(5, 50),
                'estado' => 'disponible',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera saldos de banco del tiempo
     * Tabla: banco_tiempo_saldo (usuario_id, horas, concepto, tipo, fecha)
     */
    private function generate_banco_tiempo() {
        $table = $this->prefix . 'banco_tiempo_saldo';
        if (!$this->table_exists($table)) return 0;

        $conceptos_ingreso = [
            'Clases de idiomas',
            'Cuidado de niños',
            'Ayuda con mudanza',
            'Reparaciones domésticas',
            'Paseo de mascotas',
            'Ayuda con tecnología',
        ];

        $conceptos_retiro = [
            'Clases de cocina recibidas',
            'Cuidado de plantas',
            'Ayuda administrativa',
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($users as $user_id) {
            // Ingresos
            for ($i = 0; $i < rand(2, 5); $i++) {
                $result = $this->wpdb->insert($table, [
                    'usuario_id' => $user_id,
                    'horas' => rand(1, 5),
                    'concepto' => $conceptos_ingreso[array_rand($conceptos_ingreso)],
                    'tipo' => 'ingreso',
                    'fecha' => $this->random_date('-60 days', 'now'),
                ]);
                if ($result) $inserted++;
            }

            // Retiros
            for ($i = 0; $i < rand(1, 3); $i++) {
                $result = $this->wpdb->insert($table, [
                    'usuario_id' => $user_id,
                    'horas' => rand(1, 3),
                    'concepto' => $conceptos_retiro[array_rand($conceptos_retiro)],
                    'tipo' => 'retiro',
                    'fecha' => $this->random_date('-60 days', 'now'),
                ]);
                if ($result) $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Genera colectivos
     * Tabla: colectivos (nombre, descripcion, tipo, imagen, estado)
     * Tabla: colectivos_miembros (colectivo_id, usuario_id, rol, fecha_union) - UNIQUE KEY (colectivo_id, usuario_id)
     */
    private function generate_colectivos() {
        $table = $this->prefix . 'colectivos';
        if (!$this->table_exists($table)) return 0;

        $colectivos = [
            ['Asociación de Mayores', 'Actividades para la tercera edad', 'social'],
            ['Colectivo Feminista', 'Igualdad y derechos', 'social'],
            ['Ecologistas en Acción', 'Defensa del medio ambiente', 'medioambiental'],
            ['AMPA Colegio Sol', 'Padres y madres del colegio', 'educativo'],
            ['Club de Ajedrez', 'Torneos y clases', 'deportivo'],
            ['Coral del Barrio', 'Canto y música', 'cultural'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();
        $miembros_table = $this->prefix . 'colectivos_miembros';

        foreach ($colectivos as $colectivo) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $colectivo[0],
                'descripcion' => $colectivo[1],
                'tipo' => $colectivo[2],
                'estado' => 'activo',
            ]);

            if ($result) {
                $inserted++;
                $colectivo_id = $this->wpdb->insert_id;

                // Agregar miembros usando INSERT IGNORE para evitar errores de duplicado
                $num_miembros = rand(2, 4);
                $miembros = array_rand(array_flip($users), min($num_miembros, count($users)));
                if (!is_array($miembros)) $miembros = [$miembros];

                $is_first = true;
                foreach ($miembros as $user_id) {
                    $rol = $is_first ? 'admin' : 'miembro';
                    $fecha = current_time('mysql');

                    // Usar INSERT IGNORE para evitar errores de UNIQUE KEY
                    $this->wpdb->query($this->wpdb->prepare(
                        "INSERT IGNORE INTO $miembros_table (colectivo_id, usuario_id, rol, fecha_union) VALUES (%d, %d, %s, %s)",
                        $colectivo_id, $user_id, $rol, $fecha
                    ));
                    $is_first = false;
                }
            }
        }

        return $inserted;
    }

    /**
     * Genera socios
     * Tabla: socios (usuario_id, numero_socio, tipo, estado, fecha_alta, fecha_renovacion)
     * Nota: usuario_id tiene restriccion UNIQUE
     */
    private function generate_socios() {
        $table = $this->prefix . 'socios';
        if (!$this->table_exists($table)) return 0;

        $inserted = 0;
        $users = $this->get_demo_users();

        // Obtener el siguiente numero de socio disponible
        $max_numero = $this->wpdb->get_var("SELECT MAX(CAST(SUBSTRING(numero_socio, 5) AS UNSIGNED)) FROM $table");
        $siguiente_numero = ($max_numero ?: 0) + 1;

        foreach ($users as $user_id) {
            // Verificar si ya existe socio para este usuario
            $existe = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM $table WHERE usuario_id = %d",
                $user_id
            ));

            if ($existe) {
                continue; // Saltar si ya existe
            }

            $fecha_alta = date('Y-m-d', strtotime('-' . rand(30, 365) . ' days'));

            $result = $this->wpdb->insert($table, [
                'usuario_id' => $user_id,
                'numero_socio' => 'SOC-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT),
                'tipo' => ['standard', 'familiar', 'jubilado'][rand(0, 2)],
                'estado' => 'activo',
                'fecha_alta' => $fecha_alta,
                'fecha_renovacion' => date('Y-m-d', strtotime($fecha_alta . ' +1 year')),
            ]);

            if ($result) {
                $inserted++;
                $siguiente_numero++;
            }
        }

        return $inserted;
    }

    /**
     * Genera incidencias
     * Tabla: incidencias (usuario_id, titulo, descripcion, ubicacion, categoria, estado, numero_incidencia)
     * Nota: numero_incidencia tiene restriccion UNIQUE y NOT NULL
     */
    private function generate_incidencias() {
        $table = $this->prefix . 'incidencias';
        if (!$this->table_exists($table)) return 0;

        $incidencias = [
            ['Farola fundida', 'La farola de la esquina no funciona', 'alumbrado'],
            ['Bache en la calzada', 'Bache peligroso frente al número 15', 'vía pública'],
            ['Contenedor roto', 'Tapa del contenedor amarillo rota', 'limpieza'],
            ['Grafiti en fachada', 'Pintadas en el muro del parque', 'vandalismo'],
            ['Banco roto', 'Banco del parque con tabla suelta', 'mobiliario'],
            ['Semáforo averiado', 'El semáforo de peatones no funciona', 'tráfico'],
            ['Árbol caído', 'Rama grande caída en la acera', 'parques'],
            ['Ruidos molestos', 'Obras fuera de horario permitido', 'convivencia'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        // Obtener el siguiente numero de incidencia disponible
        $max_numero = $this->wpdb->get_var("SELECT MAX(CAST(SUBSTRING(numero_incidencia, 5) AS UNSIGNED)) FROM $table WHERE numero_incidencia LIKE 'INC-%'");
        $siguiente_numero = ($max_numero ?: 0) + 1;

        foreach ($incidencias as $incidencia) {
            $numero_incidencia = 'INC-' . str_pad($siguiente_numero, 6, '0', STR_PAD_LEFT);

            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'numero_incidencia' => $numero_incidencia,
                'titulo' => $incidencia[0],
                'descripcion' => $incidencia[1],
                'ubicacion' => $this->random_address(),
                'categoria' => $incidencia[2],
                'estado' => ['pendiente', 'en_proceso', 'resuelta'][rand(0, 2)],
            ]);

            if ($result) {
                $inserted++;
                $siguiente_numero++;
            }
        }

        return $inserted;
    }

    /**
     * Genera trámites
     * Tabla: tramites (usuario_id, tipo, titulo, datos, estado)
     */
    private function generate_tramites() {
        $table = $this->prefix . 'tramites';
        if (!$this->table_exists($table)) return 0;

        $tramites = [
            ['cita_previa', 'Solicitud de cita previa', 'Para renovar documentación'],
            ['empadronamiento', 'Empadronamiento', 'Alta en el padrón municipal'],
            ['licencia_obras', 'Licencia de obras menores', 'Reforma de cocina'],
            ['ayuda_social', 'Solicitud de ayuda', 'Bono social energético'],
            ['reserva_espacio', 'Reserva de espacio', 'Para evento vecinal'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($tramites as $tramite) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'tipo' => $tramite[0],
                'titulo' => $tramite[1],
                'datos' => json_encode(['descripcion' => $tramite[2]]),
                'estado' => ['pendiente', 'en_proceso', 'completado'][rand(0, 2)],
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera procesos de participación
     * Tabla: participacion_procesos (titulo, descripcion, fecha_inicio, fecha_fin, estado)
     */
    private function generate_participacion() {
        $table = $this->prefix . 'participacion_procesos';
        if (!$this->table_exists($table)) return 0;

        $procesos = [
            ['Consulta: Uso del solar vacío', 'Decide qué hacer con el solar de Calle Luna'],
            ['Debate: Peatonalización centro', 'Opiniones sobre cerrar al tráfico'],
            ['Votación: Nombre del parque', 'Elige el nombre del nuevo parque'],
        ];

        $inserted = 0;

        foreach ($procesos as $proceso) {
            $fecha_inicio = $this->random_date('-15 days', '+5 days');

            $result = $this->wpdb->insert($table, [
                'titulo' => $proceso[0],
                'descripcion' => $proceso[1],
                'fecha_inicio' => date('Y-m-d', strtotime($fecha_inicio)),
                'fecha_fin' => date('Y-m-d', strtotime($fecha_inicio . ' +' . rand(20, 60) . ' days')),
                'estado' => 'activo',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera propuestas de presupuestos participativos
     * Tabla: presupuestos_propuestas (usuario_id, titulo, descripcion, presupuesto, votos, estado)
     */
    private function generate_presupuestos() {
        $table = $this->prefix . 'presupuestos_propuestas';
        if (!$this->table_exists($table)) return 0;

        $propuestas = [
            ['Carril bici en Avenida Central', 'Conectar el barrio con el centro', 45000],
            ['Parque infantil inclusivo', 'Juegos adaptados para todos los niños', 35000],
            ['Huerto comunitario ampliación', 'Más parcelas y sistema de riego', 15000],
            ['Mejora iluminación parque', 'Farolas LED y seguridad', 25000],
            ['Biblioteca al aire libre', 'Caseta de intercambio de libros', 5000],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($propuestas as $propuesta) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'titulo' => $propuesta[0],
                'descripcion' => $propuesta[1],
                'presupuesto' => $propuesta[2],
                'votos' => rand(10, 200),
                'estado' => ['pendiente', 'votacion', 'aprobada'][rand(0, 2)],
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera préstamos de biblioteca
     * Tabla: biblioteca_prestamos (usuario_id, libro_titulo, fecha_prestamo, fecha_devolucion, estado)
     */
    private function generate_biblioteca() {
        $table = $this->prefix . 'biblioteca_prestamos';
        if (!$this->table_exists($table)) return 0;

        $libros = [
            'Cien años de soledad', 'Don Quijote', 'El principito',
            '1984', 'El señor de los anillos', 'Harry Potter',
            'Crimen y castigo', 'Orgullo y prejuicio', 'La sombra del viento'
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($libros as $libro) {
            $fecha_prestamo = $this->random_date('-30 days', 'now');

            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'libro_titulo' => $libro,
                'fecha_prestamo' => date('Y-m-d', strtotime($fecha_prestamo)),
                'fecha_devolucion' => rand(0, 1) ? date('Y-m-d', strtotime($fecha_prestamo . ' +21 days')) : null,
                'estado' => rand(0, 2) > 0 ? 'activo' : 'devuelto',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera bares y restaurantes
     * Tabla: bares (nombre, tipo, direccion, telefono, horario, estado)
     */
    private function generate_bares() {
        $table = $this->prefix . 'bares';
        if (!$this->table_exists($table)) return 0;

        $bares = [
            ['Bar El Rincón', 'Bar', 'L-D: 10:00-00:00'],
            ['Restaurante La Huerta', 'Restaurante', 'L-S: 13:00-16:00, 20:00-23:00'],
            ['Café Central', 'Cafetería', 'L-V: 7:00-20:00'],
            ['Pizzería Napoli', 'Restaurante', 'L-D: 12:00-00:00'],
            ['Taberna Los Amigos', 'Bar', 'L-D: 11:00-02:00'],
            ['Heladería Dolce', 'Heladería', 'L-D: 10:00-22:00'],
        ];

        $inserted = 0;

        foreach ($bares as $bar) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $bar[0],
                'tipo' => $bar[1],
                'direccion' => $this->random_address(),
                'telefono' => '91' . rand(1000000, 9999999),
                'horario' => $bar[2],
                'estado' => 'activo',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera episodios de podcast
     * Tabla: podcast_episodios (titulo, descripcion, duracion_segundos, url_audio, estado, fecha_publicacion)
     */
    private function generate_podcast() {
        $table = $this->prefix . 'podcast_episodios';
        if (!$this->table_exists($table)) return 0;

        $episodios = [
            ['Historia del Barrio - Cap 1', 'Los orígenes de nuestro barrio'],
            ['Entrevista al alcalde', 'Hablamos sobre proyectos futuros'],
            ['Vecinos con historia', 'María nos cuenta 50 años de vida'],
            ['El huerto comunitario', 'Todo sobre cultivo urbano'],
            ['Fiestas patronales 2024', 'Programa de actividades'],
        ];

        $inserted = 0;

        foreach ($episodios as $episodio) {
            $result = $this->wpdb->insert($table, [
                'titulo' => $episodio[0],
                'descripcion' => $episodio[1],
                'duracion_segundos' => rand(900, 3600),
                'url_audio' => 'https://example.com/podcast/episodio-' . sanitize_title($episodio[0]) . '.mp3',
                'estado' => 'publicado',
                'fecha_publicacion' => $this->random_date('-90 days', 'now'),
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera viajes de carpooling
     * Tabla: carpooling_viajes (conductor_id, origen, destino, fecha_salida, plazas_disponibles, precio, estado)
     */
    private function generate_carpooling() {
        $table = $this->prefix . 'carpooling_viajes';
        if (!$this->table_exists($table)) return 0;

        $destinos = ['Madrid Centro', 'Aeropuerto', 'Universidad', 'Centro Comercial', 'Estación de tren'];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($destinos as $destino) {
            for ($i = 0; $i < rand(1, 2); $i++) {
                $fecha = $this->random_date('+1 day', '+14 days');
                $hora = sprintf('%02d:%02d:00', rand(6, 21), rand(0, 1) * 30);

                $result = $this->wpdb->insert($table, [
                    'conductor_id' => $users[array_rand($users)],
                    'origen' => 'Barrio ' . $this->calles[array_rand($this->calles)],
                    'destino' => $destino,
                    'fecha_salida' => date('Y-m-d', strtotime($fecha)) . ' ' . $hora,
                    'plazas_disponibles' => rand(1, 4),
                    'precio' => rand(2, 10),
                    'estado' => 'activo',
                ]);

                if ($result) $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Genera grupos de consumo
     * Tabla: grupos_consumo (nombre, descripcion, proximo_reparto, estado)
     * Tabla: grupos_consumo_miembros (grupo_id, usuario_id, fecha_union) - UNIQUE KEY (grupo_id, usuario_id)
     */
    private function generate_grupos_consumo() {
        $table = $this->prefix . 'grupos_consumo';
        if (!$this->table_exists($table)) return 0;

        $grupos = [
            ['Cesta Verde', 'Verduras ecológicas semanales'],
            ['Pan del Pueblo', 'Pan artesano de masa madre'],
            ['Frutas de Temporada', 'Fruta local y de temporada'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();
        $miembros_table = $this->prefix . 'grupos_consumo_miembros';

        foreach ($grupos as $grupo) {
            $result = $this->wpdb->insert($table, [
                'nombre' => $grupo[0],
                'descripcion' => $grupo[1],
                'proximo_reparto' => date('Y-m-d', strtotime('next saturday')),
                'estado' => 'activo',
            ]);

            if ($result) {
                $inserted++;
                $grupo_id = $this->wpdb->insert_id;

                // Agregar miembros usando INSERT IGNORE para evitar errores de duplicado
                $num_miembros = rand(2, 4);
                $miembros = array_rand(array_flip($users), min($num_miembros, count($users)));
                if (!is_array($miembros)) $miembros = [$miembros];

                foreach ($miembros as $user_id) {
                    $fecha = current_time('mysql');

                    // Usar INSERT IGNORE para evitar errores de UNIQUE KEY
                    $this->wpdb->query($this->wpdb->prepare(
                        "INSERT IGNORE INTO $miembros_table (grupo_id, usuario_id, fecha_union) VALUES (%d, %d, %s)",
                        $grupo_id, $user_id, $fecha
                    ));
                }
            }
        }

        return $inserted;
    }

    /**
     * Genera aportes de compostaje
     * Tabla: compostaje_aportes (usuario_id, cantidad_kg, tipo, fecha)
     */
    private function generate_compostaje() {
        $table = $this->prefix . 'compostaje_aportes';
        if (!$this->table_exists($table)) return 0;

        $tipos = ['verde', 'marron', 'mixto'];

        $inserted = 0;
        $users = $this->get_demo_users();

        for ($i = 0; $i < 30; $i++) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'cantidad_kg' => rand(1, 10) / 2,
                'tipo' => $tipos[array_rand($tipos)],
                'fecha' => $this->random_date('-60 days', 'now'),
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera solicitudes de ayuda vecinal
     * Tabla: ayuda_vecinal (usuario_id, titulo, descripcion, tipo, urgente, estado)
     */
    private function generate_ayuda_vecinal() {
        $table = $this->prefix . 'ayuda_vecinal';
        if (!$this->table_exists($table)) return 0;

        $ayudas = [
            ['Pasear perro', 'Necesito alguien que pasee a mi perro por las mañanas', 'mascotas', 0],
            ['Compra supermercado', 'Persona mayor necesita ayuda con la compra', 'compras', 0],
            ['Acompañamiento médico', 'Necesito que me acompañen al hospital', 'salud', 1],
            ['Clases de informática', 'Quiero aprender a usar el móvil', 'formación', 0],
            ['Cuidado de plantas', 'Regar plantas durante vacaciones', 'hogar', 0],
            ['Pequeñas reparaciones', 'Cambiar bombilla en alto', 'bricolaje', 0],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($ayudas as $ayuda) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'titulo' => $ayuda[0],
                'descripcion' => $ayuda[1],
                'tipo' => $ayuda[2],
                'urgente' => $ayuda[3],
                'estado' => ['activa', 'asignada', 'completada'][rand(0, 2)],
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera recursos compartidos
     * Tabla: recursos_compartidos (usuario_id, titulo, descripcion, tipo, estado)
     */
    private function generate_recursos() {
        $table = $this->prefix . 'recursos_compartidos';
        if (!$this->table_exists($table)) return 0;

        $recursos = [
            ['Taladro Bosch', 'Taladro percutor con maletín', 'herramientas'],
            ['Máquina de coser', 'Singer básica', 'hogar'],
            ['Tienda de campaña', '4 personas, impermeable', 'camping'],
            ['Proyector', 'Full HD, para presentaciones', 'electrónica'],
            ['Bicicleta de carga', 'Para transportar cosas', 'vehículos'],
            ['Sillas plegables', 'Pack de 10 sillas', 'eventos'],
            ['Desbrozadora', 'A gasolina, buen estado', 'jardín'],
        ];

        $inserted = 0;
        $users = $this->get_demo_users();

        foreach ($recursos as $recurso) {
            $result = $this->wpdb->insert($table, [
                'usuario_id' => $users[array_rand($users)],
                'titulo' => $recurso[0],
                'descripcion' => $recurso[1],
                'tipo' => $recurso[2],
                'estado' => rand(0, 1) ? 'disponible' : 'prestado',
            ]);

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Genera bicicletas
     * Tabla: bicicletas (codigo, tipo, ubicacion, estado) - codigo tiene UNIQUE KEY
     * Tabla: bicicletas_alquileres (bicicleta_id, usuario_id, fecha_inicio, fecha_fin, estado)
     */
    private function generate_bicicletas() {
        $table_bicis = $this->prefix . 'bicicletas';
        if (!$this->table_exists($table_bicis)) return 0;

        $ubicaciones = [
            'Estación Plaza Mayor',
            'Estación Parque Central',
            'Estación Ayuntamiento',
            'Estación Mercado',
            'Estación Universidad',
            'Estación Centro Deportivo',
        ];

        $tipos = ['urbana', 'eléctrica', 'plegable'];
        $estados = ['disponible', 'disponible', 'disponible', 'en_uso', 'mantenimiento'];

        $bicis_insertadas = 0;

        // Obtener el número más alto de bicicleta existente
        $max_codigo = $this->wpdb->get_var("SELECT MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)) FROM $table_bicis WHERE codigo LIKE 'BICI-%'");
        $siguiente_numero = ($max_codigo ?: 0) + 1;

        for ($i = 0; $i < 30; $i++) {
            $estado = $estados[array_rand($estados)];
            $codigo = 'BICI-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);

            // Verificar si el código ya existe
            $existe = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM $table_bicis WHERE codigo = %s",
                $codigo
            ));

            if ($existe) {
                $siguiente_numero++;
                continue;
            }

            $result = $this->wpdb->insert($table_bicis, [
                'codigo' => $codigo,
                'tipo' => $tipos[array_rand($tipos)],
                'ubicacion' => $ubicaciones[array_rand($ubicaciones)],
                'estado' => $estado,
            ]);

            if ($result) {
                $bicis_insertadas++;
                $bici_id = $this->wpdb->insert_id;
                $siguiente_numero++;

                // Si está en uso, crear un alquiler activo
                if ($estado === 'en_uso') {
                    $users = $this->get_demo_users();
                    $this->wpdb->insert($this->prefix . 'bicicletas_alquileres', [
                        'bicicleta_id' => $bici_id,
                        'usuario_id' => $users[array_rand($users)],
                        'fecha_inicio' => $this->random_date('-2 hours', 'now'),
                        'fecha_fin' => null,
                        'estado' => 'activo',
                    ]);
                }
            }
        }

        return $bicis_insertadas;
    }

    /**
     * Verifica si una tabla existe
     */
    private function table_exists($table) {
        return $this->wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }

    /**
     * Elimina todos los datos de prueba
     */
    public function clear_all() {
        $tables = [
            'eventos_inscripciones', 'eventos',
            'reservas', 'espacios',
            'cursos_inscripciones', 'cursos',
            'talleres', 'avisos',
            'chat_grupos_miembros', 'chat_grupos',
            'foros_temas', 'mensajes',
            'huertos_asignaciones', 'huertos_parcelas',
            'reciclaje_puntos', 'marketplace',
            'tienda_pedidos', 'tienda_productos',
            'banco_tiempo_saldo', 'colectivos_miembros', 'colectivos',
            'socios', 'incidencias', 'tramites',
            'participacion_procesos', 'presupuestos_propuestas',
            'biblioteca_prestamos', 'bares',
            'podcast_episodios', 'carpooling_viajes',
            'grupos_consumo_miembros', 'grupos_consumo',
            'compostaje_aportes', 'ayuda_vecinal',
            'recursos_compartidos', 'bicicletas_alquileres',
            'bicicletas',
        ];

        foreach ($tables as $table) {
            $full_table = $this->prefix . $table;
            if ($this->table_exists($full_table)) {
                $this->wpdb->query("TRUNCATE TABLE $full_table");
            }
        }

        // Eliminar usuarios demo
        $demo_users = get_option('flavor_demo_user_ids', []);
        foreach ($demo_users as $user_id) {
            wp_delete_user($user_id);
        }
        delete_option('flavor_demo_user_ids');

        return true;
    }
}
