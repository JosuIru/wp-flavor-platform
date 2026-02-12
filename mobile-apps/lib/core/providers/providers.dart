import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/models.dart';
import '../utils/logger.dart';

/// Provider para el cliente API
/// NOTA: Este provider se sobreescribe en main_admin.dart y main_client.dart
/// con la URL correcta del servidor
final apiClientProvider = Provider<ApiClient>((ref) {
  Logger.w(' apiClientProvider usado sin override - usando URL por defecto');
  return ApiClient();
});

// ==========================================
// BUSINESS INFO PROVIDER
// ==========================================

final businessInfoProvider = FutureProvider<BusinessInfo>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getBusinessInfo();
  if (response.success && response.data != null) {
    return BusinessInfo.fromJson(response.data!);
  }
  throw Exception(response.error ?? 'Error al obtener información del negocio');
});

// ==========================================
// LATEST POSTS PROVIDER
// ==========================================

final latestPostsProvider = FutureProvider<List<BlogPost>>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getLatestPosts();
  if (response.success && response.data != null) {
    final posts = response.data!['posts'] as List? ?? [];
    return posts.map((p) => BlogPost.fromJson(p)).toList();
  }
  return []; // Devolver lista vacía si falla
});

// ==========================================
// SITE UPDATES PROVIDER
// ==========================================

final siteUpdatesProvider = FutureProvider<List<SiteUpdate>>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getSiteUpdates();
  if (response.success && response.data != null) {
    final updates = response.data!['updates'] as List? ?? [];
    return updates.map((u) => SiteUpdate.fromJson(u)).toList();
  }
  return []; // Devolver lista vacía si falla
});

// ==========================================
// AVAILABILITY PROVIDER
// ==========================================

final availabilityProvider = FutureProvider.family<List<AvailabilityDay>, String>((ref, month) async {
  final api = ref.read(apiClientProvider);
  // month viene en formato YYYY-MM, calculamos from y to
  final from = '$month-01';
  final lastDay = DateTime(int.parse(month.split('-')[0]), int.parse(month.split('-')[1]) + 1, 0).day;
  final to = '$month-${lastDay.toString().padLeft(2, '0')}';
  final response = await api.getAvailability(from: from, to: to);
  if (response.success && response.data != null) {
    // El API devuelve 'availability', no 'days'
    final days = response.data!['availability'] as List? ?? [];
    return days.map((d) => AvailabilityDay.fromJson(d)).toList();
  }
  throw Exception(response.error ?? 'Error al obtener disponibilidad');
});

// ==========================================
// TICKETS PROVIDER
// ==========================================

/// Provider de tickets sin filtro (todos los tickets)
final ticketsProvider = FutureProvider<List<TicketType>>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getTicketTypes();
  if (response.success && response.data != null) {
    final tickets = response.data!['tickets'] as List? ?? [];
    return tickets.map((t) => TicketType.fromJson(t)).toList();
  }
  throw Exception(response.error ?? 'Error al obtener tickets');
});

/// Provider de tickets filtrado por estado del día
/// Parámetro: estado del día (ej: 'abierto', 'disponible', etc.)
final ticketsByStateProvider = FutureProvider.family<List<TicketType>, String?>((ref, state) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getTicketTypes(state: state);
  if (response.success && response.data != null) {
    final tickets = response.data!['tickets'] as List? ?? [];
    return tickets.map((t) => TicketType.fromJson(t)).toList();
  }
  throw Exception(response.error ?? 'Error al obtener tickets');
});

// ==========================================
// CHAT PROVIDER
// ==========================================

class ChatState {
  final List<ChatMessage> messages;
  final String? sessionId;
  final bool isLoading;
  final String? error;

  ChatState({
    this.messages = const [],
    this.sessionId,
    this.isLoading = false,
    this.error,
  });

