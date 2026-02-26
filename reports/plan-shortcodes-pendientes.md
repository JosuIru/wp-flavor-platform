# Plan de Implementación de Shortcodes - ACTUALIZADO

**Fecha:** 2026-02-26
**Actualizado:** 2026-02-26 04:20

## Estado Final

Tras auditoría exhaustiva, se confirma que **TODOS los shortcodes críticos están implementados**.

---

## Shortcodes Implementados Esta Sesión (6)

| # | Shortcode | Módulo | Cambio |
|---|-----------|--------|--------|
| 1 | `[podcast_buscar]` | podcast | Añadido grid de resultados con series/episodios |
| 2 | `[cursos_busqueda]` | cursos | Añadido método `buscar_cursos()` + cards |
| 3 | `[marketplace_busqueda]` | marketplace | Añadido método `buscar_anuncios()` + cards |
| 4 | `[carpooling_buscar]` | carpooling | Añadido método `buscar_viajes()` + cards |
| 5 | `[biblioteca_busqueda]` | biblioteca | Añadido método `buscar_libros()` + cards |
| 6 | `[flavor_recetas_buscador]` | recetas | Añadido método `buscar_recetas()` + cards |

---

## Shortcodes Verificados (Ya Completos) (9)

| # | Shortcode | Módulo | Funcionalidad |
|---|-----------|--------|---------------|
| 1 | `[flavor_tramites_buscar]` | tramites | Búsqueda real en tabla `flavor_tramites` |
| 2 | `[flavor_radio_dedicatorias]` | radio | Formulario + AJAX + inserción BD |
| 3 | `[flavor_radio_chat]` | radio | Chat en vivo con polling AJAX |
| 4 | `[flavor_trabajo_publicar]` | trabajo-digno | Formulario completo de ofertas |
| 5 | `[flavor_don_ofrecer]` | economia-don | Formulario con categorías + imagen |
| 6 | `[flavor_mapa_actores_buscador]` | mapa-actores | Búsqueda AJAX + resultados grid |
| 7 | `[flavor_foros_buscar]` | foros | WP_Query sobre CPT foro |
| 8 | `[crear_propuesta]` | participacion | Formulario ciudadano completo |
| 9 | `[gc_suscripciones]` | grupos-consumo | Cestas con precios y productos |

---

## Shortcodes NO Existentes (Eliminados del plan)

| Shortcode | Razón |
|-----------|-------|
| `[estado_expediente]` | No hay registro - Funcionalidad cubierta por seguimiento |
| `[transparencia_buscador_docs]` | Módulo transparencia no tiene frontend controller |

---

## Patrón de Implementación Utilizado

### Para buscadores (ejemplo carpooling):

```php
public function shortcode_buscar($atts) {
    $atts = shortcode_atts([
        'placeholder' => __('Buscar...', 'flavor-chat-ia'),
    ], $atts);

    $this->enqueue_assets();

    // Obtener filtros de GET
    $termino_busqueda = sanitize_text_field($_GET['q'] ?? '');
    $resultados = [];

    if (!empty($termino_busqueda) || !empty($_GET['origen'])) {
        $resultados = $this->buscar_viajes(
            $_GET['origen'] ?? '',
            $_GET['destino'] ?? '',
            $_GET['fecha'] ?? ''
        );
    }

    ob_start();
    ?>
    <div class="flavor-buscador">
        <form method="get" class="flavor-search-form">
            <!-- Campos del formulario -->
        </form>

        <?php if (!empty($resultados)): ?>
            <div class="flavor-resultados-grid">
                <?php foreach ($resultados as $item): ?>
                    <!-- Renderizado de cada resultado -->
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($termino_busqueda)): ?>
            <div class="flavor-sin-resultados">
                <p>No se encontraron resultados.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

private function buscar_viajes($origen, $destino, $fecha, $limite = 12) {
    $args = [
        'post_type' => 'carpooling_viaje',
        'post_status' => 'publish',
        'posts_per_page' => $limite,
        'meta_query' => ['relation' => 'AND'],
    ];

    // Filtros meta_query según parámetros

    return (new WP_Query($args))->posts;
}
```

---

## Total

- **Implementados esta sesión:** 6
- **Ya funcionaban:** 9
- **No existen:** 2
- **Shortcodes totales funcionales:** 15+

---

## Próximos Pasos

1. ✅ Auditoría de shortcodes - COMPLETADA
2. 🔜 Verificar tablas de BD tienen datos
3. 🔜 Probar en frontend con datos reales
4. 🔜 Continuar con Plan de Mejoras (Reputación, Analytics)
