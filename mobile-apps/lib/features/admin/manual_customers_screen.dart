import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/flavor_state_widgets.dart';

/// Modelo para cliente manual
class ManualCustomer {
  final int id;
  final String name;
  final String phone;
  final String email;
  final String date;
  final String notes;
  final String status;
  final List<TicketSelection> tickets;
  final String origin;

  ManualCustomer({
    required this.id,
    required this.name,
    this.phone = '',
    this.email = '',
    required this.date,
    this.notes = '',
    this.status = 'activo',
    this.tickets = const [],
    this.origin = 'manual',
  });

  factory ManualCustomer.fromJson(Map<String, dynamic> json) {
    final ticketsList = (json['tickets'] as List?)?.map((t) => TicketSelection(
      slug: t['slug'] ?? '',
      name: t['name'] ?? '',
      quantity: t['quantity'] ?? 1,
    )).toList() ?? [];

    return ManualCustomer(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      email: json['email'] ?? '',
      date: json['date'] ?? '',
      notes: json['notes'] ?? '',
      status: json['status'] ?? 'activo',
      tickets: ticketsList,
      origin: json['origin'] ?? 'manual',
    );
  }

  bool get isManual => origin == 'manual';
  bool get isWooCommerce => origin == 'woocommerce';

  String get originIcon => isManual ? '📞' : '🛒';
  String get originLabel => isManual ? 'Manual' : 'WooCommerce';
}

class TicketSelection {
  final String slug;
  final String name;
  final int quantity;

  TicketSelection({
    required this.slug,
    required this.name,
    required this.quantity,
  });
}

/// Provider para clientes unificados
final unifiedCustomersProvider = FutureProvider.family<List<ManualCustomer>, Map<String, String>>((ref, params) async {
  final api = ref.read(apiClientProvider);
  try {
    debugPrint('[UnifiedCustomers] Solicitando datos: from=${params['from']}, to=${params['to']}');
    final response = await api.getUnifiedCustomers(
      from: params['from'] ?? '',
      to: params['to'] ?? '',
    );
    debugPrint('[UnifiedCustomers] Respuesta: success=${response.success}, data=${response.data != null}');
    if (response.success && response.data != null) {
      final customersData = response.data!['customers'];
      if (customersData == null) {
        debugPrint('[UnifiedCustomers] customers es null, devolviendo lista vacía');
        return [];
      }
      final customers = customersData as List? ?? [];
      debugPrint('[UnifiedCustomers] Parseando ${customers.length} clientes');
      return customers.map((c) => ManualCustomer.fromJson(c as Map<String, dynamic>)).toList();
    }
    if (response.error != null) {
      debugPrint('[UnifiedCustomers] Error del servidor: ${response.error}');
      throw Exception(response.error);
    }
    return [];
  } catch (e, stack) {
    debugPrint('[UnifiedCustomers] Excepción: $e');
    debugPrint('[UnifiedCustomers] Stack: $stack');
    rethrow;
  }
});

/// Pantalla de clientes manuales
class ManualCustomersScreen extends ConsumerStatefulWidget {
  const ManualCustomersScreen({super.key});

  @override
  ConsumerState<ManualCustomersScreen> createState() => _ManualCustomersScreenState();
}

