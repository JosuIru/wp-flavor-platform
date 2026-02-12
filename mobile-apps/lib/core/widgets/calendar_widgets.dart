import 'package:flutter/material.dart';
import 'package:table_calendar/table_calendar.dart';
import '../models/models.dart';
import '../utils/haptics.dart';

/// Widget de calendario de disponibilidad
class AvailabilityCalendar extends StatelessWidget {
  final List<AvailabilityDay> availability;
  final String? selectedDate;
  final Function(String, AvailabilityDay) onDateSelected;
  final Function(String)? onMonthChanged;

  const AvailabilityCalendar({
    super.key,
    required this.availability,
    this.selectedDate,
    required this.onDateSelected,
    this.onMonthChanged,
  });

  AvailabilityDay? _getDayInfo(DateTime day) {
    final dateStr = _formatDate(day);
    try {
      return availability.firstWhere((d) => d.date == dateStr);
    } catch (e) {
      return null;
    }
  }

  String _formatDate(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  Color _getColorForDay(AvailabilityDay? day) {
    if (day == null) return Colors.grey.shade300;

    switch (day.state.toLowerCase()) {
      case 'abierto':
      case 'disponible':
        return Colors.green;
      case 'completo':
      case 'lleno':
        return Colors.red;
      case 'limitado':
        return Colors.orange;
      case 'cerrado':
        return Colors.grey;
      default:
        return Colors.grey.shade300;
    }
  }

  @override
  Widget build(BuildContext context) {
    DateTime? selected;
    if (selectedDate != null) {
      try {
        selected = DateTime.parse(selectedDate!);
      } catch (e) {
        selected = null;
      }
    }

    return TableCalendar(
      firstDay: DateTime.now().subtract(const Duration(days: 30)),
      lastDay: DateTime.now().add(const Duration(days: 365)),
      focusedDay: selected ?? DateTime.now(),
      selectedDayPredicate: (day) {
        return selected != null && isSameDay(selected, day);
      },
      onDaySelected: (selectedDay, focusedDay) {
        Haptics.selection();
        final dateStr = _formatDate(selectedDay);
        final dayInfo = _getDayInfo(selectedDay);
        if (dayInfo != null) {
          onDateSelected(dateStr, dayInfo);
        }
      },
      calendarBuilders: CalendarBuilders(
        defaultBuilder: (context, day, focusedDay) {
          final dayInfo = _getDayInfo(day);
          final color = _getColorForDay(dayInfo);

          return Container(
            margin: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: color.withOpacity(0.3),
              border: Border.all(color: color, width: 2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Center(
              child: Text(
                '${day.day}',
                style: TextStyle(
                  color: color.computeLuminance() > 0.5
                      ? Colors.black
                      : Colors.white,
                ),
              ),
            ),
          );
        },
        selectedBuilder: (context, day, focusedDay) {
          final dayInfo = _getDayInfo(day);
          final color = _getColorForDay(dayInfo);

          return Container(
            margin: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(8),
              boxShadow: [
                BoxShadow(
                  color: color.withOpacity(0.5),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Center(
              child: Text(
                '${day.day}',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          );
        },
        todayBuilder: (context, day, focusedDay) {
          final dayInfo = _getDayInfo(day);
          final color = _getColorForDay(dayInfo);

          return Container(
            margin: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: color.withOpacity(0.5),
              border: Border.all(color: Colors.blue, width: 2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Center(
              child: Text(
                '${day.day}',
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          );
        },
      ),
      headerStyle: const HeaderStyle(
        formatButtonVisible: false,
        titleCentered: true,
      ),
    );
  }
}

/// Widget de leyenda del calendario
class CalendarLegend extends StatelessWidget {
  const CalendarLegend({super.key});

  Widget _buildLegendItem(BuildContext context, Color color, String label) {
    return Semantics(
      label: 'Color $label',
      child: Row(
        children: [
          ExcludeSemantics(
            child: Container(
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                color: color.withOpacity(0.3),
                border: Border.all(color: color, width: 2),
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: 'Leyenda del calendario',
      child: Wrap(
        spacing: 16,
        runSpacing: 8,
        children: [
          _buildLegendItem(context, Colors.green, 'Disponible'),
          _buildLegendItem(context, Colors.orange, 'Limitado'),
          _buildLegendItem(context, Colors.red, 'Completo'),
          _buildLegendItem(context, Colors.grey, 'Cerrado'),
        ],
      ),
    );
  }
}

/// Widget de información del día seleccionado
class DayInfoCard extends StatelessWidget {
  final AvailabilityDay day;
  final VoidCallback? onSelect;

  const DayInfoCard({
    super.key,
    required this.day,
    this.onSelect,
  });

  Color _getStateColor(String state) {
    switch (state.toLowerCase()) {
      case 'abierto':
      case 'disponible':
        return Colors.green;
      case 'completo':
      case 'lleno':
        return Colors.red;
      case 'limitado':
        return Colors.orange;
      case 'cerrado':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  IconData _getStateIcon(String state) {
    switch (state.toLowerCase()) {
      case 'abierto':
      case 'disponible':
        return Icons.check_circle;
      case 'completo':
      case 'lleno':
        return Icons.cancel;
      case 'limitado':
        return Icons.warning;
      case 'cerrado':
        return Icons.lock;
      default:
        return Icons.help;
    }
  }

  @override
  Widget build(BuildContext context) {
    final stateColor = _getStateColor(day.state);

    return MergeSemantics(
      child: Semantics(
        label: 'Dia ${day.date}, estado: ${day.state}',
        child: Card(
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    ExcludeSemantics(
                      child: Icon(
                        Icons.calendar_today,
                        size: 20,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      day.date,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    ExcludeSemantics(
                      child: Icon(
                        _getStateIcon(day.state),
                        color: stateColor,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      day.state,
                      style: TextStyle(
                        color: stateColor,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                // TODO: Agregar message y availableSpaces al modelo AvailabilityDay
                // si son necesarios para mostrar información adicional
                if (onSelect != null && day.state.toLowerCase() != 'cerrado') ...[
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: Semantics(
                      label: 'Seleccionar dia ${day.date}',
                      button: true,
                      child: FilledButton(
                        onPressed: () {
                          Haptics.light();
                          onSelect?.call();
                        },
                        child: const Text('Seleccionar día'),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
