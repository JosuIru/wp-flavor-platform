import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../core/widgets/calendar_widgets.dart';
import '../../core/widgets/ticket_widgets.dart';
import '../../core/widgets/common_widgets.dart';
import 'checkout_webview.dart';

/// Pantalla de reservas para clientes
class ReservationsScreen extends ConsumerStatefulWidget {
  const ReservationsScreen({super.key});

  @override
  ConsumerState<ReservationsScreen> createState() => _ReservationsScreenState();
}

class _ReservationsScreenState extends ConsumerState<ReservationsScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  String _currentMonth = '';
  String? _selectedDate;
  AvailabilityDay? _selectedDay;
  bool _showTicketSelection = false;
  Map<String, int> _ticketQuantities = {};

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _currentMonth = '${now.year}-${now.month.toString().padLeft(2, '0')}';
  }

  void _onDateSelected(String date, AvailabilityDay day) {
    setState(() {
      _selectedDate = date;
      _selectedDay = day;
      _showTicketSelection = false;
      _ticketQuantities = {};
    });
  }

  void _onMonthChanged(String month) {
    setState(() {
      _currentMonth = month;
    });
    // Invalidar el provider para recargar disponibilidad
    ref.invalidate(availabilityProvider(_currentMonth));
  }

  void _openTicketSelection() {
    setState(() {
      _showTicketSelection = true;
    });
  }

  Future<void> _handleCheckout() async {
    if (_selectedDate == null || _selectedDay == null || _ticketQuantities.isEmpty) return;

    // Actualizar carrito
    final cartNotifier = ref.read(cartProvider.notifier);
    cartNotifier.setDate(_selectedDate!);

    // Usar tickets filtrados por estado del día
    final tickets = ref.read(ticketsByStateProvider(_selectedDay!.state)).valueOrNull ?? [];
    for (final entry in _ticketQuantities.entries) {
      final ticket = tickets.firstWhere(
        (t) => t.slug == entry.key,
        orElse: () => TicketType(slug: entry.key, name: entry.key),
      );
      cartNotifier.updateItem(entry.key, entry.value, ticket.price);
    }

    // Obtener URL de checkout
    final checkoutUrl = await cartNotifier.checkout();

    if (checkoutUrl != null && mounted) {
      // Abrir WebView de checkout
      final result = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (context) => CheckoutWebView(
            checkoutUrl: checkoutUrl,
          ),
        ),
      );

      if (result == true && mounted) {
        // Compra completada
        showAppSnackBar(
          context,
          message: '¡Reserva completada con éxito!',
        );
        // Limpiar estado
        setState(() {
          _selectedDate = null;
          _selectedDay = null;
          _showTicketSelection = false;
          _ticketQuantities = {};
        });
        cartNotifier.clear();
      }
    } else if (mounted) {
      final error = ref.read(cartProvider).error;
      showAppSnackBar(
        context,
        message: error ?? 'Error al procesar la reserva',
        isError: true,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final availabilityAsync = ref.watch(availabilityProvider(_currentMonth));
    final cartState = ref.watch(cartProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.reserveCta),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Calendario de disponibilidad
            availabilityAsync.when(
              data: (availability) => AvailabilityCalendar(
                availability: availability,
                selectedDate: _selectedDate,
                onDateSelected: _onDateSelected,
                onMonthChanged: _onMonthChanged,
              ),
              loading: () => SizedBox(
                height: 350,
                child: LoadingScreen(message: i18n.loadingAvailability),
              ),
              error: (error, stack) => SizedBox(
                height: 350,
                child: ErrorScreen(
                  message: i18n.reservationsAvailabilityError,
                  onRetry: () => ref.invalidate(availabilityProvider(_currentMonth)),
                ),
              ),
            ),

            // Leyenda
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: CalendarLegend(),
            ),

            // Información del día seleccionado
            if (_selectedDay != null)
              DayInfoCard(
                day: _selectedDay!,
                onSelect: _openTicketSelection,
              ),

            // Selección de tickets (filtrados por estado del día)
            if (_showTicketSelection && _selectedDay != null)
              ref.watch(ticketsByStateProvider(_selectedDay!.state)).when(
                data: (tickets) => Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(
                        'Selecciona tus tickets',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ),
                    TicketSelectionList(
                      tickets: tickets,
                      quantities: _ticketQuantities,
                      onQuantityChanged: (slug, quantity) {
                        setState(() {
                          if (quantity <= 0) {
                            _ticketQuantities.remove(slug);
                          } else {
                            _ticketQuantities[slug] = quantity;
                          }
                        });
                      },
                    ),
                    // Resumen del carrito
                    if (_ticketQuantities.isNotEmpty)
                      CartSummary(
                        tickets: tickets,
                        quantities: _ticketQuantities,
                        date: _selectedDate,
                        onCheckout: _handleCheckout,
                        isLoading: cartState.isLoading,
                      ),
                  ],
                ),
                loading: () => Padding(
                  padding: EdgeInsets.all(32),
                  child: LoadingScreen(message: i18n.loadingTickets),
                ),
                error: (error, stack) => Padding(
                  padding: const EdgeInsets.all(32),
                  child: ErrorScreen(
                    message: i18n.reservationsTicketsError,
                    onRetry: () => ref.invalidate(ticketsProvider),
                  ),
                ),
              ),

            const SizedBox(height: 100), // Espacio para scroll
          ],
        ),
      ),
    );
  }
}
