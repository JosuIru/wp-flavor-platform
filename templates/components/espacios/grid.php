<?php
/**
 * Template: Grid de Espacios Disponibles
 *
 * Muestra un grid con los diferentes espacios disponibles para reserva
 * (sala reuniones, auditorio, coworking, etc.)
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Nuestros Espacios';
$subtitulo_seccion = isset($args['subtitulo_seccion']) ? $args['subtitulo_seccion'] : 'Encuentra el espacio perfecto para tu próxima reunión, evento o jornada de trabajo';
$espacios = isset($args['espacios']) ? $args['espacios'] : array();
$columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$texto_boton_reservar = isset($args['texto_boton_reservar']) ? $args['texto_boton_reservar'] : 'Reservar';
$texto_boton_detalle = isset($args['texto_boton_detalle']) ? $args['texto_boton_detalle'] : 'Ver Detalles';

// Datos de demostración
if (empty($espacios)) {
    $espacios = array(
        array(
            'id' => 1,
            'nombre' => 'Sala de Reuniones Premium',
            'tipo' => 'reunion',
            'descripcion' => 'Espacio elegante con equipamiento audiovisual de última generación, ideal para presentaciones ejecutivas.',
            'capacidad' => 12,
            'precio_hora' => 45,
            'imagen' => '',
            'caracteristicas' => array('WiFi', 'Proyector 4K', 'Videoconferencia', 'Pizarra Digital', 'Aire Acondicionado'),
            'disponible' => true,
            'calificacion' => 4.8,
            'reservas_mes' => 67
        ),
        array(
            'id' => 2,
            'nombre' => 'Auditorio Principal',
            'tipo' => 'auditorio',
            'descripcion' => 'Auditorio con capacidad para grandes eventos, conferencias y presentaciones corporativas.',
            'capacidad' => 150,
            'precio_hora' => 200,
            'imagen' => '',
            'caracteristicas' => array('Escenario', 'Sistema de Sonido', 'Iluminación Profesional', 'Camerinos', 'Accesibilidad'),
            'disponible' => true,
            'calificacion' => 4.9,
            'reservas_mes' => 23
        ),
        array(
            'id' => 3,
            'nombre' => 'Espacio Coworking Abierto',
            'tipo' => 'coworking',
            'descripcion' => 'Área de trabajo flexible con escritorios compartidos y ambiente colaborativo.',
            'capacidad' => 30,
            'precio_hora' => 15,
            'imagen' => '',
            'caracteristicas' => array('WiFi Alta Velocidad', 'Café Gratis', 'Lockers', 'Zona de Descanso', 'Impresora'),
            'disponible' => true,
            'calificacion' => 4.6,
            'reservas_mes' => 145
        ),
        array(
            'id' => 4,
            'nombre' => 'Sala Creativa',
            'tipo' => 'creatividad',
            'descripcion' => 'Espacio diseñado para brainstorming y sesiones creativas con mobiliario modular.',
            'capacidad' => 8,
            'precio_hora' => 35,
            'imagen' => '',
            'caracteristicas' => array('Pizarras Móviles', 'Material Creativo', 'Iluminación Natural', 'Música Ambiente', 'Pufs'),
            'disponible' => false,
            'calificacion' => 4.7,
            'reservas_mes' => 52
        ),
        array(
            'id' => 5,
            'nombre' => 'Oficina Privada Ejecutiva',
            'tipo' => 'oficina',
            'descripcion' => 'Oficina privada totalmente equipada para trabajo concentrado o reuniones confidenciales.',
            'capacidad' => 4,
            'precio_hora' => 55,
            'imagen' => '',
            'caracteristicas' => array('Insonorización', 'Escritorio Ejecutivo', 'Mini Nevera', 'TV 55"', 'Seguridad'),
            'disponible' => true,
            'calificacion' => 4.9,
            'reservas_mes' => 89
        ),
        array(
            'id' => 6,
            'nombre' => 'Terraza Eventos',
            'tipo' => 'exterior',
            'descripcion' => 'Hermosa terraza con vistas panorámicas para eventos sociales y networking.',
            'capacidad' => 80,
            'precio_hora' => 120,
            'imagen' => '',
            'caracteristicas' => array('Vista Panorámica', 'Mobiliario Exterior', 'Iluminación LED', 'Barra', 'Toldo Retráctil'),
            'disponible' => true,
            'calificacion' => 4.8,
            'reservas_mes' => 34
        )
    );
}

// Tipos de espacios para filtros
$tipos_espacio = array(
    'todos' => 'Todos',
    'reunion' => 'Salas de Reuniones',
    'auditorio' => 'Auditorios',
    'coworking' => 'Coworking',
    'creatividad' => 'Espacios Creativos',
    'oficina' => 'Oficinas Privadas',
    'exterior' => 'Exteriores'
);
?>

<section class="flavor-espacios-grid" id="espacios">
    <div class="flavor-espacios-grid__container">
        <header class="flavor-espacios-grid__header">
            <h2 class="flavor-espacios-grid__titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-espacios-grid__subtitulo"><?php echo esc_html($subtitulo_seccion); ?></p>
        </header>

        <?php if ($mostrar_filtros) : ?>
        <div class="flavor-espacios-grid__filtros">
            <?php foreach ($tipos_espacio as $tipo_clave => $tipo_nombre) : ?>
            <button class="flavor-espacios-grid__filtro <?php echo $tipo_clave === 'todos' ? 'flavor-espacios-grid__filtro--activo' : ''; ?>" data-filtro="<?php echo esc_attr($tipo_clave); ?>">
                <?php echo esc_html($tipo_nombre); ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="flavor-espacios-grid__lista" style="--columnas: <?php echo esc_attr($columnas); ?>;">
            <?php foreach ($espacios as $espacio) : ?>
            <article class="flavor-espacios-grid__item" data-tipo="<?php echo esc_attr($espacio['tipo']); ?>">
                <div class="flavor-espacios-grid__imagen">
                    <?php if (!empty($espacio['imagen'])) : ?>
                        <img src="<?php echo esc_url($espacio['imagen']); ?>" alt="<?php echo esc_attr($espacio['nombre']); ?>">
                    <?php else : ?>
                        <div class="flavor-espacios-grid__imagen-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <?php if (!$espacio['disponible']) : ?>
                    <span class="flavor-espacios-grid__badge flavor-espacios-grid__badge--ocupado">Ocupado</span>
                    <?php else : ?>
                    <span class="flavor-espacios-grid__badge flavor-espacios-grid__badge--disponible">Disponible</span>
                    <?php endif; ?>

                    <div class="flavor-espacios-grid__precio">
                        <span class="flavor-espacios-grid__precio-valor">$<?php echo esc_html($espacio['precio_hora']); ?></span>
                        <span class="flavor-espacios-grid__precio-unidad">/hora</span>
                    </div>
                </div>

                <div class="flavor-espacios-grid__contenido">
                    <div class="flavor-espacios-grid__meta">
                        <span class="flavor-espacios-grid__calificacion">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <?php echo esc_html($espacio['calificacion']); ?>
                        </span>
                        <span class="flavor-espacios-grid__reservas">
                            <?php echo esc_html($espacio['reservas_mes']); ?> reservas este mes
                        </span>
                    </div>

                    <h3 class="flavor-espacios-grid__nombre"><?php echo esc_html($espacio['nombre']); ?></h3>

                    <p class="flavor-espacios-grid__descripcion"><?php echo esc_html($espacio['descripcion']); ?></p>

                    <div class="flavor-espacios-grid__capacidad">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Hasta <?php echo esc_html($espacio['capacidad']); ?> personas</span>
                    </div>

                    <div class="flavor-espacios-grid__caracteristicas">
                        <?php
                        $caracteristicas_mostrar = array_slice($espacio['caracteristicas'], 0, 3);
                        foreach ($caracteristicas_mostrar as $caracteristica) :
                        ?>
                        <span class="flavor-espacios-grid__caracteristica"><?php echo esc_html($caracteristica); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($espacio['caracteristicas']) > 3) : ?>
                        <span class="flavor-espacios-grid__caracteristica flavor-espacios-grid__caracteristica--mas">
                            +<?php echo count($espacio['caracteristicas']) - 3; ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-espacios-grid__acciones">
                        <a href="#reservar-<?php echo esc_attr($espacio['id']); ?>" class="flavor-espacios-grid__boton flavor-espacios-grid__boton--primario" <?php echo !$espacio['disponible'] ? 'disabled' : ''; ?>>
                            <?php echo esc_html($texto_boton_reservar); ?>
                        </a>
                        <a href="#detalle-<?php echo esc_attr($espacio['id']); ?>" class="flavor-espacios-grid__boton flavor-espacios-grid__boton--secundario">
                            <?php echo esc_html($texto_boton_detalle); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.flavor-espacios-grid {
    padding: 80px 0;
    background: #f8fafc;
}

.flavor-espacios-grid__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.flavor-espacios-grid__header {
    text-align: center;
    margin-bottom: 40px;
}

.flavor-espacios-grid__titulo {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px;
}

.flavor-espacios-grid__subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0;
    max-width: 600px;
    margin: 0 auto;
}

.flavor-espacios-grid__filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 40px;
}

.flavor-espacios-grid__filtro {
    padding: 10px 20px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.flavor-espacios-grid__filtro:hover {
    border-color: #667eea;
    color: #667eea;
}

.flavor-espacios-grid__filtro--activo {
    background: #667eea;
    color: #ffffff;
    border-color: #667eea;
}

.flavor-espacios-grid__lista {
    display: grid;
    grid-template-columns: repeat(var(--columnas, 3), 1fr);
    gap: 30px;
}

.flavor-espacios-grid__item {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.flavor-espacios-grid__item:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.flavor-espacios-grid__imagen {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.flavor-espacios-grid__imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-espacios-grid__imagen-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: rgba(255, 255, 255, 0.5);
}

.flavor-espacios-grid__badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

.flavor-espacios-grid__badge--disponible {
    background: #10b981;
    color: #ffffff;
}

.flavor-espacios-grid__badge--ocupado {
    background: #ef4444;
    color: #ffffff;
}

.flavor-espacios-grid__precio {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0, 0, 0, 0.7);
    padding: 8px 14px;
    border-radius: 8px;
    color: #ffffff;
}

.flavor-espacios-grid__precio-valor {
    font-size: 1.25rem;
    font-weight: 700;
}

.flavor-espacios-grid__precio-unidad {
    font-size: 0.75rem;
    opacity: 0.8;
}

.flavor-espacios-grid__contenido {
    padding: 24px;
}

.flavor-espacios-grid__meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
    font-size: 0.875rem;
}

.flavor-espacios-grid__calificacion {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #f59e0b;
    font-weight: 600;
}

.flavor-espacios-grid__reservas {
    color: #64748b;
}

.flavor-espacios-grid__nombre {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px;
}

.flavor-espacios-grid__descripcion {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 16px;
}

.flavor-espacios-grid__capacidad {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #475569;
    margin-bottom: 16px;
}

.flavor-espacios-grid__caracteristicas {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.flavor-espacios-grid__caracteristica {
    padding: 4px 10px;
    font-size: 0.75rem;
    color: #667eea;
    background: #eef2ff;
    border-radius: 4px;
}

.flavor-espacios-grid__caracteristica--mas {
    background: #f1f5f9;
    color: #64748b;
}

.flavor-espacios-grid__acciones {
    display: flex;
    gap: 12px;
}

.flavor-espacios-grid__boton {
    flex: 1;
    padding: 12px 16px;
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.flavor-espacios-grid__boton--primario {
    background: #667eea;
    color: #ffffff;
}

.flavor-espacios-grid__boton--primario:hover {
    background: #5a67d8;
}

.flavor-espacios-grid__boton--primario[disabled] {
    background: #cbd5e1;
    cursor: not-allowed;
}

.flavor-espacios-grid__boton--secundario {
    background: #f1f5f9;
    color: #475569;
}

.flavor-espacios-grid__boton--secundario:hover {
    background: #e2e8f0;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-espacios-grid__lista {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-espacios-grid {
        padding: 60px 0;
    }

    .flavor-espacios-grid__titulo {
        font-size: 2rem;
    }

    .flavor-espacios-grid__filtros {
        gap: 8px;
    }

    .flavor-espacios-grid__filtro {
        padding: 8px 16px;
        font-size: 0.8125rem;
    }
}

@media (max-width: 576px) {
    .flavor-espacios-grid__lista {
        grid-template-columns: 1fr;
    }

    .flavor-espacios-grid__acciones {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtros = document.querySelectorAll('.flavor-espacios-grid__filtro');
    const items = document.querySelectorAll('.flavor-espacios-grid__item');

    filtros.forEach(function(filtro) {
        filtro.addEventListener('click', function() {
            const tipoSeleccionado = this.getAttribute('data-filtro');

            // Actualizar estado activo de filtros
            filtros.forEach(function(filtroItem) {
                filtroItem.classList.remove('flavor-espacios-grid__filtro--activo');
            });
            this.classList.add('flavor-espacios-grid__filtro--activo');

            // Filtrar items
            items.forEach(function(item) {
                const tipoItem = item.getAttribute('data-tipo');
                if (tipoSeleccionado === 'todos' || tipoItem === tipoSeleccionado) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>
