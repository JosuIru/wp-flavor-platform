import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../core/widgets/common_widgets.dart';
import 'manual_customers_screen.dart' show ManualCustomer, unifiedCustomersProvider;

/// Plantillas de mensajes predefinidas
class MessageTemplate {
  final String id;
  final String name;
  final String template;
  final IconData icon;

  const MessageTemplate({
    required this.id,
    required this.name,
    required this.template,
    required this.icon,
  });

  String format(Reservation reservation) {
    return template
        .replaceAll('{nombre}', reservation.customer?.name ?? 'Cliente')
        .replaceAll('{fecha}', reservation.date)
        .replaceAll('{ticket}', reservation.ticketName)
        .replaceAll('{codigo}', reservation.ticketCode);
  }
}

const List<MessageTemplate> messageTemplates = [
  MessageTemplate(
    id: 'confirmacion',
    name: 'Confirmación',
    template: 'Hola {nombre}! Tu reserva para {ticket} el día {fecha} está confirmada. Código: {codigo}. ¡Te esperamos!',
    icon: Icons.check_circle,
  ),
  MessageTemplate(
    id: 'recordatorio',
    name: 'Recordatorio',
    template: 'Hola {nombre}! Te recordamos que tienes una reserva para {ticket} el día {fecha}. Código: {codigo}. ¡Te esperamos!',
    icon: Icons.alarm,
  ),
  MessageTemplate(
    id: 'cambio',
    name: 'Cambio/Aviso',
    template: 'Hola {nombre}! Te informamos sobre tu reserva de {ticket} del día {fecha}. ',
    icon: Icons.info,
  ),
  MessageTemplate(
    id: 'agradecimiento',
    name: 'Agradecimiento',
    template: 'Hola {nombre}! Gracias por visitarnos. Esperamos que hayas disfrutado de {ticket}. ¡Esperamos verte pronto!',
    icon: Icons.favorite,
  ),
];

/// Pantalla de gestión de reservas para admin con dos vistas
class AdminReservationsScreen extends ConsumerStatefulWidget {
  const AdminReservationsScreen({super.key});

  @override
  ConsumerState<AdminReservationsScreen> createState() =>
      _AdminReservationsScreenState();
}