  ChatState copyWith({
    List<ChatMessage>? messages,
    String? sessionId,
    bool? isLoading,
    String? error,
  }) {
    return ChatState(
      messages: messages ?? this.messages,
      sessionId: sessionId ?? this.sessionId,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class ChatNotifier extends StateNotifier<ChatState> {
  final ApiClient _api;

  ChatNotifier(this._api) : super(ChatState());

  Future<void> initSession() async {
    if (state.sessionId != null) return;

    state = state.copyWith(isLoading: true);
    final response = await _api.createChatSession();

    if (response.success && response.data != null) {
      state = state.copyWith(
        sessionId: response.data!['session_id'],
        isLoading: false,
      );
    } else {
      state = state.copyWith(
        isLoading: false,
        error: response.error ?? 'Error al iniciar sesión de chat',
      );
    }
  }

  Future<void> sendMessage(String content) async {
    if (state.sessionId == null) {
      await initSession();
      if (state.sessionId == null) return;
    }

    // Añadir mensaje del usuario
    final userMessage = ChatMessage.user(content);
    state = state.copyWith(
      messages: [...state.messages, userMessage],
      isLoading: true,
    );

    // Añadir mensaje de carga
    final loadingMessage = ChatMessage.loading();
    state = state.copyWith(
      messages: [...state.messages, loadingMessage],
    );

    // Enviar al servidor
    final response = await _api.sendChatMessage(
      sessionId: state.sessionId!,
      message: content,
    );

    // Quitar mensaje de carga
    final messagesWithoutLoading = state.messages
        .where((m) => !m.isLoading)
        .toList();

    if (response.success && response.data != null) {
      final assistantMessage = ChatMessage.assistant(
        response.data!['response'] ?? '',
      );
      state = state.copyWith(
        messages: [...messagesWithoutLoading, assistantMessage],
        isLoading: false,
      );
    } else {
      final errorMessage = ChatMessage(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        content: response.error ?? 'Error al enviar mensaje',
        isUser: false,
        hasError: true,
      );
      state = state.copyWith(
        messages: [...messagesWithoutLoading, errorMessage],
        isLoading: false,
        error: response.error,
      );
    }
  }

  void clearChat() {
    state = ChatState();
  }
}

final chatProvider = StateNotifierProvider<ChatNotifier, ChatState>((ref) {
  return ChatNotifier(ref.read(apiClientProvider));
});

// ==========================================
// ADMIN CHAT PROVIDER
// ==========================================

class AdminChatNotifier extends StateNotifier<ChatState> {
  final ApiClient _api;

  AdminChatNotifier(this._api) : super(ChatState());

  Future<void> sendMessage(String content) async {
    // Añadir mensaje del usuario
    final userMessage = ChatMessage.user(content);
    state = state.copyWith(
      messages: [...state.messages, userMessage],
      isLoading: true,
    );

    // Añadir mensaje de carga
    final loadingMessage = ChatMessage.loading();
    state = state.copyWith(
      messages: [...state.messages, loadingMessage],
    );

    // Enviar al servidor (endpoint admin)
    final response = await _api.sendAdminChatMessage(message: content);

    // Quitar mensaje de carga
    final messagesWithoutLoading = state.messages
        .where((m) => !m.isLoading)
        .toList();

    if (response.success && response.data != null) {
      final assistantMessage = ChatMessage.assistant(
        response.data!['response'] ?? '',
      );
      state = state.copyWith(
        messages: [...messagesWithoutLoading, assistantMessage],
        isLoading: false,
      );
    } else {
      final errorMessage = ChatMessage(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        content: response.error ?? 'Error al enviar mensaje',
        isUser: false,
        hasError: true,
      );
      state = state.copyWith(
        messages: [...messagesWithoutLoading, errorMessage],
        isLoading: false,
        error: response.error,
      );
    }
  }

  void clearChat() {
    state = ChatState();
  }
}

final adminChatProvider = StateNotifierProvider<AdminChatNotifier, ChatState>((ref) {
  return AdminChatNotifier(ref.read(apiClientProvider));
});

// ==========================================
// CART PROVIDER
// ==========================================

class CartState {
  final String? date;
  final Map<String, int> items; // slug -> quantity
  final Map<String, double> prices; // slug -> price (para cálculo correcto)
  final double total;
  final bool isLoading;
  final String? error;

  CartState({
    this.date,
    this.items = const {},
    this.prices = const {},
    this.total = 0,
    this.isLoading = false,
    this.error,
  });

  CartState copyWith({
    String? date,
    Map<String, int>? items,
    Map<String, double>? prices,
    double? total,
    bool? isLoading,
    String? error,
  }) {
    return CartState(
      date: date ?? this.date,
      items: items ?? this.items,
      prices: prices ?? this.prices,
      total: total ?? this.total,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }

  bool get isEmpty => items.isEmpty;
  int get itemCount => items.values.fold(0, (sum, qty) => sum + qty);
}

class CartNotifier extends StateNotifier<CartState> {
  final ApiClient _api;

  CartNotifier(this._api) : super(CartState());

  void setDate(String date) {
    state = state.copyWith(date: date, items: {}, prices: {}, total: 0);
  }

  void updateItem(String slug, int quantity, double price) {
    final newItems = Map<String, int>.from(state.items);
    final newPrices = Map<String, double>.from(state.prices);

    if (quantity <= 0) {
      newItems.remove(slug);
      newPrices.remove(slug);
    } else {
      newItems[slug] = quantity;
      newPrices[slug] = price; // Guardar precio individual
    }

    // Recalcular total usando precio individual de cada item
    double newTotal = 0;
    newItems.forEach((itemSlug, qty) {
      final itemPrice = newPrices[itemSlug] ?? 0;
      newTotal += qty * itemPrice;
    });

    state = state.copyWith(items: newItems, prices: newPrices, total: newTotal);
  }

  Future<String?> checkout() async {
    if (state.date == null || state.isEmpty) {
      state = state.copyWith(error: 'Carrito vacío o sin fecha');
      return null;
    }

    state = state.copyWith(isLoading: true);

    // Convertir Map<String, int> a List<Map<String, dynamic>>
    final ticketsList = state.items.entries.map((entry) => {
      'slug': entry.key,
      'quantity': entry.value,
    }).toList();

    Logger.d('Checkout iniciado: fecha=${state.date}, tickets=$ticketsList', tag: 'Cart');

    // Obtener URL de checkout móvil (con datos del carrito codificados)
    // Esta URL añade los productos al carrito del navegador cuando se abre
    final response = await _api.getMobileCheckoutUrl(
      date: state.date!,
      tickets: ticketsList,
    );

    Logger.d('getMobileCheckoutUrl response: success=${response.success}, data=${response.data}', tag: 'Cart');

    if (response.success && response.data != null) {
      final checkoutUrl = response.data!['checkout_url'] as String?;
      Logger.d('checkoutUrl: $checkoutUrl', tag: 'Cart');
      state = state.copyWith(isLoading: false);
      return checkoutUrl;
    } else {
      Logger.e('Error: ${response.error}', tag: 'Cart');
      state = state.copyWith(
        isLoading: false,
        error: response.error ?? 'Error al procesar el carrito',
      );
      return null;
    }
  }

  void clear() {
    state = CartState(); // prices se resetea automáticamente a {}
  }
}

final cartProvider = StateNotifierProvider<CartNotifier, CartState>((ref) {
  return CartNotifier(ref.read(apiClientProvider));
});

// ==========================================
// ADMIN PROVIDERS
// ==========================================

/// Provider para el usuario admin actual
final adminUserProvider = StateProvider<User?>((ref) => null);

final dashboardProvider = FutureProvider<DashboardData>((ref) async {
  final api = ref.read(apiClientProvider);
  try {
    Logger.d('Solicitando datos...', tag: 'Dashboard');
    final response = await api.getAdminDashboard();
    Logger.d('Respuesta: success=${response.success}, data=${response.data != null}', tag: 'Dashboard');

    if (response.success && response.data != null) {
      Logger.d('Parseando datos...', tag: 'Dashboard');
      final data = DashboardData.fromJson(response.data!);
      Logger.d('Datos parseados correctamente', tag: 'Dashboard');
      return data;
    }

    Logger.e('Error: ${response.error}', tag: 'Dashboard');
    throw Exception(response.error ?? 'Error al obtener dashboard');
  } catch (e, stack) {
    Logger.e('Excepción: $e', tag: 'Dashboard', error: e);
    Logger.e('Stack: $stack', tag: 'Dashboard');
    rethrow;
  }
});

final adminReservationsProvider = FutureProvider.family<List<Reservation>, Map<String, String?>>((ref, params) async {
  final api = ref.read(apiClientProvider);
  try {
    Logger.d('Solicitando datos: $params', tag: 'Reservations');
    final response = await api.getAdminReservations(
      date: params['date'],
      status: params['status'],
      ticketType: params['ticket_type'],
      search: params['search'],
      from: params['from'],
      to: params['to'],
    );
    Logger.d('Respuesta: success=${response.success}, hasData=${response.data != null}', tag: 'Reservations');

    if (response.success && response.data != null) {
      final reservationsData = response.data!['reservations'];
      if (reservationsData == null) {
        Logger.d('reservations es null, devolviendo lista vacía', tag: 'Reservations');
        return [];
      }
      final reservations = reservationsData as List? ?? [];
      Logger.d('Parseando ${reservations.length} reservas', tag: 'Reservations');
      return reservations.map((r) => Reservation.fromJson(r as Map<String, dynamic>)).toList();
    }
    Logger.e('Error del servidor: ${response.error}', tag: 'Reservations');
    throw Exception(response.error ?? 'Error al obtener reservas');
  } catch (e, stack) {
    Logger.e('Excepción: $e', tag: 'Reservations', error: e);
    Logger.e('Stack: $stack', tag: 'Reservations');
    rethrow;
  }
});

final customersProvider = FutureProvider.family<List<Customer>, String?>((ref, search) async {
  final api = ref.read(apiClientProvider);
  try {
    Logger.d('Solicitando datos: search=$search', tag: 'Customers');
    final response = await api.getCustomers(search: search);
    Logger.d('Respuesta: success=${response.success}, hasData=${response.data != null}', tag: 'Customers');

    if (response.success && response.data != null) {
      final customersData = response.data!['customers'];
      if (customersData == null) {
        Logger.d('customers es null, devolviendo lista vacía', tag: 'Customers');
        return [];
      }
      final customers = customersData as List? ?? [];
      Logger.d('Parseando ${customers.length} clientes', tag: 'Customers');
      return customers.map((c) => Customer.fromJson(c as Map<String, dynamic>)).toList();
    }
    Logger.e('Error del servidor: ${response.error}', tag: 'Customers');
    throw Exception(response.error ?? 'Error al obtener clientes');
  } catch (e, stack) {
    Logger.e('Excepción: $e', tag: 'Customers', error: e);
    Logger.e('Stack: $stack', tag: 'Customers');
    rethrow;
  }
});
