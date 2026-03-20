# Referencia de Shortcodes por Módulo

## Uso General

```php
[shortcode_name atributo="valor" otro="valor"]
```

En VBP, los shortcodes se usan como bloques:
```json
{
  "type": "gc-catalogo",
  "data": {
    "limite": 8,
    "columnas": 4
  }
}
```

---

## Grupos de Consumo

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `gc_catalogo` | Catálogo de productos | `limite`, `columnas`, `grupo_id`, `categoria` |
| `gc_grupos_lista` | Listado de grupos | `limite`, `mostrar_mapa` |
| `gc_pedido_form` | Formulario de pedido | `grupo_id`, `ciclo_id` |
| `gc_mi_cuenta` | Panel del usuario | - |
| `gc_precio_transparente` | Desglose de precios | `producto_id` |
| `gc_proximos_ciclos` | Próximos ciclos de pedido | `limite` |
| `gc_productores` | Listado de productores | `limite`, `columnas` |
| `gc_estadisticas` | Estadísticas del grupo | `grupo_id` |

**Ejemplo en VBP:**
```json
{
  "type": "gc-catalogo",
  "data": {
    "titulo": "Productos Disponibles",
    "limite": 12,
    "columnas": 4,
    "mostrar_filtros": true,
    "mostrar_precio": true,
    "esquema_color": "success"
  }
}
```

---

## Eventos

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `eventos_proximos` | Próximos eventos | `limite`, `categoria`, `columnas` |
| `ev_calendario` | Calendario mensual | `mes`, `año` |
| `ev_detalle` | Detalle de evento | `id` |
| `ev_inscripcion` | Formulario inscripción | `evento_id` |
| `ev_mis_eventos` | Mis inscripciones | - |
| `ev_mapa` | Mapa de eventos | `limite` |
| `ev_voluntariado_eventos` | Oportunidades voluntariado | `limite` |

**Ejemplo en VBP:**
```json
{
  "type": "eventos-proximos",
  "data": {
    "titulo": "Próximos Eventos",
    "limite": 6,
    "columnas": 3,
    "mostrar_fecha": true,
    "mostrar_ubicacion": true
  }
}
```

---

## Socios

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `socios_listado` | Listado de socios | `limite`, `columnas`, `tipo` |
| `socios_perfil` | Perfil del socio | `id` |
| `socios_registro` | Formulario alta | - |
| `socios_cuotas` | Estado de cuotas | - |
| `socios_directorio` | Directorio búsqueda | `mostrar_filtros` |
| `socios_stats` | Estadísticas | - |
| `socios_carnet` | Carnet digital | - |

**Ejemplo en VBP:**
```json
{
  "type": "socios-listado",
  "data": {
    "titulo": "Nuestros Socios",
    "limite": 12,
    "columnas": 4,
    "mostrar_avatar": true,
    "mostrar_cargo": true
  }
}
```

---

## Marketplace

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `marketplace_productos` | Listado productos | `limite`, `columnas`, `categoria`, `vendedor` |
| `marketplace_categorias` | Categorías | `mostrar_contador` |
| `marketplace_vendedores` | Vendedores | `limite`, `columnas` |
| `marketplace_buscar` | Buscador | - |
| `marketplace_mi_tienda` | Panel vendedor | - |
| `marketplace_carrito` | Carrito de compra | - |
| `marketplace_destacados` | Productos destacados | `limite` |

**Ejemplo en VBP:**
```json
{
  "type": "marketplace-productos",
  "data": {
    "titulo": "Productos del Marketplace",
    "limite": 8,
    "columnas": 4,
    "mostrar_precio": true,
    "mostrar_vendedor": true,
    "categoria": ""
  }
}
```

---

## Cursos

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `cursos_catalogo` | Catálogo de cursos | `limite`, `columnas`, `categoria` |
| `cursos_aula` | Aula virtual | `curso_id` |
| `cursos_mis_cursos` | Mis cursos | - |
| `cursos_certificado` | Certificado | `curso_id`, `usuario_id` |
| `cursos_progreso` | Barra de progreso | `curso_id` |
| `cursos_leccion` | Contenido lección | `leccion_id` |
| `cursos_instructores` | Listado instructores | `limite` |

**Ejemplo en VBP:**
```json
{
  "type": "cursos-catalogo",
  "data": {
    "titulo": "Nuestros Cursos",
    "limite": 6,
    "columnas": 3,
    "mostrar_duracion": true,
    "mostrar_nivel": true,
    "mostrar_precio": true
  }
}
```

---

## Reservas

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `reservas_disponibilidad` | Calendario disponibilidad | `recurso_id` |
| `reservas_formulario` | Formulario reserva | `recurso_id` |
| `reservas_mis_reservas` | Mis reservas | - |
| `reservas_recursos` | Listado recursos | `categoria`, `limite` |
| `reservas_calendario` | Calendario general | - |

**Ejemplo en VBP:**
```json
{
  "type": "reservas-recursos",
  "data": {
    "titulo": "Espacios Disponibles",
    "limite": 6,
    "columnas": 3,
    "mostrar_disponibilidad": true
  }
}
```

---

## Incidencias

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `incidencias_crear` | Formulario nueva incidencia | `categoria` |
| `incidencias_listado` | Listado incidencias | `limite`, `estado` |
| `incidencias_mis` | Mis incidencias | - |
| `incidencias_detalle` | Detalle incidencia | `id` |
| `incidencias_mapa` | Mapa de incidencias | - |
| `incidencias_stats` | Estadísticas | - |