class _ManualCustomersScreenState extends ConsumerState<ManualCustomersScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now().add(const Duration(days: 7));
  String _filterOrigin = 'all'; // 'all', 'manual', 'woocommerce'

  // Cache del mapa de parámetros para evitar recrearlo en cada build
  late Map<String, String> _cachedParams;

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
      'from': _formatDateParam(_startDate),
      'to': _formatDateParam(_endDate),
    };
  }

  Map<String, String> get _params => _cachedParams;

  void _selectDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );
    if (range != null) {
      _startDate = range.start;
      _endDate = range.end;
      _updateParams();
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final customersAsync = ref.watch(unifiedCustomersProvider(_params));
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.clientesSemanaD70cd2),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(unifiedCustomersProvider(_params)),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddCustomerDialog(),
        icon: const Icon(Icons.person_add),
        label: Text(i18n.aAdirD20f65),
      ),
      body: Column(
        children: [
          // Filtros
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: colorScheme.surface,
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
                // Rango de fechas
                InkWell(
                  onTap: _selectDateRange,
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      border: Border.all(color: colorScheme.outline.withOpacity(0.5)),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.date_range, color: colorScheme.primary),
                        const SizedBox(width: 12),
                        Text(
                          _formatRangeLabel(
                            _startDate,
                            _endDate,
                            i18n.localeName,
                          ),
                          style: Theme.of(context).textTheme.bodyLarge,
                        ),
                        const Spacer(),
                        Icon(Icons.chevron_right, color: colorScheme.outline),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),

                // Filtro de origen
                Row(
                  children: [
                    _FilterChip(
                      label: i18n.reservationsOriginAll,
                      selected: _filterOrigin == 'all',
                      onSelected: () => setState(() => _filterOrigin = 'all'),
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label: i18n.reservationsOriginManual,
                      selected: _filterOrigin == 'manual',
                      onSelected: () => setState(() => _filterOrigin = 'manual'),
                      color: Colors.orange,
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label: i18n.reservationsOriginWooCommerce,
                      selected: _filterOrigin == 'woocommerce',
                      onSelected: () => setState(() => _filterOrigin = 'woocommerce'),
                      color: Colors.purple,
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Lista de clientes
          Expanded(
            child: customersAsync.when(
              data: (customers) {
                // Filtrar por origen
                final filtered = _filterOrigin == 'all'
                    ? customers
                    : customers.where((c) => c.origin == _filterOrigin).toList();

                if (filtered.isEmpty) {
                  return EmptyScreen(
                    message: i18n.reservationsNoCustomers,
                    subtitle: _filterOrigin == 'all'
                        ? i18n.reservationsNoCustomersInRange
                        : _filterOrigin == 'manual'
                            ? i18n.reservationsNoCustomersManual
                            : i18n.reservationsNoCustomersWooCommerce,
                    icon: Icons.people_outline,
                  );
                }

                // Agrupar por fecha
                final grouped = <String, List<ManualCustomer>>{};
                for (final c in filtered) {
                  grouped.putIfAbsent(c.date, () => []).add(c);
                }

                final sortedDates = grouped.keys.toList()..sort((a, b) => b.compareTo(a));

                return RefreshableList(
                  onRefresh: () async {
                    ref.invalidate(unifiedCustomersProvider(_params));
                  },
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: sortedDates.length,
                    itemBuilder: (context, index) {
                      final date = sortedDates[index];
                      final dayCustomers = grouped[date]!;
                      return _DaySection(
                        date: date,
                        customers: dayCustomers,
                        onEdit: (c) => _showEditCustomerDialog(c),
                        onDelete: (c) => _deleteCustomer(c),
                        onEditNotes: (c) => _showNotesDialog(c),
                      );
                    },
                  ),
                );
              },
              loading: () => LoadingScreen(message: i18n.loadingCustomers),
              error: (error, _) => ErrorScreen(
                message: i18n.reservationsLoadCustomersError,
                onRetry: () => ref.invalidate(unifiedCustomersProvider(_params)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatRangeLabel(DateTime start, DateTime end, String localeName) {
    final startLabel = DateFormat.Md(localeName).format(start);
    final endLabel = DateFormat.yMd(localeName).format(end);
    return '$startLabel - $endLabel';
  }

  void _showAddCustomerDialog() {
    _showCustomerDialog(null);
  }

  void _showEditCustomerDialog(ManualCustomer customer) {
    if (!customer.isManual) {
      FlavorSnackbar.showInfo(
        context,
        i18n.soloSePuedenEditarClientesManualesE83c06,
      );
      return;
    }
    _showCustomerDialog(customer);
  }

  void _showCustomerDialog(ManualCustomer? customer) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (sheetContext) => _CustomerFormSheet(
        customer: customer,
        onSave: (data) async {
          final api = ref.read(apiClientProvider);
          ApiResponse<Map<String, dynamic>> response;

          if (customer == null) {
            response = await api.createManualCustomer(data);
          } else {
            response = await api.updateManualCustomer(customer.id, data);
          }

          if (response.success) {
            ref.invalidate(unifiedCustomersProvider(_params));
            if (!sheetContext.mounted) return;
            Navigator.pop(sheetContext);
          } else {
            if (!sheetContext.mounted) return;
            FlavorSnackbar.showError(
              sheetContext,
              response.error ?? i18n.customersSaveError,
            );
          }
        },
      ),
    );
  }

  void _showNotesDialog(ManualCustomer customer) {
    final controller = TextEditingController(text: customer.notes);

    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(i18n.customersNotesTitle(customer.name)),
        content: TextField(
          controller: controller,
          decoration: InputDecoration(
            hintText: i18n.escribeLasNotas771c72,
            border: const OutlineInputBorder(),
          ),
          maxLines: 5,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () async {
              final api = ref.read(apiClientProvider);
              await api.saveCustomerNotes(
                origin: customer.origin,
                id: customer.id,
                notes: controller.text,
                date: customer.date,
              );
              ref.invalidate(unifiedCustomersProvider(_params));
              if (!dialogContext.mounted) return;
              Navigator.pop(dialogContext);
            },
            child: Text(i18n.guardarD3270b),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteCustomer(ManualCustomer customer) async {
    if (!customer.isManual) {
      FlavorSnackbar.showInfo(
        context,
        i18n.soloSePuedenEliminarClientesManuale76681f,
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.eliminarCliente63bb53),
        content: Text(i18n.customersDeleteConfirm(customer.name)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: Text(i18n.eliminar5b5c9f),
          ),
        ],
      ),
    );

    if (confirm == true) {
      final api = ref.read(apiClientProvider);
      final response = await api.deleteManualCustomer(customer.id);
      if (response.success) {
        ref.invalidate(unifiedCustomersProvider(_params));
      } else {
        if (mounted) {
          FlavorSnackbar.showError(
            context,
            response.error ?? i18n.customersDeleteError,
          );
        }
      }
    }
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onSelected;
  final Color? color;

  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onSelected,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onSelected(),
      selectedColor: color?.withOpacity(0.2),
      checkmarkColor: color,
    );
  }
}

class _DaySection extends StatelessWidget {
  final String date;
  final List<ManualCustomer> customers;
  final Function(ManualCustomer) onEdit;
  final Function(ManualCustomer) onDelete;
  final Function(ManualCustomer) onEditNotes;

  const _DaySection({
    required this.date,
    required this.customers,
    required this.onEdit,
    required this.onDelete,
    required this.onEditNotes,
  });

  String _formatDate(BuildContext context, String dateStr) {
    try {
      final dt = DateTime.parse(dateStr);
      final i18n = AppLocalizations.of(context);
      final weekday = DateFormat.E(i18n.localeName).format(dt);
      final day = DateFormat.Md(i18n.localeName).format(dt);
      return '$weekday $day';
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final manualCount = customers.where((c) => c.isManual).length;
    final wcCount = customers.where((c) => c.isWooCommerce).length;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header del día
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              Text(
                _formatDate(context, date),
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const Spacer(),
              if (manualCount > 0)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  margin: const EdgeInsets.only(right: 8),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    i18n.customersManualCount(manualCount),
                    style: const TextStyle(fontSize: 12),
                  ),
                ),
              if (wcCount > 0)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    i18n.customersWcCount(wcCount),
                    style: const TextStyle(fontSize: 12),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 8),

        // Lista de clientes del día
        ...customers.map((c) => _CustomerCard(
          customer: c,
          onEdit: () => onEdit(c),
          onDelete: () => onDelete(c),
          onEditNotes: () => onEditNotes(c),
        )),

        const SizedBox(height: 16),
      ],
    );
  }
}

