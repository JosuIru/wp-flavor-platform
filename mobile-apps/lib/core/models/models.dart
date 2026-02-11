/// Modelos de datos para las apps

// ==========================================
// USUARIO Y AUTH
// ==========================================

class User {
  final int id;
  final String name;
  final String email;
  final bool isAdmin;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.isAdmin = false,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      isAdmin: json['is_admin'] ?? false,
    );
  }
}

// ==========================================
// NEGOCIO
// ==========================================

class BusinessInfo {
  final String name;
  final String description;
  final String address;
  final String phone;
  final String email;
  final String whatsapp;
  final String schedule;
  final String? mapsUrl;
  // Redes sociales
  final String facebook;
  final String instagram;
  final String twitter;
  final String youtube;
  final String website;

  BusinessInfo({
    required this.name,
    this.description = '',
    this.address = '',
    this.phone = '',
    this.email = '',
    this.whatsapp = '',
    this.schedule = '',
    this.mapsUrl,
    this.facebook = '',
    this.instagram = '',
    this.twitter = '',
    this.youtube = '',
    this.website = '',
  });

  factory BusinessInfo.fromJson(Map<String, dynamic> json) {
    final business = json['business'] ?? json;
    final social = business['social'] ?? {};
    return BusinessInfo(
      name: business['name'] ?? '',
      description: business['description'] ?? '',
      address: business['address'] ?? '',
      phone: business['phone'] ?? '',
      email: business['email'] ?? '',
      whatsapp: business['whatsapp'] ?? '',
      schedule: business['schedule'] ?? '',
      mapsUrl: business['maps_url'],
      facebook: social['facebook'] ?? business['facebook'] ?? '',
      instagram: social['instagram'] ?? business['instagram'] ?? '',
      twitter: social['twitter'] ?? business['twitter'] ?? '',
      youtube: social['youtube'] ?? business['youtube'] ?? '',
      website: social['website'] ?? business['website'] ?? '',
    );
  }
}

// ==========================================
// BLOG POSTS
// ==========================================

class BlogPost {
  final int id;
  final String title;
  final String excerpt;
  final String content;
  final String url;
  final String imageUrl;
  final String date;
  final String author;
  final List<String> categories;

  BlogPost({
    required this.id,
    required this.title,
    this.excerpt = '',
    this.content = '',
    this.url = '',
    this.imageUrl = '',
    this.date = '',
    this.author = '',
    this.categories = const [],
  });

  factory BlogPost.fromJson(Map<String, dynamic> json) {
    return BlogPost(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      excerpt: json['excerpt'] ?? '',
      content: json['content'] ?? '',
      url: json['url'] ?? json['link'] ?? '',
      imageUrl: json['image_url'] ?? json['featured_image'] ?? '',
      date: json['date'] ?? '',
      author: json['author'] ?? '',
      categories: List<String>.from(json['categories'] ?? []),
    );
  }

  String get dateFormatted {
    try {
      final dt = DateTime.parse(date);
      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (e) {
      return date;
    }
  }
}

// ==========================================
// SITE UPDATES / NOVEDADES
// ==========================================

class SiteUpdate {
  final int id;
  final String title;
  final String summary;
  final String type; // 'news', 'promo', 'event', 'alert'
  final String date;
  final String? url;
  final String? imageUrl;

  SiteUpdate({
    required this.id,
    required this.title,
    this.summary = '',
    this.type = 'news',
    this.date = '',
    this.url,
    this.imageUrl,
  });

  factory SiteUpdate.fromJson(Map<String, dynamic> json) {
    return SiteUpdate(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      summary: json['summary'] ?? json['excerpt'] ?? '',
      type: json['type'] ?? 'news',
      date: json['date'] ?? '',
      url: json['url'],
      imageUrl: json['image_url'],
    );
  }

  String get typeLabel {
    switch (type) {
      case 'promo':
        return 'Promoción';
      case 'event':
        return 'Evento';
      case 'alert':
        return 'Aviso';
      case 'news':
      default:
        return 'Novedad';
    }
  }