**Ejemplo en VBP:**
```json
{
  "type": "incidencias-crear",
  "data": {
    "titulo": "Reportar Incidencia",
    "mostrar_mapa": true,
    "categorias": []
  }
}
```

---

## Foros

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `foros_listado` | Listado de foros | `limite` |
| `foros_temas` | Temas de un foro | `foro_id`, `limite` |
| `foros_tema` | Detalle tema | `tema_id` |
| `foros_crear_tema` | Crear nuevo tema | `foro_id` |
| `foros_buscar` | Búsqueda | - |
| `foros_recientes` | Temas recientes | `limite` |

**Ejemplo en VBP:**
```json
{
  "type": "foros-listado",
  "data": {
    "titulo": "Foros de la Comunidad",
    "limite": 10,
    "mostrar_stats": true
  }
}
```

---

## Transparencia

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `transparencia_portal` | Portal principal | - |
| `transparencia_presupuesto` | Presupuesto actual | `año` |
| `transparencia_gastos` | Últimos gastos | `limite` |
| `transparencia_contratos` | Contratos | `limite`, `tipo` |
| `transparencia_actas` | Actas de reuniones | `limite` |
| `transparencia_indicadores` | KPIs | - |

**Ejemplo en VBP:**
```json
{
  "type": "transparencia-portal",
  "data": {
    "titulo": "Portal de Transparencia",
    "secciones": ["presupuesto", "gastos", "contratos"],
    "año": "2024"
  }
}
```

---

## Participación

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `participacion_procesos` | Procesos activos | `limite` |
| `participacion_votacion` | Votación | `proceso_id` |
| `participacion_propuestas` | Listado propuestas | `proceso_id`, `limite` |
| `participacion_crear_propuesta` | Crear propuesta | `proceso_id` |
| `participacion_resultados` | Resultados | `proceso_id` |

**Ejemplo en VBP:**
```json
{
  "type": "participacion-procesos",
  "data": {
    "titulo": "Participa en las Decisiones",
    "limite": 4,
    "mostrar_votos": true,
    "mostrar_fecha_limite": true
  }
}
```

---

## Comunidades

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `comunidades_listado` | Listado comunidades | `limite`, `columnas` |
| `comunidades_feed` | Feed de actividad | `comunidad_id`, `limite` |
| `comunidades_crear` | Crear comunidad | - |
| `comunidades_miembros` | Miembros | `comunidad_id`, `limite` |
| `comunidades_buscar` | Buscador | - |

**Ejemplo en VBP:**
```json
{
  "type": "comunidades-listado",
  "data": {
    "titulo": "Nuestras Comunidades",
    "limite": 6,
    "columnas": 3,
    "mostrar_miembros": true
  }
}
```

---

## Encuestas

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `encuestas_activas` | Encuestas activas | `limite` |
| `encuestas_votar` | Formulario votación | `encuesta_id` |
| `encuestas_resultados` | Resultados | `encuesta_id` |
| `encuestas_crear` | Crear encuesta | - |

**Ejemplo en VBP:**
```json
{
  "type": "encuestas-activas",
  "data": {
    "titulo": "Encuestas Abiertas",
    "limite": 3,
    "mostrar_participantes": true
  }
}
```

---

## Banco de Tiempo

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `bt_ofertas` | Ofertas de servicios | `limite`, `categoria` |
| `bt_demandas` | Demandas | `limite`, `categoria` |
| `bt_mi_cuenta` | Mi cuenta de horas | - |
| `bt_intercambio` | Registrar intercambio | - |
| `bt_ranking_comunidad` | Ranking | `limite` |
| `bt_donar_horas` | Donar al fondo | - |

**Ejemplo en VBP:**
```json
{
  "type": "bt-ofertas",
  "data": {
    "titulo": "Servicios Disponibles",
    "limite": 8,
    "columnas": 4,
    "categoria": ""
  }
}
```

---

## Carpooling

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `carpooling_viajes` | Viajes disponibles | `limite`, `origen`, `destino` |
| `carpooling_crear` | Publicar viaje | - |
| `carpooling_mis_viajes` | Mis viajes | - |
| `carpooling_buscar` | Buscador | - |
| `carpooling_mapa` | Mapa de viajes | - |

**Ejemplo en VBP:**
```json
{
  "type": "carpooling-viajes",
  "data": {
    "titulo": "Viajes Compartidos",
    "limite": 6,
    "mostrar_mapa": false,
    "mostrar_precio": true
  }
}
```

---

## Atributos Comunes

Estos atributos funcionan en la mayoría de shortcodes:

| Atributo | Tipo | Descripción |
|----------|------|-------------|
| `titulo` | string | Título de la sección |
| `limite` | number | Número máximo de items |
| `columnas` | number | Columnas en grid (2,3,4) |
| `esquema_color` | string | default, primary, success, warning, danger, purple, pink, dark |
| `radio_bordes` | string | none, sm, md, lg, xl, full |
| `sombra` | string | none, sm, md, lg, xl |
| `animacion_entrada` | string | none, fade, slide-up, slide-down, zoom, bounce |

---

## Shortcodes Genéricos

```php
// Dashboard de módulo
[flavor_client_dashboard module="grupos_consumo"]

// Widget individual
[flavor_widget id="socios_stats"]

// Múltiples widgets
[flavor_widgets ids="eventos_proximos,socios_stats,gc_stats" columnas="3"]

// Listado genérico
[flavor_module_listing module="eventos" action="listar" limite="6"]

// Navegación de módulo
[flavor_module_nav module="grupos_consumo"]
```