class _CustomerCard extends StatelessWidget {
  final ManualCustomer customer;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onEditNotes;

  const _CustomerCard({
    required this.customer,
    required this.onEdit,
    required this.onDelete,
    required this.onEditNotes,
  });

  Future<void> _launchPhone(String phone) async {
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _launchWhatsApp(String phone) async {
    // Limpiar el número de teléfono (quitar espacios, guiones, etc.)
    String cleanPhone = phone.replaceAll(RegExp(r'[^\d+]'), '');
    // Si no empieza con +, añadir código de España
    if (!cleanPhone.startsWith('+')) {
      if (cleanPhone.startsWith('34')) {
        cleanPhone = '+$cleanPhone';
      } else {
        cleanPhone = '+34$cleanPhone';
      }
    }
    final uri = Uri.parse('https://wa.me/$cleanPhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _launchEmail(String email) async {
    final uri = Uri.parse('mailto:$email');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final colorScheme = Theme.of(context).colorScheme;
    final borderColor = customer.isManual ? Colors.orange : Colors.purple;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: borderColor.withOpacity(0.3), width: 2),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header con origen y nombre
            Row(
              children: [
                Text(customer.originIcon, style: const TextStyle(fontSize: 20)),
                const SizedBox(width: 8),
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
                      if (customer.email.isNotEmpty)
                        Text(
                          customer.email,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurface.withOpacity(0.7),
                          ),
                        ),
                    ],
                  ),
                ),
                // Acciones
                if (customer.isManual) ...[
                  IconButton(
                    onPressed: onEdit,
                    icon: const Icon(Icons.edit, size: 20),
                    tooltip: i18n.editarEf485e,
                  ),
                  IconButton(
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete, size: 20, color: Colors.red),
                    tooltip: i18n.eliminar5b5c9f,
                  ),
                ] else ...[
                  IconButton(
                    onPressed: onEditNotes,
                    icon: const Icon(Icons.note_add, size: 20),
                    tooltip: i18n.notas265b13,
                  ),
                ],
              ],
            ),

            const Divider(height: 16),

            // Botones de contacto
            if (customer.phone.isNotEmpty || customer.email.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  children: [
                    if (customer.phone.isNotEmpty) ...[
                      // Botón llamar
                      _ContactButton(
                        icon: Icons.phone,
                        label: i18n.llamarC9c110,
                        color: Colors.blue,
                        onTap: () => _launchPhone(customer.phone),
                      ),
                      const SizedBox(width: 8),
                      // Botón WhatsApp
                      _ContactButton(
                        icon: Icons.chat,
                        label: i18n.whatsapp8b777e,
                        color: const Color(0xFF25D366),
                        onTap: () => _launchWhatsApp(customer.phone),
                      ),
                      const SizedBox(width: 8),
                    ],
                    if (customer.email.isNotEmpty)
                      _ContactButton(
                        icon: Icons.email,
                        label: i18n.emailCe8ae9,
                        color: Colors.orange,
                        onTap: () => _launchEmail(customer.email),
                      ),
                  ],
                ),
              ),

            // Tickets
            if (customer.tickets.isNotEmpty)
              Wrap(
                spacing: 4,
                runSpacing: 4,
                children: customer.tickets.map((t) => Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: colorScheme.secondaryContainer,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${t.name} x${t.quantity}',
                    style: TextStyle(
                      fontSize: 12,
                      color: colorScheme.onSecondaryContainer,
                    ),
                  ),
                )).toList(),
              ),

            // Notas
            if (customer.notes.isNotEmpty) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.amber.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.sticky_note_2, size: 16, color: Colors.amber),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        customer.notes,
                        style: Theme.of(context).textTheme.bodySmall,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _CustomerFormSheet extends ConsumerStatefulWidget {
  final ManualCustomer? customer;
  final Function(Map<String, dynamic>) onSave;

  const _CustomerFormSheet({
    this.customer,
    required this.onSave,
  });

  @override
  ConsumerState<_CustomerFormSheet> createState() => _CustomerFormSheetState();
}

