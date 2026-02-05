# App Móvil Admin - Módulo de Reservas

Guía completa para integrar la gestión de reservas en la app móvil del administrador.

---

## 📱 Descripción General

Este módulo permite a los administradores del restaurante gestionar todas las reservas desde su dispositivo móvil en tiempo real, incluyendo:

- ✅ Ver todas las reservas (filtradas y ordenadas)
- ✅ Confirmar reservas pendientes
- ✅ Cancelar reservas
- ✅ Ver detalles completos
- ✅ Recibir notificaciones de nuevas reservas
- ✅ Ver estadísticas del día/semana/mes
- ✅ Buscar por código de reserva
- ✅ Verificar disponibilidad

---

## 🔑 Autenticación

Todos los endpoints de administración requieren autenticación JWT o sesión de WordPress.

### Obtener Token JWT

```http
POST /wp-json/jwt-auth/v1/token

Body:
{
  "username": "admin",
  "password": "tu_password"
}

Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user_email": "admin@restaurant.com",
  "user_nicename": "admin",
  "user_display_name": "Administrador"
}
```

### Usar Token en Peticiones

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## 📋 Pantallas de la App

### 1. Pantalla Principal: Lista de Reservas

**Diseño sugerido:**

```
┌─────────────────────────────────┐
│  ☰  Reservas        [🔔] [⚙️]  │
├─────────────────────────────────┤
│  Filtros: [Hoy ▾] [Todas ▾]    │
├─────────────────────────────────┤
│  📊 Estadísticas del Día        │
│  ┌─────────┬─────────┬─────────┐│
│  │ ⏰ 3    │ ✅ 12   │ 👥 45   ││
│  │Pendien. │Confirm. │Personas ││
│  └─────────┴─────────┴─────────┘│
├─────────────────────────────────┤
│  🔴 Pendientes de Confirmar (3) │
│  ┌───────────────────────────┐  │
│  │ 🕐 20:30  👥 4  Mesa 05   │  │
│  │ Juan Pérez                │  │
│  │ 612-345-678               │  │
│  │ [✓ Confirmar] [✗ Rechazar]│  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ 🕐 21:00  👥 2  Mesa 03   │  │
│  │ María López               │  │
│  │ 623-456-789               │  │
│  │ [✓ Confirmar] [✗ Rechazar]│  │
│  └───────────────────────────┘  │
├─────────────────────────────────┤
│  ✅ Confirmadas Hoy (12)        │
│  ┌───────────────────────────┐  │
│  │ 🕐 19:00  👥 6  Mesa 08   │  │
│  │ Carlos García (confirmada)│  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ 🕐 19:30  👥 3  Mesa 02   │  │
│  │ Ana Martín (confirmada)   │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

### 2. Pantalla de Detalles

```
┌─────────────────────────────────┐
│  ←  RES-260204-0001             │
├─────────────────────────────────┤
│  Estado: 🟡 PENDIENTE           │
│                                 │
│  📅 Información de Reserva      │
│  Fecha: Viernes, 15 Feb 2024    │
│  Hora: 20:30                    │
│  Duración: 2 horas              │
│  Personas: 4                    │
│  Mesa: Mesa 05 (Terraza)        │
│                                 │
│  👤 Cliente                     │
│  Juan Pérez                     │
│  📞 612-345-678 [Llamar]        │
│  ✉️ juan@email.com [Email]      │
│                                 │
│  📝 Solicitudes Especiales      │
│  "Mesa cerca de la ventana      │
│   para celebración de           │
│   cumpleaños"                   │
│                                 │
│  ⏰ Creada                       │
│  04 Feb 2024 - 14:25            │
│                                 │
│  ┌───────────────────────────┐  │
│  │  ✅ CONFIRMAR RESERVA     │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │  ✏️ CAMBIAR MESA          │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │  ✗ CANCELAR               │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

### 3. Pantalla de Estadísticas