  String get dateFormatted {
    try {
      final dt = DateTime.parse(date);
      final now = DateTime.now();
      final diff = now.difference(dt);

      if (diff.inDays == 0) return 'Hoy';
      if (diff.inDays == 1) return 'Ayer';
      if (diff.inDays < 7) return 'Hace ${diff.inDays} días';

      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${dt.day} ${months[dt.month - 1]}';
    } catch (e) {
      return date;
    }
  }
}

// ==========================================
// DISPONIBILIDAD
// ==========================================

class AvailabilityDay {
  final String date;
  final String state;
  final String stateName;
  final String schedule;
  final String color;

  AvailabilityDay({
    required this.date,
    required this.state,
    required this.stateName,
    this.schedule = '',
    this.color = '#4CAF50',
  });

  factory AvailabilityDay.fromJson(Map<String, dynamic> json) {
    return AvailabilityDay(
      date: json['date'] ?? '',
      state: json['state'] ?? '',
      stateName: json['state_name'] ?? json['state'] ?? '',
      schedule: json['schedule'] ?? '',
      color: json['color'] ?? '#4CAF50',
    );
  }

  DateTime get dateTime => DateTime.parse(date);
}

// ==========================================
// TICKETS
// ==========================================

class TicketType {
  final String slug;
  final String name;
  final String description;
  final double price;
  final int capacity;
  final String duration;
  final String type;
  final List<String> dependsOn; // Slugs de tickets requeridos
  final int minQuantity; // Cantidad mínima requerida
  final int maxQuantity; // Cantidad máxima permitida

  TicketType({
    required this.slug,
    required this.name,
    this.description = '',
    this.price = 0,
    this.capacity = 0,
    this.duration = '',
    this.type = 'normal',
    this.dependsOn = const [],
    this.minQuantity = 0,
    this.maxQuantity = 10,
  });

  factory TicketType.fromJson(Map<String, dynamic> json) {
    // Parsear dependencias - puede venir como lista o string separado por comas
    List<String> parseDependsOn(dynamic value) {
      if (value == null) return [];
      if (value is List) return List<String>.from(value);
      if (value is String && value.isNotEmpty) {
        return value.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty).toList();
      }
      return [];
    }

    return TicketType(
      slug: json['slug'] ?? '',
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      price: (json['price'] ?? 0).toDouble(),
      capacity: json['capacity'] ?? 0,
      duration: json['duration'] ?? '',
      type: json['type'] ?? 'normal',
      dependsOn: parseDependsOn(json['depends_on'] ?? json['dependsOn'] ?? json['requiere']),
      minQuantity: json['min_quantity'] ?? json['minQuantity'] ?? 0,
      maxQuantity: json['max_quantity'] ?? json['maxQuantity'] ?? 10,
    );
  }

  String get formattedPrice => '${price.toStringAsFixed(2)}€';

  /// Verifica si las dependencias están satisfechas
  bool areDependenciesSatisfied(Map<String, int> selectedQuantities) {
    if (dependsOn.isEmpty) return true;

    for (final requiredSlug in dependsOn) {
      final qty = selectedQuantities[requiredSlug] ?? 0;
      if (qty <= 0) return false;
    }
    return true;
  }

  /// Verifica si este ticket puede ser seleccionado
  bool canBeSelected(Map<String, int> selectedQuantities, List<TicketType> allTickets) {
    return areDependenciesSatisfied(selectedQuantities);
  }

  /// Obtiene los nombres de los tickets requeridos
  List<String> getRequiredTicketNames(List<TicketType> allTickets) {
    return dependsOn.map((slug) {
      final ticket = allTickets.where((t) => t.slug == slug).firstOrNull;
      return ticket?.name ?? slug;
    }).toList();
  }
}

// ==========================================
// EXPERIENCIAS
// ==========================================

class Experience {
  final String id;
  final String name;
  final String description;
  final String color;
  final String duration;
  final List<String> schedules;

  Experience({
    required this.id,
    required this.name,
    this.description = '',
    this.color = '#4CAF50',
    this.duration = '',
    this.schedules = const [],
  });

  factory Experience.fromJson(Map<String, dynamic> json) {
    return Experience(
      id: json['id'] ?? '',
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      color: json['color'] ?? '#4CAF50',
      duration: json['duration'] ?? '',
      schedules: List<String>.from(json['schedules'] ?? []),
    );
  }
}

