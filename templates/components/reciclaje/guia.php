<?php
/**
 * Template: Guía Visual de Reciclaje
 * Guía visual de reciclaje (qué va en cada contenedor con iconos y colores)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? esc_html($args['titulo']) : 'Guía de Reciclaje';
$mostrar_buscador = isset($args['mostrar_buscador']) ? (bool) $args['mostrar_buscador'] : true;
$mostrar_errores_comunes = isset($args['mostrar_errores']) ? (bool) $args['mostrar_errores'] : true;
$columnas_grid = isset($args['columnas']) ? absint($args['columnas']) : 3;
$clase_adicional = isset($args['clase']) ? esc_attr($args['clase']) : '';

// Definición de contenedores con sus residuos permitidos
$contenedores = array(
    'amarillo' => array(
        'nombre' => 'Contenedor Amarillo',
        'subtitulo' => 'Envases ligeros',
        'icono' => '🟡',
        'color' => '#FFD700',
        'color_fondo' => '#fffde7',
        'descripcion' => 'Para envases de plástico, latas y briks',
        'residuos_permitidos' => array(
            array('nombre' => 'Botellas de plástico', 'icono' => '🍾', 'ejemplo' => 'Agua, refrescos, leche'),
            array('nombre' => 'Envases de plástico', 'icono' => '🥤', 'ejemplo' => 'Yogures, bandejas, tarrinas'),
            array('nombre' => 'Latas', 'icono' => '🥫', 'ejemplo' => 'Conservas, refrescos, cerveza'),
            array('nombre' => 'Briks', 'icono' => '🧃', 'ejemplo' => 'Leche, zumos, caldos'),
            array('nombre' => 'Bolsas de plástico', 'icono' => '🛍️', 'ejemplo' => 'Bolsas de la compra'),
            array('nombre' => 'Papel de aluminio', 'icono' => '📄', 'ejemplo' => 'Papel de aluminio limpio'),
            array('nombre' => 'Tapones y tapas', 'icono' => '⭕', 'ejemplo' => 'Tapones de plástico y metal'),
            array('nombre' => 'Aerosoles vacíos', 'icono' => '🧴', 'ejemplo' => 'Desodorantes, ambientadores')
        ),
        'residuos_prohibidos' => array(
            array('nombre' => 'Juguetes de plástico', 'icono' => '🧸'),
            array('nombre' => 'Cubos y barreños', 'icono' => '🪣'),
            array('nombre' => 'Electrodomésticos', 'icono' => '📱')
        ),
        'consejos' => array(
            'Vacía y aplasta los envases para ocupar menos espacio',
            'No es necesario lavarlos, pero sí escurrirlos',
            'Quita los tapones si son de diferente material'
        )
    ),
    'azul' => array(
        'nombre' => 'Contenedor Azul',
        'subtitulo' => 'Papel y cartón',
        'icono' => '🔵',
        'color' => '#2196F3',
        'color_fondo' => '#e3f2fd',
        'descripcion' => 'Para papel y cartón limpio y seco',
        'residuos_permitidos' => array(
            array('nombre' => 'Cajas de cartón', 'icono' => '📦', 'ejemplo' => 'Cajas de cereales, zapatos, envíos'),
            array('nombre' => 'Periódicos y revistas', 'icono' => '📰', 'ejemplo' => 'Prensa, catálogos, folletos'),
            array('nombre' => 'Papel de oficina', 'icono' => '📄', 'ejemplo' => 'Folios, sobres, cuadernos'),
            array('nombre' => 'Envases de cartón', 'icono' => '🥡', 'ejemplo' => 'Hueveras, cajas de pizza limpias'),
            array('nombre' => 'Bolsas de papel', 'icono' => '🛍️', 'ejemplo' => 'Bolsas de la compra'),
            array('nombre' => 'Libros y libretas', 'icono' => '📚', 'ejemplo' => 'Sin tapas de plástico')
        ),
        'residuos_prohibidos' => array(
            array('nombre' => 'Papel sucio o grasiento', 'icono' => '🍕'),
            array('nombre' => 'Pañuelos usados', 'icono' => '🤧'),
            array('nombre' => 'Papel de aluminio', 'icono' => '📄'),
            array('nombre' => 'Briks', 'icono' => '🧃'),
            array('nombre' => 'Papel plastificado', 'icono' => '📋')
        ),
        'consejos' => array(
            'Pliega las cajas para ocupar menos espacio',
            'El papel debe estar limpio y seco',
            'Retira grapas, clips y cintas adhesivas si es posible'
        )
    ),
    'verde' => array(
        'nombre' => 'Contenedor Verde',
        'subtitulo' => 'Vidrio',
        'icono' => '🟢',
        'color' => '#4CAF50',
        'color_fondo' => '#e8f5e9',
        'descripcion' => 'Para envases de vidrio sin tapas ni tapones',
        'residuos_permitidos' => array(
            array('nombre' => 'Botellas de vidrio', 'icono' => '🍾', 'ejemplo' => 'Vino, aceite, refrescos'),
            array('nombre' => 'Tarros de vidrio', 'icono' => '🫙', 'ejemplo' => 'Conservas, mermeladas, salsas'),
            array('nombre' => 'Frascos de vidrio', 'icono' => '🧴', 'ejemplo' => 'Perfumes vacíos, colonias'),
            array('nombre' => 'Botes de vidrio', 'icono' => '🥫', 'ejemplo' => 'Alimentos en conserva')
        ),
        'residuos_prohibidos' => array(
            array('nombre' => 'Espejos', 'icono' => '🪞'),
            array('nombre' => 'Cristales de ventanas', 'icono' => '🪟'),
            array('nombre' => 'Bombillas', 'icono' => '💡'),
            array('nombre' => 'Vajilla y cerámica', 'icono' => '🍽️'),
            array('nombre' => 'Tapones y tapas', 'icono' => '⭕')
        ),
        'consejos' => array(
            'Retira tapones y tapas (van al amarillo)',
            'No es necesario lavar los envases',
            'El vidrio se recicla infinitas veces sin perder calidad'
        )
    ),
    'marron' => array(
        'nombre' => 'Contenedor Marrón',
        'subtitulo' => 'Orgánico',
        'icono' => '🟤',
        'color' => '#795548',
        'color_fondo' => '#efebe9',
        'descripcion' => 'Para restos de comida y residuos biodegradables',
        'residuos_permitidos' => array(
            array('nombre' => 'Restos de comida', 'icono' => '🍽️', 'ejemplo' => 'Sobras, alimentos caducados'),
            array('nombre' => 'Frutas y verduras', 'icono' => '🍎', 'ejemplo' => 'Pieles, cáscaras, huesos'),
            array('nombre' => 'Cáscaras de huevo', 'icono' => '🥚', 'ejemplo' => 'Cáscaras limpias'),
            array('nombre' => 'Posos de café', 'icono' => '☕', 'ejemplo' => 'Con filtro de papel'),
            array('nombre' => 'Bolsitas de infusión', 'icono' => '🍵', 'ejemplo' => 'Té, manzanilla, etc.'),
            array('nombre' => 'Restos de jardinería', 'icono' => '🌿', 'ejemplo' => 'Hojas, flores, césped'),
            array('nombre' => 'Papel de cocina sucio', 'icono' => '🧻', 'ejemplo' => 'Servilletas usadas'),
            array('nombre' => 'Corchos', 'icono' => '🍾', 'ejemplo' => 'Tapones de corcho natural')
        ),
        'residuos_prohibidos' => array(
            array('nombre' => 'Aceite de cocina', 'icono' => '🫒'),
            array('nombre' => 'Pañales', 'icono' => '👶'),
            array('nombre' => 'Colillas', 'icono' => '🚬'),
            array('nombre' => 'Pelo de mascotas', 'icono' => '🐕'),
            array('nombre' => 'Arena de gato', 'icono' => '🐱')
        ),
        'consejos' => array(
            'Usa bolsas compostables o de papel',
            'Evita líquidos y aceites',
            'Se convierte en compost para agricultura'
        )
    ),
    'gris' => array(
        'nombre' => 'Contenedor Gris/Verde Oscuro',
        'subtitulo' => 'Resto / Fracción resto',
        'icono' => '⚫',
        'color' => '#607D8B',
        'color_fondo' => '#eceff1',
        'descripcion' => 'Para residuos que no se pueden reciclar en otros contenedores',
        'residuos_permitidos' => array(
            array('nombre' => 'Pañales y compresas', 'icono' => '👶', 'ejemplo' => 'Productos de higiene'),
            array('nombre' => 'Colillas', 'icono' => '🚬', 'ejemplo' => 'Apagadas y frías'),
            array('nombre' => 'Polvo de barrer', 'icono' => '🧹', 'ejemplo' => 'Residuos de la limpieza'),
            array('nombre' => 'Pelo y cabello', 'icono' => '💇', 'ejemplo' => 'Pelo humano y de mascotas'),
            array('nombre' => 'Chicles', 'icono' => '🫧', 'ejemplo' => 'Envueltos en papel'),
            array('nombre' => 'Cepillos de dientes', 'icono' => '🪥', 'ejemplo' => 'Cepillos usados'),
            array('nombre' => 'Juguetes rotos', 'icono' => '🧸', 'ejemplo' => 'Pequeños, sin pilas'),
            array('nombre' => 'Cerámica rota', 'icono' => '🍽️', 'ejemplo' => 'Platos, tazas rotas')
        ),
        'residuos_prohibidos' => array(
            array('nombre' => 'Pilas y baterías', 'icono' => '🔋'),
            array('nombre' => 'Medicamentos', 'icono' => '💊'),
            array('nombre' => 'Aceite usado', 'icono' => '🫒'),
            array('nombre' => 'Electrodomésticos', 'icono' => '📺'),
            array('nombre' => 'Productos químicos', 'icono' => '⚗️')
        ),
        'consejos' => array(
            'Es el último recurso: primero intenta reciclar',
            'Deposita solo lo que no pueda ir a otro contenedor',
            'Los residuos peligrosos van al punto limpio'
        )
    )
);

// Residuos especiales que van al punto limpio
$residuos_punto_limpio = array(
    array(
        'categoria' => 'Residuos electrónicos (RAEE)',
        'icono' => '📱',
        'items' => array('Móviles', 'Ordenadores', 'Televisores', 'Electrodomésticos', 'Cables')
    ),
    array(
        'categoria' => 'Pilas y baterías',
        'icono' => '🔋',
        'items' => array('Pilas alcalinas', 'Pilas botón', 'Baterías de móvil', 'Baterías de coche')
    ),
    array(
        'categoria' => 'Aceites usados',
        'icono' => '🫒',
        'items' => array('Aceite de cocina', 'Aceite de motor', 'Aceite de fritura')
    ),
    array(
        'categoria' => 'Medicamentos',
        'icono' => '💊',
        'items' => array('Medicamentos caducados', 'Envases de medicamentos (Punto SIGRE en farmacias)')
    ),
    array(
        'categoria' => 'Productos químicos',
        'icono' => '⚗️',
        'items' => array('Pinturas', 'Disolventes', 'Insecticidas', 'Productos de limpieza')
    ),
    array(
        'categoria' => 'Textiles y ropa',
        'icono' => '👕',
        'items' => array('Ropa usada', 'Calzado', 'Bolsos', 'Ropa de cama')
    ),
    array(
        'categoria' => 'Muebles y enseres',
        'icono' => '🪑',
        'items' => array('Muebles viejos', 'Colchones', 'Sofás', 'Electrodomésticos grandes')
    ),
    array(
        'categoria' => 'Escombros pequeños',
        'icono' => '🧱',
        'items' => array('Restos de obra', 'Azulejos rotos', 'Sanitarios')
    )
);

// Errores comunes de reciclaje
$errores_comunes = array(
    array(
        'error' => 'Tirar briks al contenedor azul',
        'icono' => '❌',
        'correccion' => 'Los briks van al contenedor amarillo porque tienen plástico y aluminio',
        'contenedor_correcto' => 'amarillo'
    ),
    array(
        'error' => 'Echar servilletas usadas al azul',
        'icono' => '❌',
        'correccion' => 'El papel sucio o con restos de comida va al contenedor marrón (orgánico)',
        'contenedor_correcto' => 'marron'
    ),
    array(
        'error' => 'Depositar cristales rotos en el verde',
        'icono' => '❌',
        'correccion' => 'Solo envases de vidrio. Cristales de ventanas o espejos van al punto limpio',
        'contenedor_correcto' => 'punto_limpio'
    ),
    array(
        'error' => 'Tirar tapones con las botellas de vidrio',
        'icono' => '❌',
        'correccion' => 'Los tapones de plástico o metal van al contenedor amarillo',
        'contenedor_correcto' => 'amarillo'
    ),
    array(
        'error' => 'Echar aceite por el fregadero',
        'icono' => '❌',
        'correccion' => 'El aceite usado debe llevarse al punto limpio en una botella cerrada',
        'contenedor_correcto' => 'punto_limpio'
    ),
    array(
        'error' => 'Depositar juguetes en el amarillo',
        'icono' => '❌',
        'correccion' => 'Aunque sean de plástico, los juguetes no son envases. Van al punto limpio o contenedor gris',
        'contenedor_correcto' => 'gris'
    )
);
?>

<div class="flavor-guia-reciclaje <?php echo $clase_adicional; ?>">

    <!-- Encabezado -->
    <div class="flavor-guia-header">
        <h2 class="flavor-guia-titulo"><?php echo $titulo_seccion; ?></h2>
        <p class="flavor-guia-subtitulo">Aprende a separar correctamente tus residuos para un reciclaje eficiente</p>
    </div>

    <?php if ($mostrar_buscador) : ?>
    <!-- Buscador de residuos -->
    <div class="flavor-buscador-residuos">
        <div class="flavor-buscador-container">
            <span class="flavor-buscador-icono">🔍</span>
            <input type="text"
                   id="flavor-buscador-input"
                   class="flavor-buscador-input"
                   placeholder="¿Dónde tiro...? Escribe un residuo (ej: botella, yogur, periódico)">
            <button type="button" class="flavor-buscador-limpiar" id="flavor-buscador-limpiar" style="display: none;">✕</button>
        </div>
        <div class="flavor-buscador-resultados" id="flavor-buscador-resultados" style="display: none;"></div>
    </div>
    <?php endif; ?>

    <!-- Grid de contenedores -->
    <div class="flavor-contenedores-grid flavor-grid-cols-<?php echo $columnas_grid; ?>">
        <?php foreach ($contenedores as $tipo_contenedor => $datos_contenedor) : ?>
        <article class="flavor-contenedor-card"
                 id="contenedor-<?php echo esc_attr($tipo_contenedor); ?>"
                 style="border-top: 5px solid <?php echo esc_attr($datos_contenedor['color']); ?>;">

            <!-- Cabecera del contenedor -->
            <div class="flavor-contenedor-header" style="background: <?php echo esc_attr($datos_contenedor['color_fondo']); ?>;">
                <div class="flavor-contenedor-icono-grande" style="background: <?php echo esc_attr($datos_contenedor['color']); ?>;">
                    <?php echo $datos_contenedor['icono']; ?>
                </div>
                <div class="flavor-contenedor-titulo">
                    <h3><?php echo esc_html($datos_contenedor['nombre']); ?></h3>
                    <span class="flavor-contenedor-subtitulo"><?php echo esc_html($datos_contenedor['subtitulo']); ?></span>
                </div>
            </div>

            <p class="flavor-contenedor-descripcion"><?php echo esc_html($datos_contenedor['descripcion']); ?></p>

            <!-- Residuos permitidos -->
            <div class="flavor-residuos-seccion">
                <h4 class="flavor-seccion-titulo flavor-titulo-permitido">
                    <span>✅</span> Qué SÍ depositar
                </h4>
                <ul class="flavor-residuos-lista">
                    <?php foreach ($datos_contenedor['residuos_permitidos'] as $residuo_permitido) : ?>
                    <li class="flavor-residuo-item flavor-residuo-permitido" data-nombre="<?php echo esc_attr(strtolower($residuo_permitido['nombre'])); ?>">
                        <span class="flavor-residuo-icono"><?php echo $residuo_permitido['icono']; ?></span>
                        <div class="flavor-residuo-info">
                            <strong><?php echo esc_html($residuo_permitido['nombre']); ?></strong>
                            <?php if (!empty($residuo_permitido['ejemplo'])) : ?>
                            <small><?php echo esc_html($residuo_permitido['ejemplo']); ?></small>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Residuos prohibidos -->
            <div class="flavor-residuos-seccion">
                <h4 class="flavor-seccion-titulo flavor-titulo-prohibido">
                    <span>❌</span> Qué NO depositar
                </h4>
                <div class="flavor-residuos-prohibidos">
                    <?php foreach ($datos_contenedor['residuos_prohibidos'] as $residuo_prohibido) : ?>
                    <span class="flavor-residuo-badge-prohibido">
                        <?php echo $residuo_prohibido['icono']; ?> <?php echo esc_html($residuo_prohibido['nombre']); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Consejos -->
            <div class="flavor-consejos-seccion">
                <h4 class="flavor-seccion-titulo flavor-titulo-consejos">
                    <span>💡</span> Consejos
                </h4>
                <ul class="flavor-consejos-lista">
                    <?php foreach ($datos_contenedor['consejos'] as $consejo) : ?>
                    <li><?php echo esc_html($consejo); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </article>
        <?php endforeach; ?>
    </div>

    <!-- Punto limpio -->
    <div class="flavor-punto-limpio-seccion">
        <div class="flavor-punto-limpio-header">
            <span class="flavor-punto-limpio-icono">♻️</span>
            <div>
                <h3 class="flavor-punto-limpio-titulo">Punto Limpio</h3>
                <p class="flavor-punto-limpio-subtitulo">Residuos especiales que requieren tratamiento específico</p>
            </div>
        </div>

        <div class="flavor-punto-limpio-grid">
            <?php foreach ($residuos_punto_limpio as $categoria_residuo) : ?>
            <div class="flavor-punto-limpio-categoria">
                <div class="flavor-categoria-header">
                    <span class="flavor-categoria-icono"><?php echo $categoria_residuo['icono']; ?></span>
                    <strong><?php echo esc_html($categoria_residuo['categoria']); ?></strong>
                </div>
                <ul class="flavor-categoria-items">
                    <?php foreach ($categoria_residuo['items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($mostrar_errores_comunes) : ?>
    <!-- Errores comunes -->
    <div class="flavor-errores-seccion">
        <h3 class="flavor-errores-titulo">
            <span>⚠️</span> Errores comunes de reciclaje
        </h3>
        <p class="flavor-errores-subtitulo">Evita estos errores frecuentes para reciclar correctamente</p>

        <div class="flavor-errores-grid">
            <?php foreach ($errores_comunes as $error_reciclaje) : ?>
            <div class="flavor-error-card">
                <div class="flavor-error-header">
                    <span class="flavor-error-icono"><?php echo $error_reciclaje['icono']; ?></span>
                    <strong class="flavor-error-texto"><?php echo esc_html($error_reciclaje['error']); ?></strong>
                </div>
                <div class="flavor-error-correccion">
                    <span class="flavor-correccion-icono">✅</span>
                    <p><?php echo esc_html($error_reciclaje['correccion']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Datos curiosos -->
    <div class="flavor-datos-curiosos">
        <h3 class="flavor-datos-titulo">
            <span>🌍</span> ¿Sabías que...?
        </h3>
        <div class="flavor-datos-grid">
            <div class="flavor-dato-card">
                <span class="flavor-dato-icono">🍾</span>
                <p>Reciclar <strong>una botella de vidrio</strong> ahorra la energía suficiente para mantener una bombilla encendida durante 4 horas</p>
            </div>
            <div class="flavor-dato-card">
                <span class="flavor-dato-icono">📰</span>
                <p>Por cada <strong>tonelada de papel</strong> reciclado se salvan 17 árboles y se ahorran 26.000 litros de agua</p>
            </div>
            <div class="flavor-dato-card">
                <span class="flavor-dato-icono">🥫</span>
                <p>El <strong>aluminio</strong> se puede reciclar infinitas veces sin perder calidad, ahorrando el 95% de la energía</p>
            </div>
            <div class="flavor-dato-card">
                <span class="flavor-dato-icono">🌱</span>
                <p>El <strong>compost</strong> de residuos orgánicos puede reducir hasta un 40% los residuos de un hogar</p>
            </div>
        </div>
    </div>

</div>

<style>
/* Estilos base para guía de reciclaje */
.flavor-guia-reciclaje {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-guia-header {
    text-align: center;
    margin-bottom: 30px;
}

.flavor-guia-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0 0 10px 0;
}

