<?php
/**
 * Template: Sección de intercambio de semillas/cosechas entre hortelanos
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Huertos_Urbanos
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? $args['titulo'] : 'Intercambio entre Hortelanos';
$mostrar_formulario = isset($args['mostrar_formulario']) ? $args['mostrar_formulario'] : true;
$items_por_pagina = isset($args['items_por_pagina']) ? $args['items_por_pagina'] : 12;
$filtro_categoria = isset($args['filtro_categoria']) ? $args['filtro_categoria'] : 'todos';

// Datos de demostración de intercambios
$intercambios_demo = isset($args['intercambios']) ? $args['intercambios'] : [
    [
        'id' => 1,
        'tipo' => 'semillas',
        'titulo' => 'Semillas de tomate cherry ecológico',
        'descripcion' => 'Semillas recolectadas de mi propia cosecha. Variedad muy productiva y resistente. Aproximadamente 50 semillas por sobre.',
        'categoria' => 'semillas',
        'cantidad' => '3 sobres',
        'busco' => 'Semillas de pimiento o calabacín',
        'hortelano' => [
            'nombre' => 'María García',
            'avatar' => '',
            'parcela' => 'A-02',
            'valoracion' => 4.8,
            'intercambios_realizados' => 15
        ],
        'ubicacion' => 'Huerto La Esperanza',
        'fecha_publicacion' => '2024-01-15',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 2,
        'tipo' => 'cosecha',
        'titulo' => 'Lechugas frescas recién cosechadas',
        'descripcion' => 'Lechugas variedad romana, cultivadas sin pesticidas. Perfectas para ensaladas. Recogidas esta mañana.',
        'categoria' => 'hortalizas',
        'cantidad' => '5 unidades',
        'busco' => 'Tomates maduros o zanahorias',
        'hortelano' => [
            'nombre' => 'Carlos López',
            'avatar' => '',
            'parcela' => 'B-01',
            'valoracion' => 4.5,
            'intercambios_realizados' => 8
        ],
        'ubicacion' => 'Huerto La Esperanza',
        'fecha_publicacion' => '2024-01-18',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 3,
        'tipo' => 'semillas',
        'titulo' => 'Semillas de albahaca genovesa',
        'descripcion' => 'Albahaca de hoja grande, ideal para pesto. Germinación garantizada del 90%. Incluye instrucciones de siembra.',
        'categoria' => 'semillas',
        'cantidad' => '2 sobres',
        'busco' => 'Plantones de aromáticas',
        'hortelano' => [
            'nombre' => 'Ana Martínez',
            'avatar' => '',
            'parcela' => 'B-04',
            'valoracion' => 5.0,
            'intercambios_realizados' => 23
        ],
        'ubicacion' => 'Huerto Los Pinos',
        'fecha_publicacion' => '2024-01-17',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 4,
        'tipo' => 'plantones',
        'titulo' => 'Plantones de tomate corazón de buey',
        'descripcion' => 'Plantones de 15cm listos para trasplantar. Variedad tradicional muy sabrosa. Incluyo 6 plantones.',
        'categoria' => 'plantones',
        'cantidad' => '6 plantones',
        'busco' => 'Compost o abono orgánico',
        'hortelano' => [
            'nombre' => 'Pedro Sánchez',
            'avatar' => '',
            'parcela' => 'C-04',
            'valoracion' => 4.2,
            'intercambios_realizados' => 5
        ],
        'ubicacion' => 'Huerto Municipal Centro',
        'fecha_publicacion' => '2024-01-16',
        'estado' => 'reservado',
        'imagenes' => []
    ],
    [
        'id' => 5,
        'tipo' => 'cosecha',
        'titulo' => 'Calabacines de temporada',
        'descripcion' => 'Calabacines medianos perfectos para cocinar. Sin tratamientos químicos. Cosecha de esta semana.',
        'categoria' => 'hortalizas',
        'cantidad' => '4 kg',
        'busco' => 'Cebollas, ajos o patatas',
        'hortelano' => [
            'nombre' => 'Laura Fernández',
            'avatar' => '',
            'parcela' => 'A-05',
            'valoracion' => 4.7,
            'intercambios_realizados' => 12
        ],
        'ubicacion' => 'Huerto Comunitario Norte',
        'fecha_publicacion' => '2024-01-19',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 6,
        'tipo' => 'herramientas',
        'titulo' => 'Préstamo de motocultor pequeño',
        'descripcion' => 'Presto motocultor eléctrico para preparar el terreno. Ideal para parcelas pequeñas. Por días acordados.',
        'categoria' => 'herramientas',
        'cantidad' => 'Préstamo temporal',
        'busco' => 'Ayuda con riego durante vacaciones',
        'hortelano' => [
            'nombre' => 'Miguel Ruiz',
            'avatar' => '',
            'parcela' => 'D-02',
            'valoracion' => 4.9,
            'intercambios_realizados' => 30
        ],
        'ubicacion' => 'Huerto La Esperanza',
        'fecha_publicacion' => '2024-01-14',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 7,
        'tipo' => 'semillas',
        'titulo' => 'Colección de semillas de flores comestibles',
        'descripcion' => 'Pack con semillas de caléndula, capuchina y borraja. Ideales para atraer polinizadores y decorar ensaladas.',
        'categoria' => 'semillas',
        'cantidad' => '1 pack (3 variedades)',
        'busco' => 'Semillas de aromáticas',
        'hortelano' => [
            'nombre' => 'Elena Gómez',
            'avatar' => '',
            'parcela' => 'A-08',
            'valoracion' => 4.6,
            'intercambios_realizados' => 9
        ],
        'ubicacion' => 'Huerto Escolar San Miguel',
        'fecha_publicacion' => '2024-01-20',
        'estado' => 'disponible',
        'imagenes' => []
    ],
    [
        'id' => 8,
        'tipo' => 'cosecha',
        'titulo' => 'Hierbas aromáticas frescas',
        'descripcion' => 'Ramos de romero, tomillo y orégano recién cortados. Perfectos para secar o usar frescos en cocina.',
        'categoria' => 'aromaticas',
        'cantidad' => '3 ramos grandes',
        'busco' => 'Verduras de hoja verde',
        'hortelano' => [
            'nombre' => 'Rosa Díaz',
            'avatar' => '',
            'parcela' => 'B-06',
            'valoracion' => 4.4,
            'intercambios_realizados' => 7
        ],
        'ubicacion' => 'Huerto Los Pinos',
        'fecha_publicacion' => '2024-01-21',
        'estado' => 'disponible',
        'imagenes' => []
    ]
];

// Categorías disponibles
$categorias = [
    'todos' => ['nombre' => 'Todos', 'icono' => '🌿'],
    'semillas' => ['nombre' => 'Semillas', 'icono' => '🌱'],
    'plantones' => ['nombre' => 'Plantones', 'icono' => '🪴'],
    'hortalizas' => ['nombre' => 'Hortalizas', 'icono' => '🥬'],
    'aromaticas' => ['nombre' => 'Aromáticas', 'icono' => '🌿'],
    'herramientas' => ['nombre' => 'Herramientas', 'icono' => '🔧']
];

// Función para obtener el color según el tipo
function obtener_color_tipo_intercambio($tipo) {
    $colores = [
        'semillas' => '#4CAF50',
        'cosecha' => '#FF9800',
        'plantones' => '#8BC34A',
        'herramientas' => '#607D8B'
    ];
    return isset($colores[$tipo]) ? $colores[$tipo] : '#9E9E9E';
}

// Función para obtener el icono según el tipo
function obtener_icono_tipo_intercambio($tipo) {
    $iconos = [
        'semillas' => '🌱',
        'cosecha' => '🧺',
        'plantones' => '🪴',
        'herramientas' => '🔧'
    ];
    return isset($iconos[$tipo]) ? $iconos[$tipo] : '📦';
}

// Función para formatear fecha relativa
function formatear_fecha_intercambio($fecha) {
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;

    if ($diferencia < 86400) {
        return 'Hoy';
    } elseif ($diferencia < 172800) {
        return 'Ayer';
    } elseif ($diferencia < 604800) {
        return 'Hace ' . floor($diferencia / 86400) . ' días';
    } else {
        return date('d/m/Y', $timestamp);
    }
}

// Calcular estadísticas
$total_intercambios = count($intercambios_demo);
$intercambios_disponibles = count(array_filter($intercambios_demo, fn($item) => $item['estado'] === 'disponible'));
?>

<div class="flavor-intercambios-container">
    <header class="flavor-intercambios-header">
        <div class="flavor-header-content">
            <h2 class="flavor-intercambios-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-intercambios-descripcion">
                Comparte semillas, cosechas y conocimientos con otros hortelanos de la comunidad
            </p>
        </div>
        <?php if ($mostrar_formulario) : ?>
        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-publicar">
            <span class="flavor-btn-icono">➕</span>
            Publicar intercambio
        </button>
        <?php endif; ?>
    </header>

    <!-- Estadísticas -->
    <div class="flavor-intercambios-stats">
        <div class="flavor-stat-card">
            <span class="flavor-stat-icono">📦</span>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($total_intercambios); ?></span>
                <span class="flavor-stat-texto">Publicaciones activas</span>
            </div>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-icono">✅</span>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($intercambios_disponibles); ?></span>
                <span class="flavor-stat-texto">Disponibles ahora</span>
            </div>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-icono">🤝</span>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero">156</span>
                <span class="flavor-stat-texto">Intercambios realizados</span>
            </div>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-icono">👥</span>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero">45</span>
                <span class="flavor-stat-texto">Hortelanos participantes</span>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="flavor-intercambios-filtros">
        <div class="flavor-busqueda-grupo">
            <input type="text" class="flavor-busqueda-input" placeholder="Buscar intercambios..." id="flavor-buscar-intercambio">
            <button type="button" class="flavor-btn-buscar">🔍</button>
        </div>
        <div class="flavor-categorias-filtro">
            <?php foreach ($categorias as $categoria_clave => $categoria_info) : ?>
            <button type="button"
                    class="flavor-categoria-btn <?php echo $filtro_categoria === $categoria_clave ? 'active' : ''; ?>"
                    data-categoria="<?php echo esc_attr($categoria_clave); ?>">
                <span class="flavor-categoria-icono"><?php echo esc_html($categoria_info['icono']); ?></span>
                <span class="flavor-categoria-nombre"><?php echo esc_html($categoria_info['nombre']); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <div class="flavor-ordenar-grupo">
            <label for="flavor-ordenar">Ordenar por:</label>
            <select id="flavor-ordenar" class="flavor-select-ordenar">
                <option value="recientes">Más recientes</option>
                <option value="antiguos">Más antiguos</option>
                <option value="valoracion">Mejor valorados</option>
            </select>
        </div>
    </div>

    <!-- Grid de intercambios -->
    <div class="flavor-intercambios-grid">
        <?php foreach ($intercambios_demo as $intercambio) :
            $color_tipo = obtener_color_tipo_intercambio($intercambio['tipo']);
            $icono_tipo = obtener_icono_tipo_intercambio($intercambio['tipo']);
            $fecha_formateada = formatear_fecha_intercambio($intercambio['fecha_publicacion']);
            $es_disponible = $intercambio['estado'] === 'disponible';
        ?>
        <article class="flavor-intercambio-card <?php echo !$es_disponible ? 'flavor-reservado' : ''; ?>"
                 data-categoria="<?php echo esc_attr($intercambio['categoria']); ?>"
                 data-id="<?php echo esc_attr($intercambio['id']); ?>">

            <?php if (!$es_disponible) : ?>
            <div class="flavor-badge-reservado">Reservado</div>
            <?php endif; ?>

            <div class="flavor-card-header" style="border-top-color: <?php echo esc_attr($color_tipo); ?>;">
                <span class="flavor-tipo-badge" style="background: <?php echo esc_attr($color_tipo); ?>;">
                    <?php echo esc_html($icono_tipo); ?> <?php echo esc_html(ucfirst($intercambio['tipo'])); ?>
                </span>
                <span class="flavor-fecha"><?php echo esc_html($fecha_formateada); ?></span>
            </div>

            <div class="flavor-card-body">
                <h3 class="flavor-intercambio-titulo"><?php echo esc_html($intercambio['titulo']); ?></h3>
                <p class="flavor-intercambio-descripcion"><?php echo esc_html($intercambio['descripcion']); ?></p>

                <div class="flavor-intercambio-detalles">
                    <div class="flavor-detalle">
                        <span class="flavor-detalle-icono">📦</span>
                        <span class="flavor-detalle-label">Ofrezco:</span>
                        <span class="flavor-detalle-valor"><?php echo esc_html($intercambio['cantidad']); ?></span>
                    </div>
                    <div class="flavor-detalle flavor-detalle-busco">
                        <span class="flavor-detalle-icono">🔄</span>
                        <span class="flavor-detalle-label">Busco:</span>
                        <span class="flavor-detalle-valor"><?php echo esc_html($intercambio['busco']); ?></span>
                    </div>
                </div>

                <div class="flavor-intercambio-ubicacion">
                    <span class="flavor-ubicacion-icono">📍</span>
                    <span class="flavor-ubicacion-texto"><?php echo esc_html($intercambio['ubicacion']); ?></span>
                </div>
            </div>

            <div class="flavor-card-footer">
                <div class="flavor-hortelano-info">
                    <div class="flavor-hortelano-avatar">
                        <?php echo esc_html(substr($intercambio['hortelano']['nombre'], 0, 1)); ?>
                    </div>
                    <div class="flavor-hortelano-datos">
                        <span class="flavor-hortelano-nombre"><?php echo esc_html($intercambio['hortelano']['nombre']); ?></span>
                        <span class="flavor-hortelano-parcela">Parcela <?php echo esc_html($intercambio['hortelano']['parcela']); ?></span>
                    </div>
                    <div class="flavor-hortelano-rating">
                        <span class="flavor-rating-estrellas">⭐</span>
                        <span class="flavor-rating-valor"><?php echo esc_html($intercambio['hortelano']['valoracion']); ?></span>
                        <span class="flavor-rating-count">(<?php echo esc_html($intercambio['hortelano']['intercambios_realizados']); ?>)</span>
                    </div>
                </div>

                <?php if ($es_disponible) : ?>
                <div class="flavor-card-acciones">
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-mensaje">
                        <span>💬</span> Mensaje
                    </button>
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-intercambiar">
                        <span>🤝</span> Proponer intercambio
                    </button>
                </div>
                <?php else : ?>
                <div class="flavor-card-acciones">
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-disabled" disabled>
                        <span>⏳</span> Ya reservado
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <div class="flavor-intercambios-paginacion">
        <button type="button" class="flavor-btn flavor-btn-pag" disabled>← Anterior</button>
        <div class="flavor-pag-numeros">
            <button type="button" class="flavor-btn-pag-num active">1</button>
            <button type="button" class="flavor-btn-pag-num">2</button>
            <button type="button" class="flavor-btn-pag-num">3</button>
        </div>
        <button type="button" class="flavor-btn flavor-btn-pag">Siguiente →</button>
    </div>

    <?php if ($mostrar_formulario) : ?>
    <!-- Modal de publicar intercambio -->
    <div class="flavor-modal" id="flavor-modal-publicar" style="display: none;">
        <div class="flavor-modal-overlay"></div>
        <div class="flavor-modal-contenido">
            <div class="flavor-modal-header">
                <h3 class="flavor-modal-titulo">Publicar nuevo intercambio</h3>
                <button type="button" class="flavor-modal-cerrar">&times;</button>
            </div>
            <form class="flavor-form-intercambio">
                <div class="flavor-form-grupo">
                    <label class="flavor-form-label">Tipo de intercambio *</label>
                    <div class="flavor-form-radio-grupo">
                        <label class="flavor-radio-opcion">
                            <input type="radio" name="tipo" value="semillas" checked>
                            <span class="flavor-radio-custom">🌱 Semillas</span>
                        </label>
                        <label class="flavor-radio-opcion">
                            <input type="radio" name="tipo" value="cosecha">
                            <span class="flavor-radio-custom">🧺 Cosecha</span>
                        </label>
                        <label class="flavor-radio-opcion">
                            <input type="radio" name="tipo" value="plantones">
                            <span class="flavor-radio-custom">🪴 Plantones</span>
                        </label>
                        <label class="flavor-radio-opcion">
                            <input type="radio" name="tipo" value="herramientas">
                            <span class="flavor-radio-custom">🔧 Herramientas</span>
                        </label>
                    </div>
                </div>
                <div class="flavor-form-grupo">
                    <label class="flavor-form-label" for="titulo-intercambio">Título *</label>
                    <input type="text" id="titulo-intercambio" class="flavor-form-input" placeholder="Ej: Semillas de tomate cherry ecológico" required>
                </div>
                <div class="flavor-form-grupo">
                    <label class="flavor-form-label" for="descripcion-intercambio">Descripción *</label>
                    <textarea id="descripcion-intercambio" class="flavor-form-textarea" rows="4" placeholder="Describe lo que ofreces con detalle..." required></textarea>
                </div>
                <div class="flavor-form-fila">
                    <div class="flavor-form-grupo">
                        <label class="flavor-form-label" for="cantidad-intercambio">Cantidad *</label>
                        <input type="text" id="cantidad-intercambio" class="flavor-form-input" placeholder="Ej: 3 sobres, 2 kg..." required>
                    </div>
                    <div class="flavor-form-grupo">
                        <label class="flavor-form-label" for="categoria-intercambio">Categoría *</label>
                        <select id="categoria-intercambio" class="flavor-form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="semillas">Semillas</option>
                            <option value="plantones">Plantones</option>
                            <option value="hortalizas">Hortalizas</option>
                            <option value="aromaticas">Aromáticas</option>
                            <option value="herramientas">Herramientas</option>
                        </select>
                    </div>
                </div>
                <div class="flavor-form-grupo">
                    <label class="flavor-form-label" for="busco-intercambio">¿Qué buscas a cambio?</label>
                    <input type="text" id="busco-intercambio" class="flavor-form-input" placeholder="Ej: Semillas de pimiento, abono orgánico...">
                </div>
                <div class="flavor-form-grupo">
                    <label class="flavor-form-label">Fotos (opcional)</label>
                    <div class="flavor-form-upload">
                        <input type="file" id="fotos-intercambio" accept="image/*" multiple style="display: none;">
                        <label for="fotos-intercambio" class="flavor-upload-label">
                            <span class="flavor-upload-icono">📷</span>
                            <span class="flavor-upload-texto">Arrastra imágenes o haz clic para subir</span>
                        </label>
                    </div>
                </div>
                <div class="flavor-form-acciones">
                    <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-cancelar">Cancelar</button>
                    <button type="submit" class="flavor-btn flavor-btn-primary">Publicar intercambio</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Consejos de intercambio -->
    <div class="flavor-intercambios-consejos">
        <h3 class="flavor-consejos-titulo">💡 Consejos para un buen intercambio</h3>
        <div class="flavor-consejos-grid">
            <div class="flavor-consejo">
                <span class="flavor-consejo-numero">1</span>
                <p>Describe con detalle lo que ofreces y su estado actual</p>
            </div>
            <div class="flavor-consejo">
                <span class="flavor-consejo-numero">2</span>
                <p>Indica claramente qué te gustaría recibir a cambio</p>
            </div>
            <div class="flavor-consejo">
                <span class="flavor-consejo-numero">3</span>
                <p>Responde rápidamente a los mensajes de interesados</p>
            </div>
            <div class="flavor-consejo">
                <span class="flavor-consejo-numero">4</span>
                <p>Acuerda un punto de encuentro seguro en el huerto</p>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-intercambios-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-intercambios-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-intercambios-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0;
}

.flavor-intercambios-descripcion {
    color: #666;
    margin: 5px 0 0 0;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-primary {
    background: #4CAF50;
    color: white;
}

.flavor-btn-primary:hover {
    background: #388E3C;
}

.flavor-btn-secondary {
    background: #757575;
    color: white;
}

.flavor-btn-outline {
    background: transparent;
    border: 1px solid #4CAF50;
    color: #4CAF50;
}

.flavor-btn-outline:hover {
    background: #e8f5e9;
}

.flavor-btn-sm {
    padding: 8px 14px;
    font-size: 0.85rem;
}

.flavor-btn-disabled {
    background: #e0e0e0;
    color: #999;
    cursor: not-allowed;
}

.flavor-intercambios-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.flavor-stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.flavor-stat-icono {
    font-size: 2rem;
}

.flavor-stat-info {
    display: flex;
    flex-direction: column;
}

.flavor-stat-numero {
    font-size: 1.75rem;
    font-weight: 700;
    color: #333;
}

.flavor-stat-texto {
    font-size: 0.875rem;
    color: #666;
}

.flavor-intercambios-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 12px;
    margin-bottom: 30px;
}

.flavor-busqueda-grupo {
    display: flex;
    flex: 1;
    min-width: 250px;
    max-width: 400px;
}

.flavor-busqueda-input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px 0 0 8px;
    font-size: 0.95rem;
}

.flavor-btn-buscar {
    padding: 12px 15px;
    background: #4CAF50;
    border: none;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
}

.flavor-categorias-filtro {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-categoria-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.flavor-categoria-btn:hover {
    border-color: #4CAF50;
}

.flavor-categoria-btn.active {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-ordenar-grupo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
}

.flavor-ordenar-grupo label {
    font-size: 0.875rem;
    color: #666;
}

.flavor-select-ordenar {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
}

.flavor-intercambios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-intercambio-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.flavor-intercambio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.flavor-intercambio-card.flavor-reservado {
    opacity: 0.8;
}

.flavor-badge-reservado {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #FF9800;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 1;
}

.flavor-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fafafa;
    border-top: 4px solid;
}

.flavor-tipo-badge {
    padding: 5px 12px;
    border-radius: 20px;
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
}

.flavor-fecha {
    font-size: 0.8rem;
    color: #999;
}

.flavor-card-body {
    padding: 15px;
}

.flavor-intercambio-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 10px 0;
    line-height: 1.4;
}

.flavor-intercambio-descripcion {
    font-size: 0.9rem;
    color: #666;
    margin: 0 0 15px 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flavor-intercambio-detalles {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.flavor-detalle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}

.flavor-detalle-icono {
    font-size: 1rem;
}

.flavor-detalle-label {
    color: #666;
}

.flavor-detalle-valor {
    color: #333;
    font-weight: 500;
}

.flavor-detalle-busco .flavor-detalle-valor {
    color: #4CAF50;
}

.flavor-intercambio-ubicacion {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #999;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.flavor-card-footer {
    padding: 15px;
    background: #fafafa;
    border-top: 1px solid #eee;
}

.flavor-hortelano-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.flavor-hortelano-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #4CAF50, #81C784);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.flavor-hortelano-datos {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.flavor-hortelano-nombre {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.flavor-hortelano-parcela {
    font-size: 0.8rem;
    color: #999;
}

.flavor-hortelano-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
}

.flavor-rating-valor {
    font-weight: 600;
    color: #333;
}

.flavor-rating-count {
    color: #999;
}

.flavor-card-acciones {
    display: flex;
    gap: 10px;
}

.flavor-card-acciones .flavor-btn {
    flex: 1;
    justify-content: center;
}

.flavor-intercambios-paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
}

.flavor-btn-pag {
    padding: 10px 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-pag:hover:not(:disabled) {
    border-color: #4CAF50;
    color: #4CAF50;
}

.flavor-btn-pag:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.flavor-pag-numeros {
    display: flex;
    gap: 5px;
}

.flavor-btn-pag-num {
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-pag-num.active,
.flavor-btn-pag-num:hover {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
}

.flavor-modal-contenido {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.flavor-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.flavor-modal-titulo {
    font-size: 1.25rem;
    color: #333;
    margin: 0;
}

.flavor-modal-cerrar {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #999;
    cursor: pointer;
    padding: 5px 10px;
}

.flavor-form-intercambio {
    padding: 20px;
}

.flavor-form-grupo {
    margin-bottom: 20px;
}

.flavor-form-label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.flavor-form-input,
.flavor-form-select,
.flavor-form-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.flavor-form-input:focus,
.flavor-form-select:focus,
.flavor-form-textarea:focus {
    outline: none;
    border-color: #4CAF50;
}

.flavor-form-fila {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.flavor-form-radio-grupo {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-radio-opcion {
    cursor: pointer;
}

.flavor-radio-opcion input {
    display: none;
}

.flavor-radio-custom {
    display: inline-block;
    padding: 10px 16px;
    border: 1px solid #ddd;
    border-radius: 20px;
    transition: all 0.2s;
}

.flavor-radio-opcion input:checked + .flavor-radio-custom {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-form-upload {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: border-color 0.2s;
}

.flavor-form-upload:hover {
    border-color: #4CAF50;
}

.flavor-upload-label {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.flavor-upload-icono {
    font-size: 2rem;
}

.flavor-upload-texto {
    color: #666;
    font-size: 0.9rem;
}

.flavor-form-acciones {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

/* Consejos */
.flavor-intercambios-consejos {
    background: #e8f5e9;
    padding: 30px;
    border-radius: 12px;
}