// ==========================================
// RESERVAS
// ==========================================

class Reservation {
  final int id;
  final String date;
  final String ticketCode;
  final String ticketSlug;
  final String ticketName;
  final String status;
  final String? checkin;
  final bool blocked;
  final Customer? customer;
  final int? orderId;
  final String? createdAt;

  Reservation({
    required this.id,
    required this.date,
    required this.ticketCode,
    required this.ticketSlug,
    required this.ticketName,
    required this.status,
    this.checkin,
    this.blocked = false,
    this.customer,
    this.orderId,
    this.createdAt,
  });

  factory Reservation.fromJson(Map<String, dynamic> json) {
    // El id puede venir como String o int desde el servidor
    int parseId(dynamic value) {
      if (value == null) return 0;
      if (value is int) return value;
      if (value is String) return int.tryParse(value) ?? 0;
      return 0;
    }

    return Reservation(
      id: parseId(json['id']),
      date: json['date'] ?? json['fecha'] ?? '',
      ticketCode: json['ticket_code'] ?? '',
      ticketSlug: json['ticket_slug'] ?? '',
      ticketName: json['ticket_name'] ?? json['ticket_slug'] ?? '',
      status: json['status'] ?? json['estado'] ?? '',
      checkin: json['checkin'],
      blocked: json['blocked'] == true || json['blocked'] == 1 || json['blocked'] == '1',
      customer: json['customer'] != null
          ? Customer.fromJson(json['customer'])
          : null,
      orderId: parseId(json['order_id']),
      createdAt: json['created_at'],
    );
  }

  String get statusDisplay {
    switch (status) {
      case 'pendiente':
        return 'Pendiente';
      case 'usado':
        return 'Usado';
      case 'cancelado':
        return 'Cancelado';
      default:
        return status;
    }
  }

  bool get isUsed => status == 'usado';
  bool get isCancelled => status == 'cancelado';
  bool get isPending => status == 'pendiente';
}

// ==========================================
// CLIENTES
// ==========================================

class Customer {
  final String name;
  final String email;
  final String phone;
  final int? totalReservations;
  final String? firstReservation;
  final String? lastReservation;

  Customer({
    required this.name,
    this.email = '',
    this.phone = '',
    this.totalReservations,
    this.firstReservation,
    this.lastReservation,
  });

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      name: json['name'] ?? json['nombre'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'] ?? json['telefono'] ?? '',
      totalReservations: json['total_reservations'] ?? json['total_reservas'],
      firstReservation: json['first_reservation'] ?? json['primera_reserva'],
      lastReservation: json['last_reservation'] ?? json['ultima_reserva'],
    );
  }
}

// ==========================================
// DASHBOARD
// ==========================================

class DashboardData {
  final DashboardToday today;
  final DashboardWeek week;
  final DashboardMonth month;

  DashboardData({
    required this.today,
    required this.week,
    required this.month,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final dashboard = json['dashboard'] ?? json;
    return DashboardData(
      today: DashboardToday.fromJson(dashboard['today'] ?? {}),
      week: DashboardWeek.fromJson(dashboard['week'] ?? {}),
      month: DashboardMonth.fromJson(dashboard['month'] ?? {}),
    );
  }
}

class DashboardToday {
  final String date;
  final int reservations;
  final int checkins;
  final int pendingCheckins;

  DashboardToday({
    required this.date,
    this.reservations = 0,
    this.checkins = 0,
    this.pendingCheckins = 0,
  });

  factory DashboardToday.fromJson(Map<String, dynamic> json) {
    return DashboardToday(
      date: json['date'] ?? '',
      reservations: json['reservations'] ?? 0,
      checkins: json['checkins'] ?? 0,
      pendingCheckins: json['pending_checkins'] ?? 0,
    );
  }
}

class DashboardWeek {
  final int reservations;

  DashboardWeek({this.reservations = 0});

  factory DashboardWeek.fromJson(Map<String, dynamic> json) {
    return DashboardWeek(
      reservations: json['reservations'] ?? 0,
    );
  }
}

class DashboardMonth {
  final double revenue;
  final String formattedRevenue;

