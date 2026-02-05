# Guía del Sistema de Reservas

## 📅 ¿Qué es el Sistema de Reservas?

El sistema de reservas permite a tus clientes reservar mesas en tu restaurante desde la app móvil, y a ti gestionar todas las reservas desde el panel de administración de WordPress.

---

## 🚀 Características

✅ **Reservas Online**: Los clientes reservan desde la app
✅ **Asignación Automática**: El sistema asigna la mejor mesa disponible
✅ **Confirmación por Email**: Emails automáticos al crear y confirmar
✅ **Gestión Completa**: Panel de administración en tiempo real
✅ **Estados Personalizables**: Pendiente, Confirmada, Cancelada, etc.
✅ **Verificación de Disponibilidad**: Evita dobles reservas
✅ **Códigos Únicos**: Cada reserva tiene un código identificador
✅ **Historial Completo**: Seguimiento de todas las reservas

---

## 📱 Cómo Funciona para el Cliente

### 1. Hacer una Reserva desde la App

**Flujo:**
```
1. Cliente abre la app
   ↓
2. Va a "Reservas" o "Reservar Mesa"
   ↓
3. Selecciona:
   - Fecha
   - Hora
   - Número de personas
   ↓
4. El sistema verifica disponibilidad
   ↓
5. Cliente completa:
   - Nombre
   - Teléfono
   - Email (opcional)
   - Solicitudes especiales
   ↓
6. Confirma la reserva
   ↓
7. Recibe código de reserva por email
```

**Ejemplo de Código:**
```
RES-260204-0001
```

### 2. Seguimiento de la Reserva

**Desde la app:**
- Ver estado actual (Pendiente/Confirmada)
- Ver detalles (fecha, hora, mesa asignada)
- Cancelar si es necesario
- Recibir recordatorios

---

## 💼 Cómo Funciona para el Restaurante

### Panel de Administración

**Ubicación:**
```
WordPress Admin > Flavor Platform > Reservas
```

### Vista Principal

**Tarjetas de Estadísticas:**
- 📋 Pendientes: Reservas esperando confirmación
- ✅ Confirmadas: Reservas confirmadas
- 📅 Hoy: Reservas para hoy
- 👥 Comensales: Total de personas esperadas

**Lista de Reservas:**
Cada tarjeta muestra:
- Código de reserva
- Estado
- Cliente
- Fecha y hora
- Número de personas
- Mesa asignada

**Filtros Disponibles:**
- Por estado
- Por fecha
- Por mesa
- Solo próximas

### Gestionar una Reserva

**1. Ver Detalles**
- Click en la tarjeta de reserva
- Se abre modal con información completa

**2. Confirmar Reserva**
```
Click en "Confirmar Reserva"
   ↓
Sistema envía email de confirmación
   ↓
Mesa marcada como "Reservada"
   ↓
Cliente recibe notificación
```

**3. Cancelar Reserva**
```
Click en "Cancelar Reserva"
   ↓
Introducir motivo (opcional)
   ↓
Sistema envía email de cancelación
   ↓
Mesa liberada para otras reservas
```

---

## 🔌 API REST de Reservas

### Crear Reserva

```http
POST /wp-json/restaurant/v1/reservations
```

**Body:**
```json
{
  "customer_name": "María García",
  "customer_phone": "612345678",
  "customer_email": "maria@example.com",
  "guests_count": 4,
  "reservation_date": "2024-02-15",
  "reservation_time": "20:30:00",
  "duration": 120,
  "special_requests": "Celebración de cumpleaños"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Reserva creada exitosamente",
  "reservation": {
    "id": 1,
    "reservation_code": "RES-260204-0001",
    "table_id": 5,
    "table": {
      "table_number": "05",
      "table_name": "Mesa 05"
    },
    "customer": {
      "name": "María García",
      "phone": "612345678",
      "email": "maria@example.com"
    },
    "guests_count": 4,
    "reservation_date": "2024-02-15",
    "reservation_time": "20:30:00",
    "reservation_datetime": "2024-02-15 20:30:00",
    "duration": 120,
    "status": "pending",
    "status_label": "Pendiente",
    "special_requests": "Celebración de cumpleaños"
  }
}
```

### Obtener Reserva por Código

```http
GET /wp-json/restaurant/v1/reservations/code/RES-260204-0001
```

