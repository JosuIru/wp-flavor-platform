import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:table_calendar/table_calendar.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../core/widgets/common_widgets.dart';

/// Pantalla de calendario para ver reservas
class CalendarViewScreen extends ConsumerStatefulWidget {
  const CalendarViewScreen({super.key});

  @override
  ConsumerState<CalendarViewScreen> createState() => _CalendarViewScreenState();
}

class _CalendarViewScreenState extends ConsumerState<CalendarViewScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;
  String? _selectedTicketType;
  String? _selectedStatus;
  String _searchQuery = '';
  List<TicketType> _ticketTypes = [];

  Map<DateTime, List<Reservation>> _reservationsByDate = {};
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _selectedDay = _focusedDay;
    _loadTicketTypes();
    _loadReservationsForMonth(_focusedDay);
  }

  Future<void> _loadTicketTypes() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getTicketTypes();
      if (response.success && response.data != null) {
        final tickets = response.data!['tickets'] as List? ?? [];
        setState(() {
          _ticketTypes = tickets.map((t) => TicketType.fromJson(t)).toList();
        });
      }
    } catch (e) {
      // Ignorar errores de carga de tickets
    }
  }

  String _formatDateParam(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  DateTime _normalizeDate(DateTime date) {
    return DateTime(date.year, date.month, date.day);
  }

  Future<void> _loadReservationsForMonth(DateTime month) async {
    setState(() => _isLoading = true);

    final firstDay = DateTime(month.year, month.month, 1);
    final lastDay = DateTime(month.year, month.month + 1, 0);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getAdminReservations(
        from: _formatDateParam(firstDay),
        to: _formatDateParam(lastDay),
        ticketType: _selectedTicketType,
      );

      if (response.success && response.data != null) {
        final reservations = (response.data!['reservations'] as List? ?? [])
            .map((r) => Reservation.fromJson(r))
            .toList();

        // Agrupar por fecha
        final Map<DateTime, List<Reservation>> grouped = {};
        for (final reservation in reservations) {
          final date = _normalizeDate(DateTime.parse(reservation.date));
          if (!grouped.containsKey(date)) {
            grouped[date] = [];
          }
          grouped[date]!.add(reservation);
        }

        setState(() {
          _reservationsByDate = grouped;
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  List<Reservation> _getReservationsForDay(DateTime day) {
    final reservations = _reservationsByDate[_normalizeDate(day)] ?? [];
    return _applyFilters(reservations);
  }

  List<Reservation> _applyFilters(List<Reservation> reservations) {
    var filtered = reservations;

    // Filtro por estado
    if (_selectedStatus != null) {
      filtered = filtered.where((r) {
        switch (_selectedStatus) {
          case 'pending':
            return r.isPending;
          case 'used':
            return r.isUsed;
          case 'cancelled':
            return r.isCancelled;
          default:
            return true;
        }
      }).toList();
    }

    // Filtro por búsqueda
    if (_searchQuery.isNotEmpty) {
      filtered = filtered.where((r) {
        final customerName = r.customer?.name.toLowerCase() ?? '';
        final customerEmail = r.customer?.email.toLowerCase() ?? '';
        final customerPhone = r.customer?.phone.toLowerCase() ?? '';
        return customerName.contains(_searchQuery) ||
            customerEmail.contains(_searchQuery) ||
            customerPhone.contains(_searchQuery);
      }).toList();
    }

    return filtered;
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;
    final selectedDayReservations = _selectedDay != null
        ? _getReservationsForDay(_selectedDay!)
        : <Reservation>[];

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.calendarioB0743a),
        actions: [
          IconButton(
            onPressed: () {
              setState(() {
                _focusedDay = DateTime.now();
                _selectedDay = DateTime.now();
              });
              _loadReservationsForMonth(DateTime.now());
            },
            icon: const Icon(Icons.today),
            tooltip: i18n.hoy6368f5,
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                // Buscador
                TextField(
                  decoration: InputDecoration(
                    labelText: i18n.buscarCliente5a2444,
                    hintText: i18n.nombreEmailOTelFonoC50094,
                    prefixIcon: const Icon(Icons.search),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  ),
                  onChanged: (value) {
                    setState(() {
                      _searchQuery = value.toLowerCase();
                    });
                  },
                ),
                const SizedBox(height: 12),
                // Filtros en fila
                Row(
                  children: [
                    // Filtro de tipo de ticket
                    if (_ticketTypes.isNotEmpty)
                      Expanded(
                        child: DropdownButtonFormField<String?>(
                          value: _selectedTicketType,
                          decoration: InputDecoration(
                            labelText: i18n.tipoTicket402ee5,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            isDense: true,
                          ),
                          items: [
                            DropdownMenuItem<String?>(
                              value: null,
                              child: Text(i18n.todos32630c),
                            ),
                            ..._ticketTypes.map((ticket) => DropdownMenuItem<String?>(
                                  value: ticket.slug,
                                  child: Text(ticket.name, overflow: TextOverflow.ellipsis),
                                )),
                          ],
                          onChanged: (value) {
                            setState(() {
                              _selectedTicketType = value;
                            });
                            _loadReservationsForMonth(_focusedDay);
                          },
                        ),
                      ),
                    const SizedBox(width: 8),
                    // Filtro de estado
                    Expanded(
                      child: DropdownButtonFormField<String?>(
                        value: _selectedStatus,
                        decoration: InputDecoration(
                          labelText: i18n.estado3397e6,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          isDense: true,
                        ),
                        items: [
                          DropdownMenuItem<String?>(
                            value: null,
                            child: Text(i18n.todos32630c),
                          ),
                          DropdownMenuItem<String?>(
                            value: 'pending',
                            child: Text(i18n.pendiente17fd63),
                          ),
                          DropdownMenuItem<String?>(
                            value: 'used',
                            child: Text(i18n.usadoC277c8),
                          ),
                          DropdownMenuItem<String?>(
                            value: 'cancelled',
                            child: Text(i18n.cancelado04b0f5),
                          ),
                        ],
                        onChanged: (value) {
                          setState(() {
                            _selectedStatus = value;
                          });
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Calendario
          TableCalendar<Reservation>(
            firstDay: DateTime.now().subtract(const Duration(days: 365)),
            lastDay: DateTime.now().add(const Duration(days: 365)),
            focusedDay: _focusedDay,
            calendarFormat: _calendarFormat,
            selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
            eventLoader: _getReservationsForDay,
            startingDayOfWeek: StartingDayOfWeek.monday,
            calendarStyle: CalendarStyle(
              markersMaxCount: 3,
              markerDecoration: BoxDecoration(
                color: colorScheme.primary,
                shape: BoxShape.circle,
              ),
              todayDecoration: BoxDecoration(
                color: colorScheme.primaryContainer,
                shape: BoxShape.circle,
              ),
              selectedDecoration: BoxDecoration(
                color: colorScheme.primary,
                shape: BoxShape.circle,
              ),
              todayTextStyle: TextStyle(color: colorScheme.onPrimaryContainer),
              selectedTextStyle: TextStyle(color: colorScheme.onPrimary),
            ),
            headerStyle: HeaderStyle(
              formatButtonVisible: true,
              titleCentered: true,
              formatButtonShowsNext: false,
              formatButtonDecoration: BoxDecoration(
                border: Border.all(color: colorScheme.outline),
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            onDaySelected: (selectedDay, focusedDay) {
              setState(() {
                _selectedDay = selectedDay;
                _focusedDay = focusedDay;
              });
            },
            onFormatChanged: (format) {
              setState(() => _calendarFormat = format);
            },
            onPageChanged: (focusedDay) {
              _focusedDay = focusedDay;
              _loadReservationsForMonth(focusedDay);
            },
            calendarBuilders: CalendarBuilders(
              markerBuilder: (context, date, events) {
                if (events.isEmpty) return null;

                final pending = events.where((r) => r.isPending).length;
                final used = events.where((r) => r.isUsed).length;

                return Positioned(
                  bottom: 1,
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (pending > 0)
                        Container(
                          width: 8,
                          height: 8,
                          margin: const EdgeInsets.symmetric(horizontal: 1),
                          decoration: const BoxDecoration(
                            color: Colors.orange,
                            shape: BoxShape.circle,
                          ),
                        ),
                      if (used > 0)
                        Container(
                          width: 8,
                          height: 8,
                          margin: const EdgeInsets.symmetric(horizontal: 1),
                          decoration: const BoxDecoration(
                            color: Colors.green,
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                );
              },
            ),
          ),

          const Divider(height: 1),

          // Loading indicator
          if (_isLoading)
            const LinearProgressIndicator(),

          // Lista de reservas del día seleccionado
          Expanded(
            child: selectedDayReservations.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.event_available,
                          size: 48,
                          color: colorScheme.outline,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Sin reservas para este día',
                          style: TextStyle(color: colorScheme.outline),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: selectedDayReservations.length,
                    itemBuilder: (context, index) {
                      final reservation = selectedDayReservations[index];
                      return _ReservationCard(reservation: reservation);
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

class _ReservationCard extends StatelessWidget {
  final Reservation reservation;

  const _ReservationCard({required this.reservation});

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

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: _getStatusColor(),
            shape: BoxShape.circle,
          ),
        ),
        title: Text(
          reservation.customer?.name ?? 'Sin nombre',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Text(reservation.ticketName),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: _getStatusColor().withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            reservation.statusDisplay,
            style: TextStyle(
              color: _getStatusColor(),
              fontSize: 12,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ),
    );
  }
}