class _AdminReservationsScreenState
    extends ConsumerState<AdminReservationsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Modo de filtro: fecha única o rango
  bool _useDateRange = false;
  DateTime _selectedDate = DateTime.now();
  DateTime? _startDate;
  DateTime? _endDate;
  String? _selectedStatus;
  String? _selectedTicketType;
  String _searchQuery = '';
  final _searchController = TextEditingController();

  // Modo selección múltiple
  bool _isSelectionMode = false;
  final Set<int> _selectedIds = {};

  // Cache del Map de parámetros para evitar recreación en cada build
  Map<String, String?>? _cachedParams;
  String? _lastDate;
  String? _lastFrom;
  String? _lastTo;
  String? _lastStatus;
  String? _lastTicketType;
  String? _lastSearch;

  String _formatDateParam(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  Map<String, String?> get _params {
    final currentDate = _useDateRange ? null : _formatDateParam(_selectedDate);
    final currentFrom = _useDateRange && _startDate != null ? _formatDateParam(_startDate!) : null;
    final currentTo = _useDateRange && _endDate != null ? _formatDateParam(_endDate!) : null;
    final currentSearch = _searchQuery.isEmpty ? null : _searchQuery;

    // Solo recrear el Map si los valores cambiaron
    if (_cachedParams == null ||
        _lastDate != currentDate ||
        _lastFrom != currentFrom ||
        _lastTo != currentTo ||
        _lastStatus != _selectedStatus ||
        _lastTicketType != _selectedTicketType ||
        _lastSearch != currentSearch) {
      _lastDate = currentDate;
      _lastFrom = currentFrom;
      _lastTo = currentTo;
      _lastStatus = _selectedStatus;
      _lastTicketType = _selectedTicketType;
      _lastSearch = currentSearch;
      _cachedParams = {
        'date': currentDate,
        'from': currentFrom,
        'to': currentTo,
        'status': _selectedStatus,
        'ticket_type': _selectedTicketType,
        'search': currentSearch,
      };
    }
    return _cachedParams!;
  }

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _selectDate() async {
    final date = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (date != null) {
      setState(() => _selectedDate = date);
    }
  }

  void _selectDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : DateTimeRange(
              start: DateTime.now().subtract(const Duration(days: 7)),
              end: DateTime.now(),
            ),
    );
    if (range != null) {
      setState(() {
        _startDate = range.start;
        _endDate = range.end;
      });
    }
  }

  void _toggleSelectionMode() {
    setState(() {
      _isSelectionMode = !_isSelectionMode;
      if (!_isSelectionMode) {
        _selectedIds.clear();
      }
    });
  }

  void _toggleSelection(int id) {
    setState(() {
      if (_selectedIds.contains(id)) {
        _selectedIds.remove(id);
      } else {
        _selectedIds.add(id);
      }
    });
  }

  void _selectAll(List<Reservation> reservations) {
    setState(() {
      final pendingIds = reservations
          .where((r) => r.isPending)
          .map((r) => r.id)
          .toSet();
      if (_selectedIds.containsAll(pendingIds)) {
        _selectedIds.clear();
      } else {
        _selectedIds.addAll(pendingIds);
      }
    });
  }

  Future<void> _bulkCheckin() async {
    if (_selectedIds.isEmpty) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Check-in masivo'),
        content: Text('¿Realizar check-in de ${_selectedIds.length} reservas?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final api = ref.read(apiClientProvider);
    int successCount = 0;
    int errorCount = 0;

    for (final id in _selectedIds) {
      final response = await api.doCheckin(id);
      if (response.success) {
        successCount++;
      } else {
        errorCount++;
      }
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Check-in: $successCount exitosos, $errorCount errores'),
          backgroundColor: errorCount == 0 ? Colors.green : Colors.orange,
        ),
      );
      setState(() {
        _isSelectionMode = false;
        _selectedIds.clear();
      });
      ref.invalidate(adminReservationsProvider(_params));
    }
  }

  Future<void> _bulkCancel() async {
    if (_selectedIds.isEmpty) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelación masiva'),
        content: Text('¿Cancelar ${_selectedIds.length} reservas? Esta acción no se puede deshacer.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Sí, cancelar'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final api = ref.read(apiClientProvider);
    int successCount = 0;
    int errorCount = 0;

    for (final id in _selectedIds) {
      final response = await api.cancelReservation(id);
      if (response.success) {
        successCount++;
      } else {
        errorCount++;
      }
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Canceladas: $successCount exitosas, $errorCount errores'),
          backgroundColor: errorCount == 0 ? Colors.orange : Colors.red,
        ),
      );
      setState(() {
        _isSelectionMode = false;
        _selectedIds.clear();
      });
      ref.invalidate(adminReservationsProvider(_params));
    }
  }

  Future<void> _bulkMessage(List<Reservation> allReservations) async {
    if (_selectedIds.isEmpty) return;

    final selectedReservations = allReservations
        .where((r) => _selectedIds.contains(r.id))
        .toList();

    // Filtrar solo los que tienen contacto
    final withPhone = selectedReservations
        .where((r) => r.customer?.phone.isNotEmpty == true)
        .toList();
    final withEmail = selectedReservations
        .where((r) => r.customer?.email.isNotEmpty == true)
        .toList();

    if (withPhone.isEmpty && withEmail.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Ninguna reserva seleccionada tiene datos de contacto'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => _BulkMessageSheet(
        reservations: selectedReservations,
        withPhone: withPhone,
        withEmail: withEmail,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final reservationsAsync = ref.watch(adminReservationsProvider(_params));

    return Scaffold(
      appBar: AppBar(
        title: _isSelectionMode
            ? Text('${_selectedIds.length} seleccionadas')
            : const Text('Reservas'),
        leading: _isSelectionMode
            ? IconButton(
                onPressed: _toggleSelectionMode,
                icon: const Icon(Icons.close),
              )
            : null,
        actions: _isSelectionMode
            ? [
                IconButton(
                  onPressed: _selectedIds.isNotEmpty ? _bulkCheckin : null,
                  icon: const Icon(Icons.check_circle),
                  tooltip: 'Check-in masivo',
                ),
                IconButton(
                  onPressed: _selectedIds.isNotEmpty ? _bulkCancel : null,
                  icon: const Icon(Icons.cancel),
                  tooltip: 'Cancelar masivo',
                ),
                Builder(
                  builder: (context) {
                    final reservationsAsync = ref.watch(adminReservationsProvider(_params));
                    return reservationsAsync.maybeWhen(
                      data: (reservations) => IconButton(
                        onPressed: _selectedIds.isNotEmpty
                            ? () => _bulkMessage(reservations)
                            : null,
                        icon: const Icon(Icons.message),
                        tooltip: 'Enviar mensaje',
                      ),
                      orElse: () => const SizedBox.shrink(),
                    );
                  },
                ),
              ]
            : [
                IconButton(
                  onPressed: _toggleSelectionMode,
                  icon: const Icon(Icons.checklist),
                  tooltip: 'Selección múltiple',
                ),
                IconButton(
                  onPressed: () {
                    ref.invalidate(adminReservationsProvider(_params));
                  },
                  icon: const Icon(Icons.refresh),
                ),
              ],
        bottom: _isSelectionMode ? null : TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.confirmation_number), text: 'Reservas'),
            Tab(icon: Icon(Icons.people), text: 'Clientes'),
          ],
        ),
      ),
      body: _isSelectionMode
          ? _buildReservationsView(reservationsAsync)
          : TabBarView(
              controller: _tabController,
              children: [
                _buildReservationsView(reservationsAsync),
                const _WeeklyCustomersView(),
              ],
            ),
    );
  }

  Widget _buildReservationsView(AsyncValue<List<Reservation>> reservationsAsync) {
    return Column(
        children: [
          // Filtros
          _FiltersSection(
            useDateRange: _useDateRange,
            selectedDate: _selectedDate,
            startDate: _startDate,
            endDate: _endDate,
            selectedStatus: _selectedStatus,
            selectedTicketType: _selectedTicketType,
            searchController: _searchController,
            onDateModeTap: () {
              setState(() {
                _useDateRange = !_useDateRange;
                if (_useDateRange && _startDate == null) {
                  _startDate = DateTime.now().subtract(const Duration(days: 7));
                  _endDate = DateTime.now();
                }
              });
            },
            onDateTap: _selectDate,
            onDateRangeTap: _selectDateRange,
            onStatusChanged: (status) {
              setState(() => _selectedStatus = status);
            },
            onTicketTypeChanged: (ticketType) {
              setState(() => _selectedTicketType = ticketType);
            },
            onSearch: (query) {
              setState(() => _searchQuery = query);
            },
          ),

          // Lista de reservas
          Expanded(
            child: reservationsAsync.when(
              data: (reservations) {
                if (reservations.isEmpty) {
                  return const EmptyScreen(
                    message: 'No hay reservas',
                    subtitle: 'No se encontraron reservas para los filtros seleccionados',
                    icon: Icons.event_busy,
                  );
                }

                return RefreshableList(
                  onRefresh: () async {
                    ref.invalidate(adminReservationsProvider(_params));
                  },
                  child: Column(
                    children: [
                      // Botón seleccionar todos (solo en modo selección)
                      if (_isSelectionMode)
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          child: Row(
                            children: [
                              TextButton.icon(
                                onPressed: () => _selectAll(reservations),
                                icon: const Icon(Icons.select_all),
                                label: const Text('Seleccionar pendientes'),
                              ),
                              const Spacer(),
                              Text(
                                '${reservations.where((r) => r.isPending).length} pendientes',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ),
                      Expanded(
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: reservations.length,
                          itemBuilder: (context, index) {
                            final reservation = reservations[index];
                            return _ReservationCard(
                              reservation: reservation,
                              isSelectionMode: _isSelectionMode,
                              isSelected: _selectedIds.contains(reservation.id),
                              onTap: _isSelectionMode
                                  ? () => _toggleSelection(reservation.id)
                                  : () => _showReservationDetails(reservation),
                              onLongPress: !_isSelectionMode
                                  ? () {
                                      _toggleSelectionMode();
                                      _toggleSelection(reservation.id);
                                    }
                                  : null,
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                );
              },
              loading: () => const LoadingScreen(message: 'Cargando reservas...'),
              error: (error, stack) => ErrorScreen(
                message: 'Error al cargar reservas',
                onRetry: () => ref.invalidate(adminReservationsProvider(_params)),
              ),
            ),
          ),
        ],
      );
  }

  void _showReservationDetails(Reservation reservation) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => _ReservationDetailsSheet(
        reservation: reservation,
        onStatusChanged: () {
          // Refrescar la lista de reservas
          ref.invalidate(adminReservationsProvider(_params));
        },
      ),
    );
  }
}

class _FiltersSection extends ConsumerWidget {
  final bool useDateRange;
  final DateTime selectedDate;
  final DateTime? startDate;
  final DateTime? endDate;
  final String? selectedStatus;
  final String? selectedTicketType;
  final TextEditingController searchController;
  final VoidCallback onDateModeTap;
  final VoidCallback onDateTap;
  final VoidCallback onDateRangeTap;
  final Function(String?) onStatusChanged;
  final Function(String?) onTicketTypeChanged;
  final Function(String) onSearch;

  const _FiltersSection({
    required this.useDateRange,
    required this.selectedDate,
    required this.startDate,
    required this.endDate,
    required this.selectedStatus,
    required this.selectedTicketType,
    required this.searchController,
    required this.onDateModeTap,
    required this.onDateTap,
    required this.onDateRangeTap,
    required this.onStatusChanged,
    required this.onTicketTypeChanged,
    required this.onSearch,
  });

  String _getMonthKey(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}';
  }

  String _getDateKey(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Obtener disponibilidad del mes seleccionado para saber el estado del día
    final monthKey = _getMonthKey(selectedDate);
    final availabilityAsync = ref.watch(availabilityProvider(monthKey));

    // Determinar el estado del día seleccionado
    String? dayState;
    if (!useDateRange) {
      availabilityAsync.whenData((days) {
        final dateKey = _getDateKey(selectedDate);
        for (final day in days) {
          if (day.date == dateKey) {
            dayState = day.state;
            break;
          }
        }
      });
    }

    // Obtener tickets filtrados por estado (o todos si es rango de fechas)
    final ticketsAsync = useDateRange
        ? ref.watch(ticketsProvider)
        : ref.watch(ticketsByStateProvider(dayState));

    return Container(
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
            controller: searchController,
            decoration: InputDecoration(
              hintText: 'Buscar por nombre, email o código...',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        searchController.clear();
                        onSearch('');
                      },
                    )
                  : null,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16),
            ),
            onSubmitted: onSearch,
          ),
          const SizedBox(height: 12),

          // Toggle fecha única / rango + selector de fecha
          Row(
            children: [
              // Toggle
              SegmentedButton<bool>(
                segments: const [
                  ButtonSegment(value: false, label: Text('Día')),
                  ButtonSegment(value: true, label: Text('Rango')),
                ],
                selected: {useDateRange},
                onSelectionChanged: (_) => onDateModeTap(),
                style: const ButtonStyle(
                  visualDensity: VisualDensity.compact,
                ),
              ),
              const SizedBox(width: 12),
              // Fecha/Rango
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: useDateRange ? onDateRangeTap : onDateTap,
                  icon: Icon(
                    useDateRange ? Icons.date_range : Icons.calendar_today,
                    size: 18,
                  ),
                  label: Text(
                    useDateRange
                        ? _formatDateRange(startDate, endDate)
                        : _formatDate(selectedDate),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Estado y tipo de ticket
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String?>(
                  value: selectedStatus,
                  decoration: InputDecoration(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  hint: const Text('Estado'),
                  items: const [
                    DropdownMenuItem(value: null, child: Text('Todos')),
                    DropdownMenuItem(value: 'pendiente', child: Text('Pendiente')),
                    DropdownMenuItem(value: 'usado', child: Text('Usado')),
                    DropdownMenuItem(value: 'cancelado', child: Text('Cancelado')),
                  ],
                  onChanged: onStatusChanged,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ticketsAsync.when(
                  data: (tickets) {
                    // Si el ticket seleccionado ya no está en la lista, limpiarlo
                    final validTicket = selectedTicketType == null ||
                        tickets.any((t) => t.slug == selectedTicketType);

                    if (!validTicket) {
                      // Limpiar selección en el siguiente frame
                      WidgetsBinding.instance.addPostFrameCallback((_) {
                        onTicketTypeChanged(null);
                      });
                    }

                    return DropdownButtonFormField<String?>(
                      value: validTicket ? selectedTicketType : null,
                      decoration: InputDecoration(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      hint: const Text('Ticket'),
                      isExpanded: true,
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Todos')),
                        ...tickets.map((t) => DropdownMenuItem(
                              value: t.slug,
                              child: Text(
                                t.name,
                                overflow: TextOverflow.ellipsis,
                              ),
                            )),
                      ],
                      onChanged: onTicketTypeChanged,
                    );
                  },
                  loading: () => const SizedBox(
                    height: 48,
                    child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                  ),
                  error: (_, __) => const SizedBox.shrink(),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  String _formatDateRange(DateTime? start, DateTime? end) {
    if (start == null || end == null) return 'Seleccionar';
    return '${start.day}/${start.month} - ${end.day}/${end.month}';
  }
}

class _ReservationCard extends StatelessWidget {
  final Reservation reservation;
  final VoidCallback onTap;
  final VoidCallback? onLongPress;
  final bool isSelectionMode;
  final bool isSelected;

  const _ReservationCard({
    required this.reservation,
    required this.onTap,
    this.onLongPress,
    this.isSelectionMode = false,
    this.isSelected = false,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isSelected ? colorScheme.primaryContainer.withOpacity(0.3) : null,
      child: InkWell(
        onTap: onTap,
        onLongPress: onLongPress,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Checkbox o estado indicator
              if (isSelectionMode)
                Checkbox(
                  value: isSelected,
                  onChanged: reservation.isPending ? (_) => onTap() : null,
                )
              else
                Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(
                    color: _getStatusColor(),
                    shape: BoxShape.circle,
                  ),
                ),
              const SizedBox(width: 12),

              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      reservation.customer?.name ?? 'Sin nombre',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      reservation.ticketName,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withOpacity(0.7),
                          ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.confirmation_number,
                          size: 14,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          reservation.ticketCode,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                fontFamily: 'monospace',
                              ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Estado
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: _getStatusColor().withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  reservation.statusDisplay,
                  style: TextStyle(
                    color: _getStatusColor(),
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getStatusColor() {
    switch (reservation.status) {
      case 'pendiente':
        return Colors.orange;
      case 'usado':
        return Colors.green;
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

class _ReservationDetailsSheet extends ConsumerStatefulWidget {
  final Reservation reservation;
  final VoidCallback? onStatusChanged;

  const _ReservationDetailsSheet({
    required this.reservation,
    this.onStatusChanged,
  });

  @override
  ConsumerState<_ReservationDetailsSheet> createState() => _ReservationDetailsSheetState();
}

class _ReservationDetailsSheetState extends ConsumerState<_ReservationDetailsSheet> {
  bool _isLoading = false;

  Future<void> _launchUrl(String urlString) async {
    final uri = Uri.parse(urlString);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('No se puede abrir: $urlString'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _doCheckin() async {
    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.doCheckin(widget.reservation.id);

    setState(() => _isLoading = false);

    if (response.success && mounted) {
      Navigator.pop(context);
      widget.onStatusChanged?.call();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Check-in realizado correctamente'),
          backgroundColor: Colors.green,
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.error ?? 'Error al realizar check-in'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _cancelReservation() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar reserva'),
        content: const Text('¿Seguro que quieres cancelar esta reserva? Esta acción no se puede deshacer.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Sí, cancelar'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.cancelReservation(widget.reservation.id);

    setState(() => _isLoading = false);

    if (response.success && mounted) {
      Navigator.pop(context);
      widget.onStatusChanged?.call();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Reserva cancelada'),
          backgroundColor: Colors.orange,
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.error ?? 'Error al cancelar'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
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
                      color: Theme.of(context).colorScheme.outline.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Título
                Text(
                  'Detalles de la reserva',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 24),

                // Código de ticket
                _DetailRow(
                  icon: Icons.confirmation_number,
                  label: 'Código',
                  value: widget.reservation.ticketCode,
                  isCode: true,
                ),

                // Tipo de ticket
                _DetailRow(
                  icon: Icons.category,
                  label: 'Ticket',
                  value: widget.reservation.ticketName,
                ),

                // Fecha
                _DetailRow(
                  icon: Icons.calendar_today,
                  label: 'Fecha',
                  value: widget.reservation.date,
                ),

                // Estado
                _DetailRow(
                  icon: Icons.flag,
                  label: 'Estado',
                  value: widget.reservation.statusDisplay,
                  valueColor: _getStatusColor(),
                ),

                // Cliente
                if (widget.reservation.customer != null) ...[
                  const Divider(height: 32),
                  Text(
                    'Cliente',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 12),
                  _DetailRow(
                    icon: Icons.person,
                    label: 'Nombre',
                    value: widget.reservation.customer!.name,
                  ),
                  if (widget.reservation.customer!.email.isNotEmpty)
                    _DetailRow(
                      icon: Icons.email,
                      label: 'Email',
                      value: widget.reservation.customer!.email,
                    ),
                  if (widget.reservation.customer!.phone.isNotEmpty)
                    _DetailRow(
                      icon: Icons.phone,
                      label: 'Teléfono',
                      value: widget.reservation.customer!.phone,
                    ),
                ],

                // Check-in
                if (widget.reservation.checkin != null) ...[
                  const Divider(height: 32),
                  _DetailRow(
                    icon: Icons.check_circle,
                    label: 'Check-in',
                    value: widget.reservation.checkin!,
                    valueColor: Colors.green,
                  ),
                ],

                const SizedBox(height: 24),

                // Botones de contacto
                if (widget.reservation.customer != null &&
                    (widget.reservation.customer!.phone.isNotEmpty ||
                        widget.reservation.customer!.email.isNotEmpty)) ...[
                  const Divider(height: 32),
                  Text(
                    'Contactar cliente',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      if (widget.reservation.customer!.phone.isNotEmpty) ...[
                        FilledButton.icon(
                          onPressed: () => _launchUrl('tel:${widget.reservation.customer!.phone}'),
                          icon: const Icon(Icons.phone),
                          label: const Text('Llamar'),
                        ),
                        OutlinedButton.icon(
                          onPressed: () => _showMessageDialog(
                            context,
                            widget.reservation,
                            isWhatsApp: true,
                          ),
                          icon: const Icon(Icons.chat),
                          label: const Text('WhatsApp'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF25D366),
                          ),
                        ),
                      ],
                      if (widget.reservation.customer!.email.isNotEmpty)
                        OutlinedButton.icon(
                          onPressed: () => _showMessageDialog(
                            context,
                            widget.reservation,
                            isWhatsApp: false,
                          ),
                          icon: const Icon(Icons.email),
                          label: const Text('Email'),
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                ],

                // Acciones
                if (widget.reservation.isPending)
                  _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _cancelReservation,
                          icon: const Icon(Icons.cancel),
                          label: const Text('Cancelar'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: _doCheckin,
                          icon: const Icon(Icons.check),
                          label: const Text('Check-in'),
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

  Color _getStatusColor() {
    switch (widget.reservation.status) {
      case 'pendiente':
        return Colors.orange;
      case 'usado':
        return Colors.green;
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  void _showMessageDialog(BuildContext context, Reservation reservation, {required bool isWhatsApp}) {
    showDialog(
      context: context,
      builder: (context) => _MessageDialog(
        reservation: reservation,
        isWhatsApp: isWhatsApp,
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;
  final bool isCode;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    this.valueColor,
    this.isCode = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(
            icon,
            size: 20,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(width: 12),
          Text(
            label,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                ),
          ),
          const Spacer(),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: valueColor,
                  fontFamily: isCode ? 'monospace' : null,
                ),
          ),
        ],
      ),
    );
  }
}

/// Diálogo para enviar mensaje individual
class _MessageDialog extends StatefulWidget {
  final Reservation reservation;
  final bool isWhatsApp;

  const _MessageDialog({
    required this.reservation,
    required this.isWhatsApp,
  });

  @override
  State<_MessageDialog> createState() => _MessageDialogState();
}

class _MessageDialogState extends State<_MessageDialog> {
  final _messageController = TextEditingController();
  MessageTemplate? _selectedTemplate;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  void _applyTemplate(MessageTemplate template) {
    setState(() {
      _selectedTemplate = template;
      _messageController.text = template.format(widget.reservation);
    });
  }

  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    final encodedMessage = Uri.encodeComponent(message);

    if (widget.isWhatsApp) {
      final phone = widget.reservation.customer!.phone.replaceAll(RegExp(r'[^0-9]'), '');
      final url = 'https://wa.me/$phone?text=$encodedMessage';
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (mounted) Navigator.pop(context);
      }
    } else {
      final email = widget.reservation.customer!.email;
      final subject = Uri.encodeComponent('Reserva ${widget.reservation.ticketCode}');
      final url = 'mailto:$email?subject=$subject&body=$encodedMessage';
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri);
        if (mounted) Navigator.pop(context);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  widget.isWhatsApp ? Icons.chat : Icons.email,
                  color: widget.isWhatsApp ? Colors.green : Colors.blue,
                ),
                const SizedBox(width: 12),
                Text(
                  widget.isWhatsApp ? 'Enviar WhatsApp' : 'Enviar Email',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'A: ${widget.reservation.customer?.name ?? "Cliente"}',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 16),

            // Plantillas
            Text(
              'Plantillas rápidas:',
              style: Theme.of(context).textTheme.labelMedium,
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: messageTemplates.map((template) {
                final isSelected = _selectedTemplate?.id == template.id;
                return FilterChip(
                  selected: isSelected,
                  label: Text(template.name),
                  avatar: Icon(template.icon, size: 16),
                  onSelected: (_) => _applyTemplate(template),
                );
              }).toList(),
            ),
            const SizedBox(height: 16),

            // Campo de mensaje
            TextField(
              controller: _messageController,
              maxLines: 5,
              decoration: InputDecoration(
                hintText: 'Escribe tu mensaje...',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Botones
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancelar'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: _sendMessage,
                    icon: Icon(widget.isWhatsApp ? Icons.send : Icons.email),
                    label: const Text('Enviar'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Sheet para mensajes masivos
class _BulkMessageSheet extends StatefulWidget {
  final List<Reservation> reservations;
  final List<Reservation> withPhone;
  final List<Reservation> withEmail;

  const _BulkMessageSheet({
    required this.reservations,
    required this.withPhone,
    required this.withEmail,
  });

  @override
  State<_BulkMessageSheet> createState() => _BulkMessageSheetState();
}

class _BulkMessageSheetState extends State<_BulkMessageSheet> {
  final _messageController = TextEditingController();
  MessageTemplate? _selectedTemplate;
  bool _sendWhatsApp = true;
  bool _isSending = false;
  int _sentCount = 0;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  void _applyTemplate(MessageTemplate template) {
    setState(() {
      _selectedTemplate = template;
      _messageController.text = template.template;
    });
  }

  Future<void> _sendBulkMessages() async {
    if (_messageController.text.trim().isEmpty) return;

    final targetList = _sendWhatsApp ? widget.withPhone : widget.withEmail;
    if (targetList.isEmpty) return;

    setState(() {
      _isSending = true;
      _sentCount = 0;
    });

    for (final reservation in targetList) {
      // Formatear mensaje con datos del cliente
      final message = _messageController.text
          .replaceAll('{nombre}', reservation.customer?.name ?? 'Cliente')
          .replaceAll('{fecha}', reservation.date)
          .replaceAll('{ticket}', reservation.ticketName)
          .replaceAll('{codigo}', reservation.ticketCode);

      final encodedMessage = Uri.encodeComponent(message);

      try {
        if (_sendWhatsApp) {
          final phone = reservation.customer!.phone.replaceAll(RegExp(r'[^0-9]'), '');
          final url = 'https://wa.me/$phone?text=$encodedMessage';
          final uri = Uri.parse(url);
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        } else {
          final email = reservation.customer!.email;
          final subject = Uri.encodeComponent('Reserva ${reservation.ticketCode}');
          final url = 'mailto:$email?subject=$subject&body=$encodedMessage';
          final uri = Uri.parse(url);
          await launchUrl(uri);
        }

        setState(() => _sentCount++);

        // Pequeña pausa entre mensajes
        await Future.delayed(const Duration(milliseconds: 500));
      } catch (e) {
        // Continuar con el siguiente
      }
    }

    setState(() => _isSending = false);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Enviados $_sentCount de ${targetList.length} mensajes'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.8,
      minChildSize: 0.5,
      maxChildSize: 0.95,
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
                      color: Theme.of(context).colorScheme.outline.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                Text(
                  'Enviar mensaje masivo',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  '${widget.reservations.length} reservas seleccionadas',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                      ),
                ),
                const SizedBox(height: 24),

                // Selector de canal
                Text(
                  'Enviar por:',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                SegmentedButton<bool>(
                  segments: [
                    ButtonSegment(
                      value: true,
                      label: Text('WhatsApp (${widget.withPhone.length})'),
                      icon: const Icon(Icons.chat, color: Colors.green),
                    ),
                    ButtonSegment(
                      value: false,
                      label: Text('Email (${widget.withEmail.length})'),
                      icon: const Icon(Icons.email, color: Colors.blue),
                    ),
                  ],
                  selected: {_sendWhatsApp},
                  onSelectionChanged: (value) {
                    setState(() => _sendWhatsApp = value.first);
                  },
                ),
                const SizedBox(height: 24),

                // Plantillas
                Text(
                  'Plantillas:',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: messageTemplates.map((template) {
                    final isSelected = _selectedTemplate?.id == template.id;
                    return FilterChip(
                      selected: isSelected,
                      label: Text(template.name),
                      avatar: Icon(template.icon, size: 16),
                      onSelected: (_) => _applyTemplate(template),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 24),

                // Info de variables
                Card(
                  color: Theme.of(context).colorScheme.primaryContainer.withOpacity(0.3),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              Icons.info_outline,
                              size: 18,
                              color: Theme.of(context).colorScheme.primary,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'Variables disponibles:',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).colorScheme.primary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          '{nombre} - Nombre del cliente\n'
                          '{fecha} - Fecha de la reserva\n'
                          '{ticket} - Tipo de ticket\n'
                          '{codigo} - Código de reserva',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Campo de mensaje
                TextField(
                  controller: _messageController,
                  maxLines: 6,
                  decoration: InputDecoration(
                    hintText: 'Escribe tu mensaje...\nUsa las variables para personalizar cada mensaje.',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Botón enviar
                if (_isSending)
                  Column(
                    children: [
                      LinearProgressIndicator(
                        value: _sentCount / (_sendWhatsApp ? widget.withPhone.length : widget.withEmail.length),
                      ),
                      const SizedBox(height: 8),
                      Text('Enviando $_sentCount de ${_sendWhatsApp ? widget.withPhone.length : widget.withEmail.length}...'),
                    ],
                  )
                else
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: (_sendWhatsApp && widget.withPhone.isEmpty) ||
                              (!_sendWhatsApp && widget.withEmail.isEmpty)
                          ? null
                          : _sendBulkMessages,
                      icon: Icon(_sendWhatsApp ? Icons.chat : Icons.email),
                      label: Text(
                        'Enviar a ${_sendWhatsApp ? widget.withPhone.length : widget.withEmail.length} clientes',
                      ),
                    ),
                  ),

                const SizedBox(height: 16),
                Text(
                  _sendWhatsApp
                      ? 'Se abrirá WhatsApp para cada mensaje. Deberás confirmar el envío de cada uno.'
                      : 'Se abrirá tu app de email para cada mensaje.',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.onSurface.withOpacity(0.5),
                      ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

/// Vista de clientes de la semana integrada
class _WeeklyCustomersView extends ConsumerStatefulWidget {
  const _WeeklyCustomersView();

  @override
  ConsumerState<_WeeklyCustomersView> createState() => _WeeklyCustomersViewState();
}

class _WeeklyCustomersViewState extends ConsumerState<_WeeklyCustomersView> {
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now().add(const Duration(days: 7));
  String _filterOrigin = 'all';
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
    final customersAsync = ref.watch(unifiedCustomersProvider(_cachedParams));
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
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
                        '${_startDate.day}/${_startDate.month} - ${_endDate.day}/${_endDate.month}/${_endDate.year}',
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
                  _OriginFilterChip(
                    label: 'Todos',
                    selected: _filterOrigin == 'all',
                    onSelected: () => setState(() => _filterOrigin = 'all'),
                  ),
                  const SizedBox(width: 8),
                  _OriginFilterChip(
                    label: '📞 Manual',
                    selected: _filterOrigin == 'manual',
                    onSelected: () => setState(() => _filterOrigin = 'manual'),
                    color: Colors.orange,
                  ),
                  const SizedBox(width: 8),
                  _OriginFilterChip(
                    label: '🛒 WooCommerce',
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
              final filtered = _filterOrigin == 'all'
                  ? customers
                  : customers.where((c) => c.origin == _filterOrigin).toList();

              if (filtered.isEmpty) {
                return EmptyScreen(
                  message: 'No hay clientes',
                  subtitle: _filterOrigin == 'all'
                      ? 'No hay clientes en el rango seleccionado'
                      : 'No hay clientes ${_filterOrigin == 'manual' ? 'manuales' : 'de WooCommerce'}',
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
                  ref.invalidate(unifiedCustomersProvider(_cachedParams));
                },
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: sortedDates.length,
                  itemBuilder: (context, index) {
                    final date = sortedDates[index];
                    final dayCustomers = grouped[date]!;
                    return _CustomerDaySection(date: date, customers: dayCustomers);
                  },
                ),
              );
            },
            loading: () => const LoadingScreen(message: 'Cargando clientes...'),
            error: (error, _) => ErrorScreen(
              message: 'Error al cargar clientes',
              onRetry: () => ref.invalidate(unifiedCustomersProvider(_cachedParams)),
            ),
          ),
        ),
      ],
    );
  }
}

class _OriginFilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onSelected;
  final Color? color;

  const _OriginFilterChip({
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

class _CustomerDaySection extends StatelessWidget {
  final String date;
  final List<ManualCustomer> customers;

  const _CustomerDaySection({
    required this.date,
    required this.customers,
  });

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr);
      final weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
      return '${weekdays[dt.weekday - 1]} ${dt.day}/${dt.month}';
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    final manualCount = customers.where((c) => c.origin == 'manual').length;
    final wcCount = customers.where((c) => c.origin == 'woocommerce').length;

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
                _formatDate(date),
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
                  child: Text('📞 $manualCount', style: const TextStyle(fontSize: 12)),
                ),
              if (wcCount > 0)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text('🛒 $wcCount', style: const TextStyle(fontSize: 12)),
                ),
            ],
          ),
        ),
        const SizedBox(height: 8),

        // Clientes del día
        ...customers.map((c) => _CompactCustomerCard(customer: c)),

        const SizedBox(height: 16),
      ],
    );
  }
}

class _CompactCustomerCard extends StatelessWidget {
  final ManualCustomer customer;

  const _CompactCustomerCard({required this.customer});

  Future<void> _launchWhatsApp(String phone) async {
    String cleanPhone = phone.replaceAll(RegExp(r'[^\d+]'), '');
    if (!cleanPhone.startsWith('+')) {
      cleanPhone = cleanPhone.startsWith('34') ? '+$cleanPhone' : '+34$cleanPhone';
    }
    final uri = Uri.parse('https://wa.me/$cleanPhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _launchPhone(String phone) async {
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final isManual = customer.origin == 'manual';
    final borderColor = isManual ? Colors.orange : Colors.purple;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: borderColor.withOpacity(0.3), width: 2),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            // Icono de origen
            Text(isManual ? '📞' : '🛒', style: const TextStyle(fontSize: 20)),
            const SizedBox(width: 12),

            // Info del cliente
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    customer.name,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (customer.tickets.isNotEmpty)
                    Text(
                      customer.tickets.map((t) => '${t.name} x${t.quantity}').join(', '),
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurface.withOpacity(0.7),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                ],
              ),
            ),

            // Botones de contacto
            if (customer.phone.isNotEmpty) ...[
              IconButton(
                onPressed: () => _launchPhone(customer.phone),
                icon: const Icon(Icons.phone, size: 20),
                color: Colors.blue,
                tooltip: 'Llamar',
              ),
              IconButton(
                onPressed: () => _launchWhatsApp(customer.phone),
                icon: const Icon(Icons.chat, size: 20),
                color: const Color(0xFF25D366),
                tooltip: 'WhatsApp',
              ),
            ],
          ],
        ),
      ),
    );
  }
}