**Respuesta:**
```json
{
  "success": true,
  "reservation": {
    "id": 1,
    "reservation_code": "RES-260204-0001",
    "status": "confirmed",
    ...
  }
}
```

### Verificar Disponibilidad

```http
GET /wp-json/restaurant/v1/reservations/availability
  ?date=2024-02-15
  &time=20:30:00
  &guests=4
  &duration=120
```

**Respuesta:**
```json
{
  "success": true,
  "available": true,
  "tables_count": 3,
  "tables": [
    {
      "id": 5,
      "table_number": "05",
      "capacity": 4
    },
    {
      "id": 8,
      "table_number": "08",
      "capacity": 6
    }
  ]
}
```

### Listar Reservas

```http
GET /wp-json/restaurant/v1/reservations
  ?status=confirmed
  &date=2024-02-15
  &upcoming=true
```

**Parámetros:**
- `status`: pending, confirmed, cancelled, completed, no_show
- `date`: Fecha específica (YYYY-MM-DD)
- `date_from`: Desde fecha
- `date_to`: Hasta fecha
- `table_id`: Filtrar por mesa
- `upcoming`: true/false (solo futuras)
- `page`: Número de página
- `per_page`: Resultados por página

### Confirmar Reserva

```http
POST /wp-json/restaurant/v1/reservations/{id}/confirm
```

**Body:**
```json
{
  "notes": "Mesa preferente asignada"
}
```

**Permisos:** Requiere autenticación de administrador

### Cancelar Reserva

```http
POST /wp-json/restaurant/v1/reservations/{id}/cancel
```

**Body:**
```json
{
  "reason": "Cliente canceló por cambio de planes"
}
```

**Permisos:** Cliente puede cancelar su propia reserva, admin puede cancelar cualquiera

### Estadísticas de Reservas

```http
GET /wp-json/restaurant/v1/reservations/statistics
  ?date_from=2024-02-01
  &date_to=2024-02-29
```

**Respuesta:**
```json
{
  "success": true,
  "statistics": {
    "total_reservations": 145,
    "confirmed_reservations": 120,
    "pending_reservations": 15,
    "cancelled_reservations": 8,
    "no_show_reservations": 2,
    "total_guests": 420,
    "average_party_size": 2.9
  }
}
```

---

## ⚙️ Configuración

### Opciones de Reservas

**Ubicación:** `Flavor Platform > Restaurante`

**Parámetros:**
```php
enable_reservations: true/false
  → Habilitar sistema de reservas

reservation_duration_default: 120
  → Duración por defecto en minutos

reservation_min_advance_hours: 2
  → Mínimo de antelación (horas)

reservation_max_advance_days: 30
  → Máximo de antelación (días)
```

### Emails Automáticos

**Tipos de Email:**

**1. Reserva Creada**
```
Asunto: Reserva Recibida - RES-260204-0001

Hola María,

Hemos recibido tu reserva. Te confirmaremos en breve.

Detalles:
- Código: RES-260204-0001
- Fecha: 15/02/2024 a las 20:30
- Personas: 4
- Mesa: Mesa 05
```

**2. Reserva Confirmada**
```
Asunto: ✅ Reserva Confirmada - RES-260204-0001

Hola María,

¡Tu reserva está confirmada!

Detalles:
- Código: RES-260204-0001
- Fecha: 15/02/2024 a las 20:30
- Personas: 4
- Mesa: Mesa 05

Te esperamos.
```

**3. Reserva Cancelada**
```
Asunto: Reserva Cancelada - RES-260204-0001

Hola María,

Tu reserva RES-260204-0001 ha sido cancelada.

Si deseas hacer una nueva reserva, estaremos encantados.
```

---

## 🔄 Estados de Reserva

### pending (Pendiente)
- Estado inicial al crear la reserva
- Esperando confirmación del restaurante
- La mesa NO está bloqueada

### confirmed (Confirmada)
- El restaurante ha confirmado la reserva
- Email enviado al cliente
- La mesa está BLOQUEADA para ese horario

### cancelled (Cancelada)
- Reserva cancelada por el cliente o restaurante
- Mesa liberada
- Email de cancelación enviado

### completed (Completada)
- La reserva se cumplió exitosamente
- Los clientes llegaron y fueron atendidos
- Estado automático tras la fecha/hora de reserva

### no_show (No se presentó)
- Reserva confirmada pero el cliente no llegó
- Estado automático 1 hora después de la hora reservada
- Útil para estadísticas

---