```
┌─────────────────────────────────┐
│  ←  Estadísticas                │
├─────────────────────────────────┤
│  [Hoy] [Semana] [Mes]           │
├─────────────────────────────────┤
│  📊 Resumen del Día             │
│  ┌─────────────────────────┐    │
│  │ Total Reservas    15    │    │
│  │ Confirmadas       12    │    │
│  │ Pendientes         3    │    │
│  │ Canceladas         0    │    │
│  │ Total Comensales  45    │    │
│  └─────────────────────────┘    │
│                                 │
│  📈 Gráfico de Ocupación        │
│  [Gráfico de barras por hora]   │
│                                 │
│  ⭐ Horarios Más Solicitados    │
│  1. 20:30 - 21:00 (8 reservas)  │
│  2. 19:00 - 19:30 (4 reservas)  │
│  3. 21:30 - 22:00 (3 reservas)  │
│                                 │
│  🎯 Tasa de Confirmación        │
│  80% (12 de 15)                 │
│                                 │
│  ⚠️ No Shows                    │
│  0 esta semana                  │
└─────────────────────────────────┘
```

---

## 🔌 Endpoints de la API

### 1. Obtener Lista de Reservas

**Endpoint:**
```http
GET /wp-json/restaurant/v1/reservations
```

**Parámetros de Consulta:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `status` | string | Filtrar por estado | `pending`, `confirmed`, `cancelled` |
| `date` | string | Fecha específica | `2024-02-15` |
| `date_from` | string | Desde fecha | `2024-02-01` |
| `date_to` | string | Hasta fecha | `2024-02-28` |
| `upcoming` | boolean | Solo futuras | `true` |
| `page` | int | Número de página | `1` |
| `per_page` | int | Resultados por página | `20` |

**Ejemplo de Llamada:**

```dart
// Flutter/Dart
Future<List<Reservation>> getReservations({
  String? status,
  String? date,
  bool upcoming = false,
  int page = 1,
  int perPage = 20,
}) async {
  final queryParams = {
    if (status != null) 'status': status,
    if (date != null) 'date': date,
    if (upcoming) 'upcoming': 'true',
    'page': page.toString(),
    'per_page': perPage.toString(),
  };

  final uri = Uri.parse('$baseUrl/restaurant/v1/reservations')
    .replace(queryParameters: queryParams);

  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return (data['reservations'] as List)
      .map((json) => Reservation.fromJson(json))
      .toList();
  } else {
    throw Exception('Error al cargar reservas');
  }
}
```

**Respuesta:**

```json
{
  "success": true,
  "reservations": [
    {
      "id": 1,
      "reservation_code": "RES-260204-0001",
      "table_id": 5,
      "table": {
        "id": 5,
        "table_number": "05",
        "table_name": "Mesa 05",
        "capacity": 4,
        "location": "Terraza"
      },
      "customer": {
        "name": "Juan Pérez",
        "phone": "612345678",
        "email": "juan@email.com"
      },
      "user_id": null,
      "guests_count": 4,
      "reservation_date": "2024-02-15",
      "reservation_time": "20:30:00",
      "reservation_datetime": "2024-02-15 20:30:00",
      "duration": 120,
      "status": "pending",
      "status_label": "Pendiente",
      "special_requests": "Mesa cerca de la ventana",
      "notes": "",
      "created_at": "2024-02-04 14:25:00",
      "updated_at": "2024-02-04 14:25:00",
      "confirmed_at": null,
      "cancelled_at": null
    },
    // ... más reservas
  ],
  "total": 15
}
```

### 2. Obtener Detalles de una Reserva

**Endpoint:**
```http
GET /wp-json/restaurant/v1/reservations/{id}
```

**Ejemplo:**

```dart
Future<Reservation> getReservationDetails(int reservationId) async {
  final response = await http.get(
    Uri.parse('$baseUrl/restaurant/v1/reservations/$reservationId'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return Reservation.fromJson(data['reservation']);
  } else {
    throw Exception('Reserva no encontrada');
  }
}
```

