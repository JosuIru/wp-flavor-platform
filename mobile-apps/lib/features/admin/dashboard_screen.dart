import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/common_widgets.dart';
import 'qr_scanner_screen.dart';
import 'export_screen.dart';
import 'stats_screen.dart';
import 'calendar_view_screen.dart';
import 'customers_screen.dart';
import 'manual_customers_screen.dart';
import 'escalated_chats_screen.dart';
import 'camps/camps_management_screen.dart';

/// Dashboard para administradores
class DashboardScreen extends ConsumerWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;

  const DashboardScreen({
    super.key,
    this.onNavigateToReservations,
    this.onNavigateToChat,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(dashboardProvider),
            icon: const Icon(Icons.refresh),
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: dashboardAsync.when(
        data: (data) => RefreshableList(
          onRefresh: () async {
            ref.invalidate(dashboardProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Fecha de hoy
                Text(
                  _formatDate(data.today.date),
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 24),

                // Estadísticas de hoy
                Text(
                  'Hoy',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: 1.3,
                  children: [
                    StatCard(
                      title: 'Reservas',
                      value: '${data.today.reservations}',
                      icon: Icons.calendar_today,
                      color: Colors.blue,
                    ),
                    StatCard(
                      title: 'Check-ins',
                      value: '${data.today.checkins}',
                      icon: Icons.check_circle,
                      color: Colors.green,
                      subtitle: '${data.today.pendingCheckins} pendientes',
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // Estadísticas de la semana
                Text(
                  'Esta semana',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                StatCard(
                  title: 'Reservas totales',
                  value: '${data.week.reservations}',
                  icon: Icons.event_note,
                  color: Colors.purple,
                ),

                const SizedBox(height: 24),

                // Estadísticas del mes
                Text(
                  'Este mes',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                StatCard(
                  title: 'Ingresos',
                  value: data.month.formattedRevenue,
                  icon: Icons.euro,
                  color: Colors.amber,
                ),

                const SizedBox(height: 24),

                // Accesos rápidos
                Text(
                  'Accesos rápidos',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                _QuickActions(
                  onNavigateToReservations: onNavigateToReservations,
                  onNavigateToChat: onNavigateToChat,
                ),
              ],
            ),
          ),
        ),
        loading: () => const LoadingScreen(message: 'Cargando dashboard...'),
        error: (error, stack) => ErrorScreen(
          message: 'Error al cargar el dashboard',
          onRetry: () => ref.invalidate(dashboardProvider),
        ),
      ),
    );
  }

  String _formatDate(String date) {
    try {
      if (date.isEmpty) {
        final now = DateTime.now();
        date = '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      }
      final parts = date.split('-');
      final weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
      final months = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
      ];
      final dateObj = DateTime.parse(date);
      final weekday = weekdays[dateObj.weekday - 1];
      final day = int.parse(parts[2]);
      final month = months[int.parse(parts[1]) - 1];
      return '$weekday, $day de $month';
    } catch (e) {
      return date;
    }
  }
}

class _QuickActions extends StatelessWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;

  const _QuickActions({
    this.onNavigateToReservations,
    this.onNavigateToChat,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Acciones principales
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            _QuickActionChip(
              icon: Icons.qr_code_scanner,
              label: 'Escanear QR',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const QRScannerScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.calendar_today,
              label: 'Ver reservas',
              onTap: onNavigateToReservations ?? () {},
            ),
            _QuickActionChip(
              icon: Icons.smart_toy,
              label: 'Chat IA',
              onTap: onNavigateToChat ?? () {},
            ),
            _QuickActionChip(
              icon: Icons.summarize,
              label: 'Ver resúmenes',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const ExportScreen(),
                  ),
                );
              },
            ),
          ],
        ),

        const SizedBox(height: 24),

        // Herramientas
        Text(
          'Herramientas',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            _QuickActionChip(
              icon: Icons.bar_chart,
              label: 'Estadísticas',
              color: Colors.purple,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const StatsScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.calendar_month,
              label: 'Calendario',
              color: Colors.teal,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const CalendarViewScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.people,
              label: 'Clientes',
              color: Colors.indigo,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const CustomersScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.people_outline,
              label: 'Clientes Semana',
              color: Colors.orange,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const ManualCustomersScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.support_agent,
              label: 'Chats Escalados',
              color: Colors.red,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const EscalatedChatsScreen(),
                  ),
                );
              },
            ),
            _QuickActionChip(
              icon: Icons.cabin,
              label: 'Campamentos',
              color: Colors.brown,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const CampsManagementScreen(),
                  ),
                );
              },
            ),
          ],
        ),
      ],
    );
  }
}

class _QuickActionChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  const _QuickActionChip({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(icon, size: 18, color: color),
      label: Text(label),
      onPressed: onTap,
      side: color != null ? BorderSide(color: color!.withOpacity(0.3)) : null,
    );
  }
}