## 🕐 Gestión Automática

### Limpieza Diaria

**Cron Job (automático):**
```php
// Se ejecuta una vez al día
flavor_restaurant_cleanup_reservations

Acciones:
1. Marca como "completed" las reservas confirmadas que ya pasaron
2. Marca como "no_show" las pendientes que pasaron hace más de 1 hora
3. Limpia datos antiguos (opcional)
```

### Bloqueo de Mesas

**Reglas:**
1. Cuando reserva está "confirmed" → Mesa bloqueada
2. Se verifica disponibilidad considerando:
   - Hora de inicio
   - Duración
   - Margen de limpieza (15 min)

**Ejemplo:**
```
Reserva 1: 20:00 - 22:00 (Mesa 5)
Reserva 2: 22:30 - 00:30 (Mesa 5) ← Disponible (margen de 30 min)
Reserva 3: 21:00 - 23:00 (Mesa 5) ← NO disponible (conflicto)
```

---

## 💡 Casos de Uso

### Caso 1: Reserva Simple

**Cliente:**
1. Abre app
2. Selecciona fecha y hora
3. Introduce datos
4. Confirma

**Restaurante:**
1. Recibe notificación
2. Revisa disponibilidad
3. Confirma reserva
4. Cliente recibe confirmación

### Caso 2: Grupo Grande

**Cliente solicita:**
- Fecha: Viernes 20:00
- Personas: 12

**Sistema:**
1. No hay mesa para 12
2. Busca combinación de mesas
3. Sugiere Mesa 5 (6 pers) + Mesa 8 (6 pers)
4. O sugiere hora alternativa

**Restaurante:**
1. Ve la solicitud
2. Decide juntarmesas manualmente
3. Asigna mesas y confirma

### Caso 3: Cancelación

**Cliente cancela:**
1. Entra a "Mis Reservas"
2. Click en reserva activa
3. "Cancelar reserva"
4. Confirma

**Sistema:**
1. Cambia estado a "cancelled"
2. Libera mesa
3. Envía email de cancelación
4. Notifica al restaurante

---

## ❓ Preguntas Frecuentes

### ¿Cuánto tiempo antes se puede reservar?

Por defecto:
- Mínimo: 2 horas de antelación
- Máximo: 30 días

Configurable en ajustes.

### ¿Se pueden reservar mesas específicas?

**Desde la app de cliente:** No, el sistema asigna automáticamente
**Desde el admin:** Sí, puedes cambiar la mesa asignada

### ¿Qué pasa si un cliente no llega?

El sistema marca automáticamente como "no_show" después de 1 hora de la hora reservada si la reserva estaba confirmada.

### ¿Se pueden hacer reservas recurrentes?

No está soportado actualmente. Cada reserva debe crearse individualmente.

### ¿Cómo evito dobles reservas?

El sistema verifica automáticamente:
- Disponibilidad de mesas
- Conflictos de horarios
- Capacidad

### ¿Puedo deshabilitar las reservas?

Sí, en `Flavor Platform > Restaurante`:
- Desactiva "Habilitar reservas"

### ¿Los emails son personalizables?

Actualmente usan templates predefinidos. En futuras versiones podrás personalizarlos completamente.

---

## 🔧 Troubleshooting

### No llegan los emails

**Solución:**
1. Verifica configuración SMTP de WordPress
2. Usa un plugin de SMTP (WP Mail SMTP)
3. Revisa spam del cliente

### Las reservas no bloquean mesas

**Solución:**
1. Verifica que el estado sea "confirmed"
2. Solo "confirmed" bloquea mesas
3. Las "pending" no bloquean

### No se puede reservar con antelación

**Solución:**
1. Verifica `reservation_min_advance_hours` en ajustes
2. Asegúrate que la hora solicitada es mayor a este mínimo

---

## 🎯 Buenas Prácticas

✅ **Confirma rápido**: Responde a reservas pendientes en menos de 2 horas
✅ **Envía recordatorios**: Un día antes, recuerda a los clientes
✅ **Gestiona no-shows**: Lleva registro y considera política de depósitos
✅ **Actualiza disponibilidad**: Marca mesas fuera de servicio si es necesario
✅ **Solicitudes especiales**: Lee y atiende peticiones especiales
✅ **Horarios pico**: Ajusta duración en horarios muy demandados

---

¡Sistema de reservas completo y listo! 🎉

Para más información técnica, consulta el README.md