### 3. Confirmar Reserva

**Endpoint:**
```http
POST /wp-json/restaurant/v1/reservations/{id}/confirm
```

**Body (opcional):**
```json
{
  "notes": "Mesa preferente asignada"
}
```

**Ejemplo:**

```dart
Future<Reservation> confirmReservation(
  int reservationId, {
  String? notes,
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/restaurant/v1/reservations/$reservationId/confirm'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      if (notes != null) 'notes': notes,
    }),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);

    // Mostrar notificación de éxito
    showSuccessNotification(data['message']);

    // Email enviado automáticamente al cliente

    return Reservation.fromJson(data['reservation']);
  } else {
    throw Exception('Error al confirmar reserva');
  }
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Reserva confirmada exitosamente",
  "reservation": {
    "id": 1,
    "status": "confirmed",
    "status_label": "Confirmada",
    "confirmed_at": "2024-02-04 15:30:00",
    // ... resto de datos
  }
}
```

**Acciones Automáticas:**
- ✅ Estado cambia a `confirmed`
- ✅ Email de confirmación enviado al cliente
- ✅ Mesa bloqueada para ese horario
- ✅ Campo `confirmed_at` actualizado

### 4. Cancelar Reserva

**Endpoint:**
```http
POST /wp-json/restaurant/v1/reservations/{id}/cancel
```

**Body:**
```json
{
  "reason": "Cliente canceló por cambio de planes"
}
```

**Ejemplo:**

```dart
Future<Reservation> cancelReservation(
  int reservationId,
  String reason,
) async {
  final response = await http.post(
    Uri.parse('$baseUrl/restaurant/v1/reservations/$reservationId/cancel'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'reason': reason,
    }),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    showSuccessNotification(data['message']);
    return Reservation.fromJson(data['reservation']);
  } else {
    throw Exception('Error al cancelar reserva');
  }
}
```

**Acciones Automáticas:**
- ✅ Estado cambia a `cancelled`
- ✅ Email de cancelación enviado al cliente
- ✅ Mesa liberada
- ✅ Campo `cancelled_at` actualizado

### 5. Actualizar Reserva

**Endpoint:**
```http
PUT /wp-json/restaurant/v1/reservations/{id}
```

**Body:**
```json
{
  "table_id": 8,
  "guests_count": 6,
  "reservation_time": "21:00:00",
  "notes": "Mesa cambiada a petición del cliente"
}
```

**Ejemplo:**

```dart
Future<Reservation> updateReservation(
  int reservationId,
  Map<String, dynamic> updates,
) async {
  final response = await http.put(
    Uri.parse('$baseUrl/restaurant/v1/reservations/$reservationId'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode(updates),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return Reservation.fromJson(data['reservation']);
  } else {
    throw Exception('Error al actualizar reserva');
  }
}
```

**Campos Actualizables:**
- `table_id`: Cambiar mesa
- `guests_count`: Cambiar número de personas
- `reservation_date`: Cambiar fecha
- `reservation_time`: Cambiar hora
- `duration`: Cambiar duración
- `special_requests`: Actualizar solicitudes
- `notes`: Notas internas

### 6. Buscar por Código

**Endpoint:**
```http
GET /wp-json/restaurant/v1/reservations/code/{code}
```

**Ejemplo:**

```dart
Future<Reservation> searchByCode(String code) async {
  final response = await http.get(
    Uri.parse('$baseUrl/restaurant/v1/reservations/code/$code'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return Reservation.fromJson(data['reservation']);
  } else if (response.statusCode == 404) {
    throw Exception('Reserva no encontrada');
  } else {
    throw Exception('Error en la búsqueda');
  }
}
```

**Uso:**
```dart
// Búsqueda rápida desde barra de búsqueda
final reservation = await searchByCode('RES-260204-0001');
```