  DashboardMonth({
    this.revenue = 0,
    this.formattedRevenue = '0,00€',
  });

  factory DashboardMonth.fromJson(Map<String, dynamic> json) {
    return DashboardMonth(
      revenue: (json['revenue'] ?? 0).toDouble(),
      formattedRevenue: json['formatted_revenue'] ?? '0,00€',
    );
  }
}

// ==========================================
// CHAT
// ==========================================

class ChatMessage {
  final String id;
  final String content;
  final bool isUser;
  final DateTime timestamp;
  final bool isLoading;
  final bool hasError;

  ChatMessage({
    required this.id,
    required this.content,
    required this.isUser,
    DateTime? timestamp,
    this.isLoading = false,
    this.hasError = false,
  }) : timestamp = timestamp ?? DateTime.now();

  factory ChatMessage.user(String content) {
    return ChatMessage(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      content: content,
      isUser: true,
    );
  }

  factory ChatMessage.assistant(String content) {
    return ChatMessage(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      content: content,
      isUser: false,
    );
  }

  factory ChatMessage.loading() {
    return ChatMessage(
      id: 'loading',
      content: '',
      isUser: false,
      isLoading: true,
    );
  }

  ChatMessage copyWith({
    String? content,
    bool? isLoading,
    bool? hasError,
  }) {
    return ChatMessage(
      id: id,
      content: content ?? this.content,
      isUser: isUser,
      timestamp: timestamp,
      isLoading: isLoading ?? this.isLoading,
      hasError: hasError ?? this.hasError,
    );
  }
}

// ==========================================
// CARRITO
// ==========================================

class CartItem {
  final String slug;
  final String name;
  final int quantity;
  final double price;
  final double subtotal;

  CartItem({
    required this.slug,
    required this.name,
    required this.quantity,
    required this.price,
    required this.subtotal,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      slug: json['slug'] ?? '',
      name: json['name'] ?? '',
      quantity: json['quantity'] ?? 1,
      price: (json['price'] ?? 0).toDouble(),
      subtotal: (json['subtotal'] ?? 0).toDouble(),
    );
  }
}

class ReservationDraft {
  final String date;
  final List<CartItem> items;
  final double total;
  final Customer? customer;

  ReservationDraft({
    required this.date,
    required this.items,
    required this.total,
    this.customer,
  });

  factory ReservationDraft.fromJson(Map<String, dynamic> json) {
    final reservation = json['reservation'] ?? json;
    return ReservationDraft(
      date: reservation['date'] ?? '',
      items: (reservation['items'] as List?)
              ?.map((e) => CartItem.fromJson(e))
              .toList() ??
          [],
      total: (reservation['total'] ?? 0).toDouble(),
      customer: reservation['customer'] != null
          ? Customer.fromJson(reservation['customer'])
          : null,
    );
  }
}

// ==========================================
// CHATS ESCALADOS (ADMIN)
// ==========================================

class EscalatedChat {
  final int escalationId;
  final String sessionId;
  final String reason;
  final String summary;
  final String status; // 'pending', 'contacted', 'resolved'
  final String language;
  final int messageCount;
  final String createdAt;
  final String? resolvedAt;
  final String? conversationStarted;
  final String? notes;

  EscalatedChat({
    required this.escalationId,
    required this.sessionId,
    required this.reason,
    required this.summary,
    required this.status,
    this.language = 'es',
    this.messageCount = 0,
    required this.createdAt,
    this.resolvedAt,
    this.conversationStarted,
    this.notes,
  });

  factory EscalatedChat.fromJson(Map<String, dynamic> json) {
    return EscalatedChat(
      escalationId: json['escalation_id'] ?? 0,
      sessionId: json['session_id'] ?? '',
      reason: json['reason'] ?? '',
      summary: json['summary'] ?? '',
      status: json['status'] ?? 'pending',
      language: json['language'] ?? 'es',
      messageCount: json['message_count'] ?? 0,
      createdAt: json['created_at'] ?? '',
      resolvedAt: json['resolved_at'],
      conversationStarted: json['conversation_started'],
      notes: json['notes'],
    );
  }