.flavor-guia-subtitulo {
    color: #666;
    font-size: 1.1rem;
    margin: 0;
}

/* Buscador */
.flavor-buscador-residuos {
    max-width: 600px;
    margin: 0 auto 35px;
}

.flavor-buscador-container {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #4CAF50;
    border-radius: 30px;
    padding: 5px 20px;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
}

.flavor-buscador-icono {
    font-size: 1.3rem;
    margin-right: 12px;
}

.flavor-buscador-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 1rem;
    padding: 12px 0;
    background: transparent;
}

.flavor-buscador-input::placeholder {
    color: #999;
}

.flavor-buscador-limpiar {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #999;
    cursor: pointer;
    padding: 5px;
}

.flavor-buscador-limpiar:hover {
    color: #333;
}

.flavor-buscador-resultados {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    margin-top: 10px;
    padding: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.flavor-resultado-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-resultado-item:hover {
    background: #f5f5f5;
}

.flavor-resultado-item:last-child {
    margin-bottom: 0;
}

/* Grid de contenedores */
.flavor-contenedores-grid {
    display: grid;
    gap: 25px;
    margin-bottom: 40px;
}

.flavor-grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-grid-cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

/* Tarjeta de contenedor */
.flavor-contenedor-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.flavor-contenedor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.flavor-contenedor-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
}