class _CustomerFormSheetState extends ConsumerState<_CustomerFormSheet> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _notesController = TextEditingController();
  DateTime _selectedDate = DateTime.now();
  final Map<String, int> _ticketQuantities = {};
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    if (widget.customer != null) {
      _nameController.text = widget.customer!.name;
      _phoneController.text = widget.customer!.phone;
      _emailController.text = widget.customer!.email;
      _notesController.text = widget.customer!.notes;
      _selectedDate = DateTime.tryParse(widget.customer!.date) ?? DateTime.now();
      for (final t in widget.customer!.tickets) {
        _ticketQuantities[t.slug] = t.quantity;
      }
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ticketsAsync = ref.watch(ticketsProvider);

    return DraggableScrollableSheet(
      initialChildSize: 0.9,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: ListView(
              controller: scrollController,
              children: [
                // Header
                Row(
                  children: [
                    Text(
                      widget.customer == null
                          ? i18n.customersAddTitle
                          : i18n.customersEditTitle,
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // Nombre
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: i18n.nombre45576f,
                    prefixIcon: const Icon(Icons.person),
                  ),
                  validator: (v) => v?.isEmpty == true ? i18n.commonRequired : null,
                ),
                const SizedBox(height: 16),

                // Teléfono
                TextFormField(
                  controller: _phoneController,
                  decoration: InputDecoration(
                    labelText: i18n.telFonoD091ea,
                    prefixIcon: const Icon(Icons.phone),
                  ),
                  keyboardType: TextInputType.phone,
                ),
                const SizedBox(height: 16),

                // Email
                TextFormField(
                  controller: _emailController,
                  decoration: InputDecoration(
                    labelText: i18n.emailCe8ae9,
                    prefixIcon: const Icon(Icons.email),
                  ),
                  keyboardType: TextInputType.emailAddress,
                ),
                const SizedBox(height: 16),

                // Fecha
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.calendar_today),
                  title: Text(i18n.fechaDelEvento63f6fa),
                  subtitle: Text(
                    i18n.commonDateDmy(
                      _selectedDate.day,
                      _selectedDate.month,
                      _selectedDate.year,
                    ),
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime.now().subtract(const Duration(days: 30)),
                      lastDate: DateTime.now().add(const Duration(days: 365)),
                    );
                    if (date != null) {
                      setState(() => _selectedDate = date);
                    }
                  },
                ),
                const Divider(),

                // Tickets
                Text(
                  i18n.ticketsLabel,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                ticketsAsync.when(
                  data: (tickets) => Column(
                    children: tickets.map((t) => _TicketRow(
                      ticket: t,
                      quantity: _ticketQuantities[t.slug] ?? 0,
                      onQuantityChanged: (qty) {
                        setState(() {
                          if (qty > 0) {
                            _ticketQuantities[t.slug] = qty;
                          } else {
                            _ticketQuantities.remove(t.slug);
                          }
                        });
                      },
                    )).toList(),
                  ),
                  loading: () => const FlavorLoadingState(),
                  error: (_, __) => Text(i18n.errorAlCargarTicketsAb3af0),
                ),
                const SizedBox(height: 16),

                // Notas
                TextFormField(
                  controller: _notesController,
                  decoration: InputDecoration(
                    labelText: i18n.notas265b13,
                    prefixIcon: const Icon(Icons.note),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),

                // Botón guardar
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: _isLoading ? null : _save,
                    child: _isLoading
                        ? const FlavorInlineSpinner()
                        : Text(i18n.guardarD3270b),
                  ),
                ),

                // Espacio extra para el teclado
                SizedBox(height: MediaQuery.of(context).viewInsets.bottom + 24),
              ],
            ),
          ),
        );
      },
    );
  }

  void _save() {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final data = {
      'name': _nameController.text.trim(),
      'phone': _phoneController.text.trim(),
      'email': _emailController.text.trim(),
      'date': '${_selectedDate.year}-${_selectedDate.month.toString().padLeft(2, '0')}-${_selectedDate.day.toString().padLeft(2, '0')}',
      'notes': _notesController.text.trim(),
      'tickets': _ticketQuantities.entries
          .where((e) => e.value > 0)
          .map((e) => {'slug': e.key, 'quantity': e.value})
          .toList(),
    };

    widget.onSave(data);
  }
}

class _TicketRow extends StatelessWidget {
  final TicketType ticket;
  final int quantity;
  final Function(int) onQuantityChanged;

  const _TicketRow({
    required this.ticket,
    required this.quantity,
    required this.onQuantityChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(
            child: Text(ticket.name),
          ),
          IconButton(
            onPressed: quantity > 0 ? () => onQuantityChanged(quantity - 1) : null,
            icon: const Icon(Icons.remove_circle_outline),
          ),
          SizedBox(
            width: 40,
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          IconButton(
            onPressed: () => onQuantityChanged(quantity + 1),
            icon: const Icon(Icons.add_circle_outline),
          ),
        ],
      ),
    );
  }
}

/// Botón de contacto compacto
class _ContactButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _ContactButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