### 7. Obtener Estadísticas

**Endpoint:**
```http
GET /wp-json/restaurant/v1/reservations/statistics
```

**Parámetros:**
- `date_from`: Fecha inicio (opcional)
- `date_to`: Fecha fin (opcional)

**Ejemplo:**

```dart
Future<ReservationStatistics> getStatistics({
  String? dateFrom,
  String? dateTo,
}) async {
  final queryParams = {
    if (dateFrom != null) 'date_from': dateFrom,
    if (dateTo != null) 'date_to': dateTo,
  };

  final uri = Uri.parse('$baseUrl/restaurant/v1/reservations/statistics')
    .replace(queryParameters: queryParams);

  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return ReservationStatistics.fromJson(data['statistics']);
  } else {
    throw Exception('Error al cargar estadísticas');
  }
}
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

### 8. Verificar Disponibilidad

**Endpoint:**
```http
GET /wp-json/restaurant/v1/reservations/availability
```

**Parámetros:**
- `date`: Fecha (YYYY-MM-DD)
- `time`: Hora (HH:MM:SS)
- `guests`: Número de personas
- `duration`: Duración en minutos (opcional, default 120)

**Ejemplo:**

```dart
Future<AvailabilityResult> checkAvailability({
  required String date,
  required String time,
  required int guests,
  int duration = 120,
}) async {
  final queryParams = {
    'date': date,
    'time': time,
    'guests': guests.toString(),
    'duration': duration.toString(),
  };

  final uri = Uri.parse('$baseUrl/restaurant/v1/reservations/availability')
    .replace(queryParameters: queryParams);

  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return AvailabilityResult.fromJson(data);
  } else {
    throw Exception('Error al verificar disponibilidad');
  }
}
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
      "table_name": "Mesa 05",
      "capacity": 4,
      "location": "Terraza"
    },
    {
      "id": 8,
      "table_number": "08",
      "table_name": "Mesa 08",
      "capacity": 6,
      "location": "Salón"
    }
  ]
}
```

---

## 📦 Modelos de Datos

### Modelo: Reservation

```dart
class Reservation {
  final int id;
  final String reservationCode;
  final int? tableId;
  final TableInfo? table;
  final Customer customer;
  final int? userId;
  final int guestsCount;
  final String reservationDate;
  final String reservationTime;
  final DateTime reservationDateTime;
  final int duration;
  final ReservationStatus status;
  final String statusLabel;
  final String? specialRequests;
  final String? notes;
  final bool confirmationSent;
  final bool reminderSent;
  final DateTime createdAt;
  final DateTime updatedAt;
  final DateTime? confirmedAt;
  final DateTime? cancelledAt;

  Reservation({
    required this.id,
    required this.reservationCode,
    this.tableId,
    this.table,
    required this.customer,
    this.userId,
    required this.guestsCount,
    required this.reservationDate,
    required this.reservationTime,
    required this.reservationDateTime,
    required this.duration,
    required this.status,
    required this.statusLabel,
    this.specialRequests,
    this.notes,
    required this.confirmationSent,
    required this.reminderSent,
    required this.createdAt,
    required this.updatedAt,
    this.confirmedAt,
    this.cancelledAt,
  });