  bool get isPending => status == 'pending';
  bool get isContacted => status == 'contacted';
  bool get isResolved => status == 'resolved';

  String get statusDisplay {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'contacted':
        return 'Contactado';
      case 'resolved':
        return 'Resuelto';
      default:
        return status;
    }
  }

  String get createdAtFormatted {
    try {
      final dt = DateTime.parse(createdAt);
      final now = DateTime.now();
      final diff = now.difference(dt);

      if (diff.inMinutes < 60) {
        return 'Hace ${diff.inMinutes}m';
      }
      if (diff.inHours < 24) {
        return 'Hace ${diff.inHours}h';
      }
      if (diff.inDays < 7) {
        return 'Hace ${diff.inDays}d';
      }

      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${dt.day} ${months[dt.month - 1]}';
    } catch (e) {
      return createdAt;
    }
  }
}

class EscalatedChatDetail {
  final String sessionId;
  final String language;
  final String startedAt;
  final EscalationInfo? escalation;
  final List<ChatMessage> messages;
  final ReservationDraft? draft;

  EscalatedChatDetail({
    required this.sessionId,
    this.language = 'es',
    required this.startedAt,
    this.escalation,
    required this.messages,
    this.draft,
  });

  factory EscalatedChatDetail.fromJson(Map<String, dynamic> json) {
    return EscalatedChatDetail(
      sessionId: json['session_id'] ?? '',
      language: json['language'] ?? 'es',
      startedAt: json['started_at'] ?? '',
      escalation: json['escalation'] != null
          ? EscalationInfo.fromJson(json['escalation'])
          : null,
      messages: (json['messages'] as List?)
              ?.map((m) => ChatMessage(
                    id: DateTime.parse(m['timestamp'] ?? DateTime.now().toIso8601String())
                        .millisecondsSinceEpoch
                        .toString(),
                    content: m['content'] ?? '',
                    isUser: m['role'] == 'user',
                    timestamp: DateTime.parse(m['timestamp'] ?? DateTime.now().toIso8601String()),
                  ))
              .toList() ??
          [],
      draft: json['draft'] != null ? ReservationDraft.fromJson(json['draft']) : null,
    );
  }
}

class EscalationInfo {
  final int id;
  final String reason;
  final String summary;
  final String status;
  final String createdAt;
  final String? resolvedAt;
  final String? notes;

  EscalationInfo({
    required this.id,
    required this.reason,
    required this.summary,
    required this.status,
    required this.createdAt,
    this.resolvedAt,
    this.notes,
  });

  factory EscalationInfo.fromJson(Map<String, dynamic> json) {
    return EscalationInfo(
      id: json['id'] ?? 0,
      reason: json['reason'] ?? '',
      summary: json['summary'] ?? '',
      status: json['status'] ?? 'pending',
      createdAt: json['created_at'] ?? '',
      resolvedAt: json['resolved_at'],
      notes: json['notes'],
    );
  }
}

// ==========================================
// CAMPAMENTOS
// ==========================================

class CampTerm {
  final String slug;
  final String name;

  CampTerm({
    required this.slug,
    required this.name,
  });

  factory CampTerm.fromJson(Map<String, dynamic> json) {
    return CampTerm(
      slug: json['slug'] ?? '',
      name: json['name'] ?? '',
    );
  }
}

class CampDates {
  final String start;
  final String end;
  final String? schedule;
  final String? location;
  final String? includes;
  final String? requirements;

  CampDates({
    required this.start,
    required this.end,
    this.schedule,
    this.location,
    this.includes,
    this.requirements,
  });

  factory CampDates.fromJson(Map<String, dynamic> json) {
    return CampDates(
      start: json['start'] ?? '',
      end: json['end'] ?? '',
      schedule: json['schedule'],
      location: json['location'],
      includes: json['includes'],
      requirements: json['requirements'],
    );
  }

  DateTime? get startDate {
    try {
      return DateTime.parse(start);
    } catch (e) {
      return null;
    }
  }

  DateTime? get endDate {
    try {
      return DateTime.parse(end);
    } catch (e) {
      return null;
    }
  }