.flavor-consejos-titulo {
    text-align: center;
    font-size: 1.25rem;
    color: #2e7d32;
    margin: 0 0 20px 0;
}

.flavor-consejos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.flavor-consejo {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: white;
    padding: 15px;
    border-radius: 8px;
}

.flavor-consejo-numero {
    width: 30px;
    height: 30px;
    background: #4CAF50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.flavor-consejo p {
    margin: 0;
    font-size: 0.9rem;
    color: #555;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-intercambios-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-btn-publicar {
        width: 100%;
        justify-content: center;
    }

    .flavor-intercambios-filtros {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-busqueda-grupo {
        max-width: none;
    }

    .flavor-ordenar-grupo {
        margin-left: 0;
    }

    .flavor-intercambios-grid {
        grid-template-columns: 1fr;
    }

    .flavor-form-fila {
        grid-template-columns: 1fr;
    }

    .flavor-form-acciones {
        flex-direction: column;
    }

    .flavor-card-acciones {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .flavor-intercambios-container {
        padding: 15px;
    }

    .flavor-intercambios-titulo {
        font-size: 1.5rem;
    }

    .flavor-stat-card {
        padding: 15px;
    }

    .flavor-stat-numero {
        font-size: 1.5rem;
    }

    .flavor-modal-contenido {
        width: 95%;
        margin: 10px;
    }

    .flavor-intercambios-paginacion {
        flex-wrap: wrap;
    }
}
</style>