  factory Reservation.fromJson(Map<String, dynamic> json) {
    return Reservation(
      id: json['id'],
      reservationCode: json['reservation_code'],
      tableId: json['table_id'],
      table: json['table'] != null
        ? TableInfo.fromJson(json['table'])
        : null,
      customer: Customer.fromJson(json['customer']),
      userId: json['user_id'],
      guestsCount: json['guests_count'],
      reservationDate: json['reservation_date'],
      reservationTime: json['reservation_time'],
      reservationDateTime: DateTime.parse(json['reservation_datetime']),
      duration: json['duration'],
      status: ReservationStatus.fromString(json['status']),
      statusLabel: json['status_label'],
      specialRequests: json['special_requests'],
      notes: json['notes'],
      confirmationSent: json['confirmation_sent'],
      reminderSent: json['reminder_sent'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      confirmedAt: json['confirmed_at'] != null
        ? DateTime.parse(json['confirmed_at'])
        : null,
      cancelledAt: json['cancelled_at'] != null
        ? DateTime.parse(json['cancelled_at'])
        : null,
    );
  }

  // Helpers
  bool get isPending => status == ReservationStatus.pending;
  bool get isConfirmed => status == ReservationStatus.confirmed;
  bool get isCancelled => status == ReservationStatus.cancelled;
  bool get isUpcoming => reservationDateTime.isAfter(DateTime.now());

  String get formattedDate => DateFormat('EEEE, d MMMM yyyy', 'es')
    .format(reservationDateTime);

  String get formattedTime => DateFormat('HH:mm')
    .format(reservationDateTime);
}
```

### Enum: ReservationStatus

```dart
enum ReservationStatus {
  pending,
  confirmed,
  cancelled,
  completed,
  noShow;

  static ReservationStatus fromString(String status) {
    switch (status) {
      case 'pending':
        return ReservationStatus.pending;
      case 'confirmed':
        return ReservationStatus.confirmed;
      case 'cancelled':
        return ReservationStatus.cancelled;
      case 'completed':
        return ReservationStatus.completed;
      case 'no_show':
        return ReservationStatus.noShow;
      default:
        return ReservationStatus.pending;
    }
  }

  Color get color {
    switch (this) {
      case ReservationStatus.pending:
        return Colors.orange;
      case ReservationStatus.confirmed:
        return Colors.green;
      case ReservationStatus.cancelled:
        return Colors.red;
      case ReservationStatus.completed:
        return Colors.blue;
      case ReservationStatus.noShow:
        return Colors.grey;
    }
  }

  IconData get icon {
    switch (this) {
      case ReservationStatus.pending:
        return Icons.schedule;
      case ReservationStatus.confirmed:
        return Icons.check_circle;
      case ReservationStatus.cancelled:
        return Icons.cancel;
      case ReservationStatus.completed:
        return Icons.done_all;
      case ReservationStatus.noShow:
        return Icons.person_off;
    }
  }
}
```

### Modelo: Customer

```dart
class Customer {
  final String name;
  final String phone;
  final String? email;

  Customer({
    required this.name,
    required this.phone,
    this.email,
  });

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      name: json['name'],
      phone: json['phone'],
      email: json['email'],
    );
  }
}
```

### Modelo: TableInfo

```dart
class TableInfo {
  final int id;
  final String tableNumber;
  final String tableName;
  final int capacity;
  final String? location;

  TableInfo({
    required this.id,
    required this.tableNumber,
    required this.tableName,
    required this.capacity,
    this.location,
  });