  int get durationDays {
    final s = startDate;
    final e = endDate;
    if (s == null || e == null) return 0;
    return e.difference(s).inDays + 1;
  }
}

class Camp {
  final int id;
  final String title;
  final String slug;
  final String excerpt;
  final String? description;
  final String featuredImage;
  final List<CampTerm> categories;
  final List<CampTerm> ages;
  final List<CampTerm> languages;
  final double price;
  final double priceTotal;
  final String duration;
  final String? label;
  final bool inscriptionOpen;
  final int inscriptionCount;
  final int? capacity;
  final CampDates? dates;
  final String? schedule;
  final String? location;
  final List<String>? gallery;
  final String? categoryColor;

  Camp({
    required this.id,
    required this.title,
    required this.slug,
    this.excerpt = '',
    this.description,
    this.featuredImage = '',
    this.categories = const [],
    this.ages = const [],
    this.languages = const [],
    this.price = 0,
    this.priceTotal = 0,
    this.duration = '',
    this.label,
    this.inscriptionOpen = true,
    this.inscriptionCount = 0,
    this.capacity,
    this.dates,
    this.schedule,
    this.location,
    this.gallery,
    this.categoryColor,
  });

  factory Camp.fromJson(Map<String, dynamic> json) {
    return Camp(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      excerpt: json['excerpt'] ?? '',
      description: json['description'],
      featuredImage: json['featured_image'] ?? '',
      categories: (json['categories'] as List?)
              ?.map((c) => CampTerm.fromJson(c))
              .toList() ??
          [],
      ages: (json['ages'] as List?)?.map((a) => CampTerm.fromJson(a)).toList() ??
          [],
      languages: (json['languages'] as List?)
              ?.map((l) => CampTerm.fromJson(l))
              .toList() ??
          [],
      price: (json['price'] is num) ? (json['price'] as num).toDouble() : 0,
      priceTotal: (json['price_total'] is num)
          ? (json['price_total'] as num).toDouble()
          : 0,
      duration: json['duration'] ?? '',
      label: json['label'],
      inscriptionOpen: json['inscription_open'] ?? true,
      inscriptionCount: json['inscription_count'] ?? 0,
      capacity: json['capacity'],
      dates:
          json['dates'] != null ? CampDates.fromJson(json['dates']) : null,
      schedule: json['schedule'],
      location: json['location'],
      gallery:
          json['gallery'] != null ? List<String>.from(json['gallery']) : null,
      categoryColor: json['category_color'],
    );
  }

  bool get isFull => capacity != null && inscriptionCount >= capacity!;
  bool get isClosed => !inscriptionOpen;
  int get availablePlaces =>
      capacity != null ? (capacity! - inscriptionCount).clamp(0, 999) : 999;

  String get formattedPrice => '${price.toStringAsFixed(2)}€';
  String get formattedPriceTotal => '${priceTotal.toStringAsFixed(2)}€';

  String get categoriesText =>
      categories.map((c) => c.name).join(', ');
  String get agesText => ages.map((a) => a.name).join(', ');
  String get languagesText => languages.map((l) => l.name).join(', ');

  String get statusText {
    if (isClosed) return 'Cerrado';
    if (isFull) return 'Completo';
    return 'Abierto';
  }
}

class CampInscription {
  final int id;
  final int? campId;
  final String participantName;
  final int participantAge;
  final String participantAllergies;
  final String guardianName;
  final String guardianEmail;
  final String guardianPhone;
  final String paymentStatus;
  final String inscriptionDate;
  final double amount;
  final String? accessToken;

  CampInscription({
    required this.id,
    this.campId,
    required this.participantName,
    this.participantAge = 0,
    this.participantAllergies = '',
    required this.guardianName,
    required this.guardianEmail,
    required this.guardianPhone,
    this.paymentStatus = 'pending',
    required this.inscriptionDate,
    this.amount = 0,
    this.accessToken,
  });

