import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/api/api_client.dart';

/// Provider para clientes con búsqueda y rango de fechas
final customersSearchProvider = FutureProvider.family<List<Customer>, Map<String, String?>>((ref, params) async {
  final api = ref.read(apiClientProvider);
  try {
    debugPrint('[Customers] Solicitando datos: search=${params['search']}, from=${params['from']}, to=${params['to']}');
    final response = await api.getCustomers(
      search: params['search'],
      from: params['from'],
      to: params['to'],
    );
    debugPrint('[Customers] Respuesta: success=${response.success}, hasData=${response.data != null}');

    if (response.success && response.data != null) {
      final customersData = response.data!['customers'];
      if (customersData == null) {
        debugPrint('[Customers] customers es null, devolviendo lista vacía');
        return [];
      }
      final customers = customersData as List? ?? [];
      debugPrint('[Customers] Parseando ${customers.length} clientes');
      final result = customers.map((c) => Customer.fromJson(c as Map<String, dynamic>)).toList();
      debugPrint('[Customers] Parseado correctamente: ${result.length} clientes');
      return result;
    }
    debugPrint('[Customers] Error del servidor: ${response.error}');
    throw Exception(response.error ?? 'Error al obtener clientes');
  } catch (e, stack) {
    debugPrint('[Customers] Excepción: $e');
    debugPrint('[Customers] Stack: $stack');
    rethrow;
  }
});

/// Pantalla de gestión de clientes
class CustomersScreen extends ConsumerStatefulWidget {
  const CustomersScreen({super.key});

  @override
  ConsumerState<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends ConsumerState<CustomersScreen> {
  final _searchController = TextEditingController();
  String _searchQuery = '';
  DateTime? _startDate;
  DateTime? _endDate;

  // Cache del mapa de parámetros para evitar recrearlo en cada build
  late Map<String, String?> _cachedParams;

  @override
  void initState() {
    super.initState();
    _updateParams();
  }

  String _formatDateParam(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  void _updateParams() {
    _cachedParams = {
      'search': _searchQuery.isEmpty ? null : _searchQuery,
      'from': _startDate != null ? _formatDateParam(_startDate!) : null,
      'to': _endDate != null ? _formatDateParam(_endDate!) : null,
    };
  }

  Map<String, String?> get _params => _cachedParams;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _selectDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now().subtract(const Duration(days: 730)),
      lastDate: DateTime.now(),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : null,
    );
    if (range != null) {
      _startDate = range.start;
      _endDate = range.end;
      _updateParams();
      setState(() {});
    }
  }

