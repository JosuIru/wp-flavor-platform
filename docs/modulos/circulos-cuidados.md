# Módulo: Círculos de Cuidados

> Redes de apoyo mutuo para situaciones vitales

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `circulos_cuidados` |
| **Versión** | 4.2.0+ |
| **Categoría** | Cuidados / Comunidad |

### Principios Gailu

- `cuidados` - Apoyo mutuo comunitario
- `aprendizaje` - Compartir experiencias
- `cohesion` - Fortalecer tejido social
- `resiliencia` - Comunidades más fuertes

---

## Descripción

Organiza redes de apoyo mutuo para situaciones vitales: acompañamiento a personas mayores, cuidado compartido de infancia, apoyo en enfermedad/duelo, y bancos de horas de cuidado.

### Características Principales

- **Círculos Temáticos**: Mayores, infancia, enfermedad, duelo, maternidad, diversidad
- **Necesidades**: Publicar necesidades de cuidado
- **Ofertas de Ayuda**: Miembros ofrecen su tiempo
- **Banco de Horas**: Registro de horas de cuidado
- **Urgencias**: Sistema de alertas para necesidades urgentes
- **Anonimato**: Opción de solicitar ayuda anónimamente

---

## Tipos de Círculo

| Tipo | Nombre | Descripción | Color |
|------|--------|-------------|-------|
| `mayores` | Acompañamiento Mayores | Visitas, compañía, ayuda con gestiones | #9b59b6 |
| `infancia` | Cuidado Infancia | Cuidado compartido, recogidas, actividades | #e91e63 |
| `enfermedad` | Apoyo Enfermedad | Acompañamiento médico, comidas, ayuda | #00bcd4 |
| `duelo` | Acompañamiento Duelo | Presencia, escucha, apoyo emocional | #607d8b |
| `maternidad` | Red de Maternidad | Apoyo embarazo, postparto, crianza | #ff9800 |
| `diversidad` | Diversidad Funcional | Apoyo a personas con necesidades especiales | #4caf50 |

---

## Custom Post Types

### `cc_circulo` - Círculos de Cuidado

Post type para los círculos/grupos de cuidado.

**Meta Fields:**
- `_cc_tipo` - Tipo de círculo
- `_cc_zona` - Zona geográfica
- `_cc_miembros` - Array de miembros
- `_cc_max_miembros` - Máximo de miembros

### `cc_necesidad` - Necesidades de Cuidado

Post type para solicitudes de ayuda.

**Meta Fields:**
- `_cc_urgencia` - Nivel de urgencia
- `_cc_estado` - Estado (abierta, en_proceso, resuelta)
- `_cc_horas_necesarias` - Horas estimadas

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/`

| Endpoint | Método | Descripción | Auth |
|----------|--------|-------------|------|
| `/circulos-cuidados` | GET | Listar círculos | Público |
| `/circulos-cuidados/{id}` | GET | Obtener círculo | Público |
| `/circulos-cuidados/necesidades` | GET | Necesidades abiertas | Público |
| `/circulos-cuidados/mis-cuidados` | GET | Mis estadísticas | Usuario |

### GET `/circulos-cuidados`

**Parámetros:**
- `per_page` - Resultados por página (default: 20)
- `tipo` - Filtrar por tipo de círculo

**Respuesta:**
```json
{
  "circulos": [
    {
      "id": 123,
      "titulo": "Círculo de Mayores Casco Viejo",
      "tipo": "mayores",
      "zona": "Casco Viejo",
      "miembros": 15
    }
  ],
  "total": 42
}
```

### GET `/circulos-cuidados/necesidades`

**Parámetros:**
- `per_page` - Resultados por página

**Respuesta:**
```json
{
  "necesidades": [
    {
      "id": 456,
      "titulo": "Acompañamiento médico",
      "urgencia": "alta",
      "estado": "abierta",
      "horas_necesarias": 3
    }
  ],
  "total": 8
}
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `tipos_habilitados` | array | Todos | Tipos de círculo activos |
| `horas_minimas_compromiso` | int | 2 | Horas mínimas al mes |
| `notificar_necesidades_urgentes` | bool | true | Alertas urgentes |
| `permitir_anonimato` | bool | true | Solicitudes anónimas |
| `mostrar_en_dashboard` | bool | true | Widget en dashboard |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `cc_unirse_circulo` | Unirse a un círculo | Usuario |
| `cc_ofrecer_ayuda` | Ofrecer ayuda a necesidad | Usuario |
| `cc_registrar_horas` | Registrar horas de cuidado | Usuario |
| `cc_crear_necesidad` | Crear nueva necesidad | Usuario |

---

## Shortcodes

Expuestos a través del Frontend Controller y dashboard tabs.

---

## Hooks

### Acciones

```php
// Cuando hay necesidad urgente
do_action('cc_necesidad_urgente', $necesidad_id, $datos);

// Al ofrecer ayuda
do_action('cc_ayuda_ofrecida', $necesidad_id, $usuario_id);

// Al registrar horas
do_action('cc_horas_registradas', $usuario_id, $horas);
```

### Filtros

```php
// Modificar tipos de círculo disponibles
apply_filters('cc_tipos_circulo', $tipos);
```

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `cc_recordatorio_cuidados` | Diario | Enviar recordatorios |

---

## Dashboard Widget

El módulo registra un widget en el dashboard de usuario:

```php
add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
```

---

## Integraciones

### Acepta integraciones de:

- `multimedia` - Fotos de actividades
- `recetas` - Recetas para cuidados
- `biblioteca` - Guías y recursos

---

## Páginas de Administración

| Categoría | ID | Descripción |
|-----------|----|-------------|
| comunidad | `circulos_cuidados` | Panel de administración |

---

## Estadísticas de Usuario

El endpoint `/mis-cuidados` devuelve:

```json
{
  "horas_dadas": 24,
  "horas_recibidas": 8,
  "circulos_activos": 2,
  "necesidades_atendidas": 5,
  "balance_horas": 16
}
```

---

## Flujo de Uso

```
1. CREAR CÍRCULO
   Admin o usuarios crean círculos temáticos por zona
   ↓
2. UNIRSE
   Miembros se unen a círculos de su interés/zona
   ↓
3. PUBLICAR NECESIDAD
   Alguien publica una necesidad de cuidado
   ↓
4. OFRECER AYUDA
   Miembros del círculo ofrecen su tiempo
   ↓
5. COORDINAR
   Se coordina la ayuda (fecha, hora, detalles)
   ↓
6. REGISTRAR HORAS
   Se registran las horas de cuidado realizadas
   ↓
7. AGRADECER
   Sistema de agradecimientos mutuos
```

---

## Niveles de Urgencia

| Urgencia | Descripción | Notificación |
|----------|-------------|--------------|
| `baja` | Puede esperar días | Normal |
| `media` | En los próximos días | Normal |
| `alta` | En 24-48 horas | Push a miembros |
| `urgente` | Inmediato | Push + Email |

---

## Notas de Implementación

- Los círculos usan Custom Post Types de WordPress
- Los miembros se almacenan como meta array
- El banco de horas permite economía del cuidado
- Las necesidades urgentes disparan notificaciones automáticas
- Opción de anonimato para situaciones sensibles (duelo, enfermedad)
- Integración con dashboard unificado de usuario
- Las estadísticas muestran balance de horas dadas/recibidas