.flavor-contenedor-icono-grande {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
}

.flavor-contenedor-titulo h3 {
    margin: 0 0 4px 0;
    font-size: 1.2rem;
    color: #333;
}

.flavor-contenedor-subtitulo {
    color: #666;
    font-size: 0.9rem;
}

.flavor-contenedor-descripcion {
    padding: 0 20px;
    color: #666;
    font-size: 0.95rem;
    margin: 0 0 15px 0;
}

/* Secciones de residuos */
.flavor-residuos-seccion {
    padding: 0 20px 15px;
}

.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.flavor-titulo-permitido {
    color: #2e7d32;
}

.flavor-titulo-prohibido {
    color: #c62828;
}

.flavor-titulo-consejos {
    color: #f57c00;
}

.flavor-residuos-lista {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-residuo-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.flavor-residuo-item:last-child {
    border-bottom: none;
}

.flavor-residuo-icono {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.flavor-residuo-info {
    display: flex;
    flex-direction: column;
}

.flavor-residuo-info strong {
    font-size: 0.9rem;
    color: #333;
}

.flavor-residuo-info small {
    font-size: 0.8rem;
    color: #888;
}

.flavor-residuos-prohibidos {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-residuo-badge-prohibido {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #ffebee;
    color: #c62828;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Consejos */
.flavor-consejos-seccion {
    padding: 0 20px 20px;
}

.flavor-consejos-lista {
    margin: 0;
    padding-left: 20px;
}

.flavor-consejos-lista li {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 6px;
}

.flavor-consejos-lista li:last-child {
    margin-bottom: 0;
}

/* Punto limpio */
.flavor-punto-limpio-seccion {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 40px;
}

.flavor-punto-limpio-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.flavor-punto-limpio-icono {
    font-size: 3rem;
}

.flavor-punto-limpio-titulo {
    margin: 0 0 5px 0;
    color: #2e7d32;
    font-size: 1.5rem;
}

.flavor-punto-limpio-subtitulo {
    margin: 0;
    color: #558b2f;
}

.flavor-punto-limpio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.flavor-punto-limpio-categoria {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-categoria-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e8f5e9;
}

.flavor-categoria-icono {
    font-size: 1.5rem;
}

.flavor-categoria-header strong {
    color: #333;
    font-size: 0.95rem;
}

.flavor-categoria-items {
    margin: 0;
    padding-left: 20px;
}

.flavor-categoria-items li {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 4px;
}

/* Errores comunes */
.flavor-errores-seccion {
    background: #fff8e1;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 40px;
}

.flavor-errores-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 5px 0;
    color: #e65100;
    font-size: 1.4rem;
}

.flavor-errores-subtitulo {
    color: #666;
    margin: 0 0 25px 0;
}

.flavor-errores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.flavor-error-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-error-header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 15px;
}

.flavor-error-icono {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.flavor-error-texto {
    color: #c62828;
    font-size: 0.95rem;
}

.flavor-error-correccion {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #e8f5e9;
    padding: 12px;
    border-radius: 8px;
}

.flavor-correccion-icono {
    font-size: 1.1rem;
    flex-shrink: 0;
}

.flavor-error-correccion p {
    margin: 0;
    font-size: 0.9rem;
    color: #2e7d32;
}

/* Datos curiosos */
.flavor-datos-curiosos {
    background: #e3f2fd;
    border-radius: 16px;
    padding: 30px;
}

.flavor-datos-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 20px 0;
    color: #1565c0;
    font-size: 1.4rem;
}

.flavor-datos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.flavor-dato-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.flavor-dato-icono {
    font-size: 2.5rem;
    margin-bottom: 12px;
}

.flavor-dato-card p {
    margin: 0;
    font-size: 0.95rem;
    color: #333;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-grid-cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-guia-reciclaje {
        padding: 15px;
    }

    .flavor-guia-titulo {
        font-size: 1.5rem;
    }

    .flavor-buscador-input {
        font-size: 0.95rem;
    }

    .flavor-grid-cols-2,
    .flavor-grid-cols-3 {
        grid-template-columns: 1fr;
    }

    .flavor-contenedor-header {
        padding: 15px;
    }

    .flavor-contenedor-icono-grande {
        width: 50px;
        height: 50px;
        font-size: 1.6rem;
    }

    .flavor-punto-limpio-seccion,
    .flavor-errores-seccion,
    .flavor-datos-curiosos {
        padding: 20px;
    }

    .flavor-punto-limpio-header {
        flex-direction: column;
        text-align: center;
    }

    .flavor-errores-grid,
    .flavor-datos-grid,
    .flavor-punto-limpio-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .flavor-residuos-prohibidos {
        flex-direction: column;
    }

    .flavor-residuo-badge-prohibido {
        justify-content: center;
    }

    .flavor-error-card {
        padding: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBuscador = document.getElementById('flavor-buscador-input');
    const botonLimpiar = document.getElementById('flavor-buscador-limpiar');
    const contenedorResultados = document.getElementById('flavor-buscador-resultados');

    if (!inputBuscador) return;

    // Base de datos de residuos para búsqueda
    const baseDeDatosResiduos = [
        // Contenedor amarillo
        { nombre: 'Botella de plástico', contenedor: 'amarillo', icono: '🍾', color: '#FFD700' },
        { nombre: 'Envase de yogur', contenedor: 'amarillo', icono: '🥤', color: '#FFD700' },
        { nombre: 'Lata de conserva', contenedor: 'amarillo', icono: '🥫', color: '#FFD700' },
        { nombre: 'Lata de refresco', contenedor: 'amarillo', icono: '🥫', color: '#FFD700' },
        { nombre: 'Brik de leche', contenedor: 'amarillo', icono: '🧃', color: '#FFD700' },
        { nombre: 'Brik de zumo', contenedor: 'amarillo', icono: '🧃', color: '#FFD700' },
        { nombre: 'Bolsa de plástico', contenedor: 'amarillo', icono: '🛍️', color: '#FFD700' },
        { nombre: 'Papel de aluminio', contenedor: 'amarillo', icono: '📄', color: '#FFD700' },
        { nombre: 'Tapón de plástico', contenedor: 'amarillo', icono: '⭕', color: '#FFD700' },
        { nombre: 'Aerosol vacío', contenedor: 'amarillo', icono: '🧴', color: '#FFD700' },
        { nombre: 'Bandeja de plástico', contenedor: 'amarillo', icono: '🥤', color: '#FFD700' },
        { nombre: 'Film transparente', contenedor: 'amarillo', icono: '📄', color: '#FFD700' },

        // Contenedor azul
        { nombre: 'Caja de cartón', contenedor: 'azul', icono: '📦', color: '#2196F3' },
        { nombre: 'Periódico', contenedor: 'azul', icono: '📰', color: '#2196F3' },
        { nombre: 'Revista', contenedor: 'azul', icono: '📰', color: '#2196F3' },
        { nombre: 'Folio', contenedor: 'azul', icono: '📄', color: '#2196F3' },
        { nombre: 'Cuaderno', contenedor: 'azul', icono: '📄', color: '#2196F3' },
        { nombre: 'Sobre', contenedor: 'azul', icono: '✉️', color: '#2196F3' },
        { nombre: 'Huevera de cartón', contenedor: 'azul', icono: '🥡', color: '#2196F3' },
        { nombre: 'Caja de cereales', contenedor: 'azul', icono: '📦', color: '#2196F3' },
        { nombre: 'Libro', contenedor: 'azul', icono: '📚', color: '#2196F3' },
        { nombre: 'Folleto', contenedor: 'azul', icono: '📄', color: '#2196F3' },

        // Contenedor verde
        { nombre: 'Botella de vino', contenedor: 'verde', icono: '🍾', color: '#4CAF50' },
        { nombre: 'Botella de cerveza', contenedor: 'verde', icono: '🍾', color: '#4CAF50' },
        { nombre: 'Tarro de mermelada', contenedor: 'verde', icono: '🫙', color: '#4CAF50' },
        { nombre: 'Tarro de conserva', contenedor: 'verde', icono: '🫙', color: '#4CAF50' },
        { nombre: 'Frasco de perfume', contenedor: 'verde', icono: '🧴', color: '#4CAF50' },
        { nombre: 'Botella de aceite', contenedor: 'verde', icono: '🍾', color: '#4CAF50' },

        // Contenedor marrón
        { nombre: 'Restos de comida', contenedor: 'marron', icono: '🍽️', color: '#795548' },
        { nombre: 'Cáscara de plátano', contenedor: 'marron', icono: '🍌', color: '#795548' },
        { nombre: 'Piel de naranja', contenedor: 'marron', icono: '🍊', color: '#795548' },
        { nombre: 'Hueso de fruta', contenedor: 'marron', icono: '🍑', color: '#795548' },
        { nombre: 'Cáscara de huevo', contenedor: 'marron', icono: '🥚', color: '#795548' },
        { nombre: 'Posos de café', contenedor: 'marron', icono: '☕', color: '#795548' },
        { nombre: 'Bolsita de té', contenedor: 'marron', icono: '🍵', color: '#795548' },
        { nombre: 'Servilleta usada', contenedor: 'marron', icono: '🧻', color: '#795548' },
        { nombre: 'Hojas secas', contenedor: 'marron', icono: '🍂', color: '#795548' },
        { nombre: 'Flores marchitas', contenedor: 'marron', icono: '🌷', color: '#795548' },
        { nombre: 'Corcho', contenedor: 'marron', icono: '🍾', color: '#795548' },

        // Contenedor gris
        { nombre: 'Pañal', contenedor: 'gris', icono: '👶', color: '#607D8B' },
        { nombre: 'Colilla', contenedor: 'gris', icono: '🚬', color: '#607D8B' },
        { nombre: 'Polvo de barrer', contenedor: 'gris', icono: '🧹', color: '#607D8B' },
        { nombre: 'Chicle', contenedor: 'gris', icono: '🫧', color: '#607D8B' },
        { nombre: 'Cepillo de dientes', contenedor: 'gris', icono: '🪥', color: '#607D8B' },
        { nombre: 'Cerámica rota', contenedor: 'gris', icono: '🍽️', color: '#607D8B' },
        { nombre: 'Pelo', contenedor: 'gris', icono: '💇', color: '#607D8B' },

        // Punto limpio
        { nombre: 'Pila', contenedor: 'punto_limpio', icono: '🔋', color: '#9C27B0' },
        { nombre: 'Batería', contenedor: 'punto_limpio', icono: '🔋', color: '#9C27B0' },
        { nombre: 'Móvil', contenedor: 'punto_limpio', icono: '📱', color: '#9C27B0' },
        { nombre: 'Ordenador', contenedor: 'punto_limpio', icono: '💻', color: '#9C27B0' },
        { nombre: 'Televisor', contenedor: 'punto_limpio', icono: '📺', color: '#9C27B0' },
        { nombre: 'Aceite usado', contenedor: 'punto_limpio', icono: '🫒', color: '#9C27B0' },
        { nombre: 'Medicamento', contenedor: 'punto_limpio', icono: '💊', color: '#9C27B0' },
        { nombre: 'Pintura', contenedor: 'punto_limpio', icono: '🎨', color: '#9C27B0' },
        { nombre: 'Bombilla', contenedor: 'punto_limpio', icono: '💡', color: '#9C27B0' },
        { nombre: 'Ropa usada', contenedor: 'punto_limpio', icono: '👕', color: '#9C27B0' },
        { nombre: 'Mueble', contenedor: 'punto_limpio', icono: '🪑', color: '#9C27B0' },
        { nombre: 'Electrodoméstico', contenedor: 'punto_limpio', icono: '🔌', color: '#9C27B0' }
    ];

    const nombresContenedores = {
        'amarillo': 'Contenedor Amarillo (Envases)',
        'azul': 'Contenedor Azul (Papel/Cartón)',
        'verde': 'Contenedor Verde (Vidrio)',
        'marron': 'Contenedor Marrón (Orgánico)',
        'gris': 'Contenedor Gris (Resto)',
        'punto_limpio': 'Punto Limpio'
    };

    // Evento de escritura en el buscador
    inputBuscador.addEventListener('input', function() {
        const terminoDeBusqueda = this.value.toLowerCase().trim();

        if (terminoDeBusqueda.length < 2) {
            contenedorResultados.style.display = 'none';
            botonLimpiar.style.display = 'none';
            return;
        }

        botonLimpiar.style.display = 'block';

        // Buscar coincidencias
        const resultadosEncontrados = baseDeDatosResiduos.filter(function(residuo) {
            return residuo.nombre.toLowerCase().includes(terminoDeBusqueda);
        });

        if (resultadosEncontrados.length === 0) {
            contenedorResultados.innerHTML = '<p style="color: #666; text-align: center; margin: 0;">No se encontraron resultados. Prueba con otro término.</p>';
            contenedorResultados.style.display = 'block';
            return;
        }

        // Mostrar resultados
        let htmlResultados = '';
        resultadosEncontrados.slice(0, 8).forEach(function(resultado) {
            htmlResultados += '<div class="flavor-resultado-item" data-contenedor="' + resultado.contenedor + '" style="border-left: 4px solid ' + resultado.color + ';">' +
                '<span style="font-size: 1.5rem;">' + resultado.icono + '</span>' +
                '<div>' +
                    '<strong>' + resultado.nombre + '</strong><br>' +
                    '<small style="color: #666;">➜ ' + nombresContenedores[resultado.contenedor] + '</small>' +
                '</div>' +
            '</div>';
        });

        contenedorResultados.innerHTML = htmlResultados;
        contenedorResultados.style.display = 'block';

        // Eventos de clic en resultados
        const elementosResultado = contenedorResultados.querySelectorAll('.flavor-resultado-item');
        elementosResultado.forEach(function(elemento) {
            elemento.addEventListener('click', function() {
                const idContenedor = this.dataset.contenedor;
                const elementoContenedor = document.getElementById('contenedor-' + idContenedor);
                if (elementoContenedor) {
                    elementoContenedor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    elementoContenedor.style.boxShadow = '0 0 0 4px #4CAF50';
                    setTimeout(function() {
                        elementoContenedor.style.boxShadow = '';
                    }, 2000);
                }
            });
        });
    });

    // Botón limpiar
    botonLimpiar.addEventListener('click', function() {
        inputBuscador.value = '';
        contenedorResultados.style.display = 'none';
        botonLimpiar.style.display = 'none';
        inputBuscador.focus();
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(evento) {
        if (!evento.target.closest('.flavor-buscador-residuos')) {
            contenedorResultados.style.display = 'none';
        }
    });
});
</script>