  factory TableInfo.fromJson(Map<String, dynamic> json) {
    return TableInfo(
      id: json['id'],
      tableNumber: json['table_number'],
      tableName: json['table_name'],
      capacity: json['capacity'],
      location: json['location'],
    );
  }
}
```

---

## 🔔 Notificaciones Push

### Configurar Firebase Cloud Messaging

**1. Registrar dispositivo**

Cuando el admin inicia sesión en la app, registra su token FCM:

```dart
// En el servicio de autenticación
Future<void> registerDeviceToken() async {
  final fcmToken = await FirebaseMessaging.instance.getToken();

  // Guardar en WordPress (meta_data del usuario)
  await http.post(
    Uri.parse('$baseUrl/wp-json/restaurant/v1/admin/register-device'),
    headers: {
      'Authorization': 'Bearer $jwtToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'device_token': fcmToken,
      'platform': Platform.isIOS ? 'ios' : 'android',
    }),
  );
}
```

**2. Escuchar notificaciones**

```dart
void setupPushNotifications() {
  FirebaseMessaging.onMessage.listen((RemoteMessage message) {
    // Nueva reserva recibida mientras app está abierta
    if (message.data['type'] == 'new_reservation') {
      showInAppNotification(
        title: message.notification?.title ?? 'Nueva Reserva',
        body: message.notification?.body ?? '',
        onTap: () {
          navigateToReservation(message.data['reservation_id']);
        },
      );

      // Recargar lista de reservas
      refreshReservations();
    }
  });

  FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
    // Usuario toca la notificación
    if (message.data['type'] == 'new_reservation') {
      navigateToReservation(message.data['reservation_id']);
    }
  });
}
```

**3. Tipos de Notificaciones**

| Evento | Tipo | Título | Cuerpo |
|--------|------|--------|--------|
| Nueva reserva | `new_reservation` | "Nueva Reserva" | "Juan Pérez - 4 personas - 20:30" |
| Cancelación | `reservation_cancelled` | "Reserva Cancelada" | "RES-260204-0001 ha sido cancelada" |
| Recordatorio | `reservation_reminder` | "Recordatorio" | "Reserva en 1 hora - Mesa 05" |

---

## 🔄 Sincronización en Tiempo Real

### Polling (Método Simple)

```dart
class ReservationService {
  Timer? _pollingTimer;

  void startPolling() {
    _pollingTimer = Timer.periodic(
      Duration(seconds: 30),
      (_) => refreshReservations(),
    );
  }

  void stopPolling() {
    _pollingTimer?.cancel();
  }

  Future<void> refreshReservations() async {
    try {
      final reservations = await getReservations(
        upcoming: true,
        status: 'pending,confirmed',
      );

      // Actualizar estado
      _reservationsController.add(reservations);

      // Verificar si hay nuevas reservas
      checkForNewReservations(reservations);
    } catch (e) {
      print('Error al refrescar: $e');
    }
  }

  void checkForNewReservations(List<Reservation> current) {
    final newReservations = current.where((r) {
      return r.isPending &&
             !_previousReservations.contains(r.id);
    }).toList();

    if (newReservations.isNotEmpty) {
      // Mostrar badge o notificación local
      showNewReservationsBadge(newReservations.length);
      playNotificationSound();
    }

    _previousReservations = current.map((r) => r.id).toSet();
  }
}
```

---

## 🎨 Componentes UI Sugeridos

### 1. ReservationCard Widget

```dart
class ReservationCard extends StatelessWidget {
  final Reservation reservation;
  final VoidCallback? onTap;
  final VoidCallback? onConfirm;
  final VoidCallback? onCancel;

  const ReservationCard({
    Key? key,
    required this.reservation,
    this.onTap,
    this.onConfirm,
    this.onCancel,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    reservation.formattedTime,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Chip(
                    label: Text(reservation.statusLabel),
                    backgroundColor: reservation.status.color,
                    labelStyle: TextStyle(color: Colors.white),
                  ),
                ],
              ),

              SizedBox(height: 12),

              // Cliente
              Row(
                children: [
                  Icon(Icons.person, size: 20, color: Colors.grey[600]),
                  SizedBox(width: 8),
                  Text(
                    reservation.customer.name,
                    style: TextStyle(fontSize: 16),
                  ),
                ],
              ),

              SizedBox(height: 8),

              // Personas y Mesa
              Row(
                children: [
                  Icon(Icons.people, size: 20, color: Colors.grey[600]),
                  SizedBox(width: 8),
                  Text('${reservation.guestsCount} personas'),
                  SizedBox(width: 16),
                  if (reservation.table != null) ...[
                    Icon(Icons.table_restaurant, size: 20, color: Colors.grey[600]),
                    SizedBox(width: 8),
                    Text(reservation.table!.tableName),
                  ],
                ],
              ),