  void _clearDateFilter() {
    _startDate = null;
    _endDate = null;
    _updateParams();
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final customersAsync = ref.watch(customersSearchProvider(_params));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Clientes'),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(customersSearchProvider(_params)),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              children: [
                // Búsqueda
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Buscar por nombre, email o teléfono...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              _searchQuery = '';
                              _updateParams();
                              setState(() {});
                            },
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  onSubmitted: (value) {
                    _searchQuery = value;
                    _updateParams();
                    setState(() {});
                  },
                ),
                const SizedBox(height: 12),

                // Filtro de fechas
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _selectDateRange,
                        icon: const Icon(Icons.date_range, size: 18),
                        label: Text(
                          _startDate != null && _endDate != null
                              ? '${_startDate!.day}/${_startDate!.month} - ${_endDate!.day}/${_endDate!.month}'
                              : 'Filtrar por fecha',
                        ),
                      ),
                    ),
                    if (_startDate != null) ...[
                      const SizedBox(width: 8),
                      IconButton(
                        onPressed: _clearDateFilter,
                        icon: const Icon(Icons.clear),
                        tooltip: 'Quitar filtro',
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),

          // Lista de clientes
          Expanded(
            child: customersAsync.when(
              data: (customers) {
                if (customers.isEmpty) {
                  return const EmptyScreen(
                    message: 'No hay clientes',
                    subtitle: 'No se encontraron clientes con los filtros actuales',
                    icon: Icons.people_outline,
                  );
                }

                return RefreshableList(
                  onRefresh: () async {
                    ref.invalidate(customersSearchProvider(_params));
                  },
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: customers.length,
                    itemBuilder: (context, index) {
                      return _CustomerCard(
                        customer: customers[index],
                        onTap: () => _showCustomerDetails(customers[index]),
                      );
                    },
                  ),
                );
              },
              loading: () => const LoadingScreen(message: 'Cargando clientes...'),
              error: (error, _) => ErrorScreen(
                message: 'Error al cargar clientes',
                onRetry: () => ref.invalidate(customersSearchProvider(_params)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showCustomerDetails(Customer customer) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => _CustomerDetailsSheet(customer: customer),
    );
  }
}

class _CustomerCard extends StatelessWidget {
  final Customer customer;
  final VoidCallback onTap;

  const _CustomerCard({
    required this.customer,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Avatar
              CircleAvatar(
                backgroundColor: colorScheme.primaryContainer,
                child: Text(
                  customer.name.isNotEmpty ? customer.name[0].toUpperCase() : '?',
                  style: TextStyle(
                    color: colorScheme.onPrimaryContainer,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 12),

              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      customer.name,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    if (customer.email.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        customer.email,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: colorScheme.onSurface.withOpacity(0.7),
                            ),
                      ),
                    ],
                  ],
                ),
              ),

              // Reservas totales
              if (customer.totalReservations != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: colorScheme.primaryContainer,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${customer.totalReservations} reservas',
                    style: TextStyle(
                      color: colorScheme.onPrimaryContainer,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CustomerDetailsSheet extends StatelessWidget {
  final Customer customer;

  const _CustomerDetailsSheet({required this.customer});

  Future<void> _launchUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return DraggableScrollableSheet(
      initialChildSize: 0.5,
      minChildSize: 0.3,
      maxChildSize: 0.8,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: SingleChildScrollView(
            controller: scrollController,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: colorScheme.outline.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Avatar y nombre
                Row(
                  children: [
                    CircleAvatar(
                      radius: 32,
                      backgroundColor: colorScheme.primaryContainer,
                      child: Text(
                        customer.name.isNotEmpty ? customer.name[0].toUpperCase() : '?',
                        style: TextStyle(
                          color: colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.bold,
                          fontSize: 24,
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            customer.name,
                            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          if (customer.totalReservations != null)
                            Text(
                              '${customer.totalReservations} reservas',
                              style: TextStyle(color: colorScheme.primary),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // Información de contacto
                if (customer.email.isNotEmpty)
                  _ContactTile(
                    icon: Icons.email,
                    title: 'Email',
                    value: customer.email,
                    onTap: () => _launchUrl('mailto:${customer.email}'),
                  ),

                if (customer.phone.isNotEmpty)
                  _ContactTile(
                    icon: Icons.phone,
                    title: 'Teléfono',
                    value: customer.phone,
                    onTap: () => _launchUrl('tel:${customer.phone}'),
                  ),

                // Historial de reservas
                if (customer.firstReservation != null || customer.lastReservation != null) ...[
                  const Divider(height: 32),
                  Text(
                    'Historial',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 12),

                  if (customer.firstReservation != null)
                    _InfoTile(
                      icon: Icons.event,
                      label: 'Primera reserva',
                      value: customer.firstReservation!,
                    ),

                  if (customer.lastReservation != null)
                    _InfoTile(
                      icon: Icons.event_repeat,
                      label: 'Última reserva',
                      value: customer.lastReservation!,
                    ),
                ],

                const SizedBox(height: 24),

                // Acciones
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if (customer.email.isNotEmpty)
                      OutlinedButton.icon(
                        onPressed: () => _launchUrl('mailto:${customer.email}'),
                        icon: const Icon(Icons.email),
                        label: const Text('Email'),
                      ),
                    if (customer.phone.isNotEmpty)
                      FilledButton.icon(
                        onPressed: () => _launchUrl('tel:${customer.phone}'),
                        icon: const Icon(Icons.phone),
                        label: const Text('Llamar'),
                      ),
                    if (customer.phone.isNotEmpty)
                      FilledButton.icon(
                        onPressed: () {
                          final phone = customer.phone.replaceAll(RegExp(r'[^\d+]'), '');
                          _launchUrl('https://wa.me/$phone');
                        },
                        icon: const Icon(Icons.chat),
                        label: const Text('WhatsApp'),
                        style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFF25D366),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _ContactTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String value;
  final VoidCallback? onTap;

  const _ContactTile({
    required this.icon,
    required this.title,
    required this.value,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon),
      title: Text(title),
      subtitle: Text(value),
      trailing: onTap != null ? const Icon(Icons.chevron_right) : null,
      onTap: onTap,
      contentPadding: EdgeInsets.zero,
    );
  }
}

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoTile({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: 12),
          Text(
            label,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
            ),
          ),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }
}