  factory CampInscription.fromJson(Map<String, dynamic> json) {
    return CampInscription(
      id: json['id'] ?? 0,
      campId: json['camp_id'],
      participantName: json['participant_name'] ?? '',
      participantAge: json['participant_age'] ?? 0,
      participantAllergies: json['participant_allergies'] ?? '',
      guardianName: json['guardian_name'] ?? '',
      guardianEmail: json['guardian_email'] ?? '',
      guardianPhone: json['guardian_phone'] ?? '',
      paymentStatus: json['payment_status'] ?? 'pending',
      inscriptionDate: json['inscription_date'] ?? '',
      amount: (json['amount'] is num) ? (json['amount'] as num).toDouble() : 0,
      accessToken: json['access_token'],
    );
  }

  bool get isPaid => paymentStatus == 'paid';
  bool get isPending => paymentStatus == 'pending';

  String get statusDisplay {
    switch (paymentStatus) {
      case 'paid':
        return 'Pagado';
      case 'pending':
        return 'Pendiente';
      default:
        return paymentStatus;
    }
  }

  String get dateFormatted {
    try {
      final dt = DateTime.parse(inscriptionDate);
      final months = [
        'ene',
        'feb',
        'mar',
        'abr',
        'may',
        'jun',
        'jul',
        'ago',
        'sep',
        'oct',
        'nov',
        'dic'
      ];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (e) {
      return inscriptionDate;
    }
  }
}

class CampStats {
  final int totalInscriptions;
  final double totalRevenue;
  final Map<String, int> byCategory;
  final Map<String, double> revenueByCamp;

  CampStats({
    this.totalInscriptions = 0,
    this.totalRevenue = 0,
    this.byCategory = const {},
    this.revenueByCamp = const {},
  });

  factory CampStats.fromJson(Map<String, dynamic> json) {
    final byCategoryMap = <String, int>{};
    if (json['by_category'] != null) {
      (json['by_category'] as Map).forEach((key, value) {
        byCategoryMap[key.toString()] = (value is num) ? value.toInt() : 0;
      });
    }

    final revenueByCampMap = <String, double>{};
    if (json['revenue_by_camp'] != null) {
      (json['revenue_by_camp'] as Map).forEach((key, value) {
        revenueByCampMap[key.toString()] =
            (value is num) ? value.toDouble() : 0.0;
      });
    }

    return CampStats(
      totalInscriptions: json['total_inscriptions'] ?? 0,
      totalRevenue: (json['total_revenue'] is num)
          ? (json['total_revenue'] as num).toDouble()
          : 0.0,
      byCategory: byCategoryMap,
      revenueByCamp: revenueByCampMap,
    );
  }
}

// ==========================================
// INFO SECTIONS
// ==========================================

/// Representa una sección configurable de la pantalla Info
class InfoSection {
  final String id;
  final String label;
  final String icon;
  final int order;
  final String type;

  InfoSection({
    required this.id,
    required this.label,
    required this.icon,
    required this.order,
    required this.type,
  });

  factory InfoSection.fromJson(Map<String, dynamic> json) {
    return InfoSection(
      id: json['id'] as String? ?? '',
      label: json['label'] as String? ?? '',
      icon: json['icon'] as String? ?? 'article',
      order: json['order'] as int? ?? 0,
      type: json['type'] as String? ?? 'predefined',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'label': label,
      'icon': icon,
      'order': order,
      'type': type,
    };
  }
}

// ==========================================
// DRAWER ITEMS
// ==========================================

/// Representa un item del menú drawer/hamburguesa
class DrawerItem {
  final String title;
  final String url;
  final String icon;
  final String contentType;
  final String contentRef;
  final int order;
  final bool enabled;

  DrawerItem({
    required this.title,
    required this.url,
    required this.icon,
    required this.contentType,
    required this.contentRef,
    required this.order,
    required this.enabled,
  });

  factory DrawerItem.fromJson(Map<String, dynamic> json) {
    return DrawerItem(
      title: json['title'] as String? ?? '',
      url: json['url'] as String? ?? '',
      icon: json['icon'] as String? ?? 'public',
      contentType: json['content_type'] as String? ?? 'page',
      contentRef: json['content_ref'] as String? ?? '',
      order: json['order'] as int? ?? 0,
      enabled: json['enabled'] as bool? ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'title': title,
      'url': url,
      'icon': icon,
      'content_type': contentType,
      'content_ref': contentRef,
      'order': order,
      'enabled': enabled,
    };
  }
}