              // Teléfono
              SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.phone, size: 20, color: Colors.grey[600]),
                  SizedBox(width: 8),
                  Text(reservation.customer.phone),
                ],
              ),

              // Solicitudes especiales
              if (reservation.specialRequests != null) ...[
                SizedBox(height: 8),
                Container(
                  padding: EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.blue[50],
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.info_outline, size: 16, color: Colors.blue),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          reservation.specialRequests!,
                          style: TextStyle(fontSize: 12),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Botones de acción
              if (reservation.isPending) ...[
                SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        icon: Icon(Icons.check),
                        label: Text('Confirmar'),
                        onPressed: onConfirm,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.green,
                        ),
                      ),
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        icon: Icon(Icons.close),
                        label: Text('Rechazar'),
                        onPressed: onCancel,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
```

### 2. Filtros de Reservas

```dart
class ReservationFilters extends StatelessWidget {
  final ReservationStatus? selectedStatus;
  final DateTime? selectedDate;
  final bool showUpcoming;
  final Function(ReservationStatus?) onStatusChanged;
  final Function(DateTime?) onDateChanged;
  final Function(bool) onUpcomingChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(16),
      child: Column(
        children: [
          // Filtro de estado
          DropdownButtonFormField<ReservationStatus>(
            value: selectedStatus,
            decoration: InputDecoration(
              labelText: 'Estado',
              border: OutlineInputBorder(),
            ),
            items: [
              DropdownMenuItem(value: null, child: Text('Todos')),
              ...ReservationStatus.values.map((status) {
                return DropdownMenuItem(
                  value: status,
                  child: Text(status.name),
                );
              }),
            ],
            onChanged: onStatusChanged,
          ),

          SizedBox(height: 16),

          // Filtro de fecha
          InkWell(
            onTap: () async {
              final date = await showDatePicker(
                context: context,
                initialDate: selectedDate ?? DateTime.now(),
                firstDate: DateTime.now().subtract(Duration(days: 30)),
                lastDate: DateTime.now().add(Duration(days: 90)),
              );
              if (date != null) {
                onDateChanged(date);
              }
            },
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: 'Fecha',
                border: OutlineInputBorder(),
                suffixIcon: Icon(Icons.calendar_today),
              ),
              child: Text(
                selectedDate != null
                  ? DateFormat('dd/MM/yyyy').format(selectedDate!)
                  : 'Todas las fechas',
              ),
            ),
          ),

          SizedBox(height: 16),

          // Solo próximas
          SwitchListTile(
            title: Text('Solo reservas futuras'),
            value: showUpcoming,
            onChanged: onUpcomingChanged,
          ),
        ],
      ),
    );
  }
}
```

---

## ✅ Checklist de Implementación

### Fase 1: Setup Básico
- [ ] Configurar autenticación JWT
- [ ] Crear modelos de datos
- [ ] Implementar servicio de API
- [ ] Configurar manejo de errores

### Fase 2: Pantallas Principales
- [ ] Pantalla de lista de reservas
- [ ] Pantalla de detalles
- [ ] Implementar filtros
- [ ] Implementar búsqueda por código

### Fase 3: Acciones
- [ ] Confirmar reserva
- [ ] Cancelar reserva
- [ ] Actualizar reserva
- [ ] Cambiar mesa

### Fase 4: Notificaciones
- [ ] Configurar Firebase
- [ ] Registrar dispositivo
- [ ] Manejar notificaciones push
- [ ] Notificaciones locales

### Fase 5: Estadísticas
- [ ] Pantalla de estadísticas
- [ ] Gráficos de ocupación
- [ ] Indicadores clave

### Fase 6: Optimizaciones
- [ ] Caché local
- [ ] Modo offline
- [ ] Pull-to-refresh
- [ ] Paginación

---

## 📚 Recursos Adicionales

- **Documentación API completa**: `GUIA-RESERVAS.md`
- **Ejemplos de integración**: Ver sección de ejemplos arriba
- **Soporte**: support@flavor-platform.com

---

¡Sistema completo de gestión de reservas para app móvil admin! 🎉
