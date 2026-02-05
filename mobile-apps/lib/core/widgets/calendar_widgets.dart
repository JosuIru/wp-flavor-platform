import 'package:flutter/material.dart';
import '../models/models.dart';

/// Calendario de disponibilidad
class AvailabilityCalendar extends StatefulWidget {
  final List<AvailabilityDay> availability;
  final String? selectedDate;
  final Function(String date, AvailabilityDay day)? onDateSelected;
  final Function(String month)? onMonthChanged;

  const AvailabilityCalendar({
    super.key,
    required this.availability,
    this.selectedDate,
    this.onDateSelected,
    this.onMonthChanged,
  });

  @override
  State<AvailabilityCalendar> createState() => _AvailabilityCalendarState();
}

class _AvailabilityCalendarState extends State<AvailabilityCalendar> {
  late DateTime _currentMonth;
  late Map<String, AvailabilityDay> _availabilityMap;

  @override
  void initState() {
    super.initState();
    _currentMonth = DateTime.now();
    _buildAvailabilityMap();
  }

  @override
  void didUpdateWidget(AvailabilityCalendar oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.availability != widget.availability) {
      _buildAvailabilityMap();
    }
  }

  void _buildAvailabilityMap() {
    _availabilityMap = {
      for (var day in widget.availability) day.date: day
    };
  }

  void _previousMonth() {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month - 1);
    });
    widget.onMonthChanged?.call(
      '${_currentMonth.year}-${_currentMonth.month.toString().padLeft(2, '0')}',
    );
  }

  void _nextMonth() {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month + 1);
    });
    widget.onMonthChanged?.call(
      '${_currentMonth.year}-${_currentMonth.month.toString().padLeft(2, '0')}',
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        _buildHeader(),
        _buildDayHeaders(),
        _buildCalendarGrid(),
      ],
    );
  }

  Widget _buildHeader() {
    final monthNames = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          IconButton(
            onPressed: _previousMonth,
            icon: const Icon(Icons.chevron_left),
          ),
          Text(
            '${monthNames[_currentMonth.month - 1]} ${_currentMonth.year}',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          IconButton(
            onPressed: _nextMonth,
            icon: const Icon(Icons.chevron_right),
          ),
        ],
      ),
    );
  }

  Widget _buildDayHeaders() {
    const days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    return Row(
      children: days.map((day) {
        return Expanded(
          child: Center(
            child: Text(
              day,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                  ),
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildCalendarGrid() {
    final firstDayOfMonth = DateTime(_currentMonth.year, _currentMonth.month, 1);
    final lastDayOfMonth = DateTime(_currentMonth.year, _currentMonth.month + 1, 0);
    final daysInMonth = lastDayOfMonth.day;

    // Ajustar para que lunes sea 0
    var startingWeekday = firstDayOfMonth.weekday - 1;
    if (startingWeekday < 0) startingWeekday = 6;

    final totalCells = startingWeekday + daysInMonth;
    final rows = (totalCells / 7).ceil();

    return Column(
      children: List.generate(rows, (row) {
        return Row(
          children: List.generate(7, (col) {
            final cellIndex = row * 7 + col;
            final dayNumber = cellIndex - startingWeekday + 1;

            if (cellIndex < startingWeekday || dayNumber > daysInMonth) {
              return const Expanded(child: SizedBox(height: 48));
            }

            return Expanded(
              child: _buildDayCell(dayNumber),
            );
          }),
        );
      }),
    );
  }

  Widget _buildDayCell(int dayNumber) {
    final date = DateTime(_currentMonth.year, _currentMonth.month, dayNumber);
    final dateString = '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
    final availability = _availabilityMap[dateString];
    final isToday = _isToday(date);
    final isSelected = widget.selectedDate == dateString;
    final isPast = date.isBefore(DateTime.now().subtract(const Duration(days: 1)));

    Color? backgroundColor;
    Color? textColor;
    bool isAvailable = false;

    if (availability != null && !isPast) {
      isAvailable = availability.state == 'disponible' || availability.state == 'abierto';
      backgroundColor = _parseColor(availability.color);
      textColor = _getContrastColor(backgroundColor);
    }

    return GestureDetector(
      onTap: () {
        if (availability != null && isAvailable) {
          widget.onDateSelected?.call(dateString, availability);
        } else if (availability != null) {
          // Mostrar feedback cuando el día no es clicable
          String message = 'Este día está ';
          if (availability.state == 'lleno') {
            message += 'completo';
          } else if (availability.state == 'cerrado') {
            message += 'cerrado';
          } else {
            message += availability.state;
          }

          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(message),
              duration: const Duration(seconds: 2),
              behavior: SnackBarBehavior.floating,
              margin: const EdgeInsets.all(16),
            ),
          );
        }
      },
      child: Container(
        height: 48,
        margin: const EdgeInsets.all(2),
        decoration: BoxDecoration(
          color: isSelected
              ? Theme.of(context).colorScheme.primary
              : backgroundColor?.withOpacity(0.3),
          borderRadius: BorderRadius.circular(8),
          border: isToday
              ? Border.all(
                  color: Theme.of(context).colorScheme.primary,
                  width: 2,
                )
              : null,
        ),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '$dayNumber',
                style: TextStyle(
                  color: isPast
                      ? Theme.of(context).colorScheme.onSurface.withOpacity(0.3)
                      : isSelected
                          ? Theme.of(context).colorScheme.onPrimary
                          : textColor ?? Theme.of(context).colorScheme.onSurface,
                  fontWeight: isToday ? FontWeight.bold : FontWeight.normal,
                ),
              ),
              if (availability != null && !isPast)
                Container(
                  width: 6,
                  height: 6,
                  margin: const EdgeInsets.only(top: 2),
                  decoration: BoxDecoration(
                    color: _parseColor(availability.color),
                    shape: BoxShape.circle,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  bool _isToday(DateTime date) {
    final now = DateTime.now();
    return date.year == now.year && date.month == now.month && date.day == now.day;
  }

  Color _parseColor(String hexColor) {
    try {
      final hex = hexColor.replaceFirst('#', '');
      return Color(int.parse('FF$hex', radix: 16));
    } catch (e) {
      return Colors.green;
    }
  }

  Color _getContrastColor(Color color) {
    final luminance = color.computeLuminance();
    return luminance > 0.5 ? Colors.black : Colors.white;
  }
}

/// Leyenda de estados del calendario
class CalendarLegend extends StatelessWidget {
  final List<({String state, String name, String color})> states;

  const CalendarLegend({
    super.key,
    this.states = const [
      (state: 'disponible', name: 'Disponible', color: '#4CAF50'),
      (state: 'lleno', name: 'Completo', color: '#F44336'),
      (state: 'cerrado', name: 'Cerrado', color: '#9E9E9E'),
    ],
  });

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 16,
      runSpacing: 8,
      children: states.map((state) {
        return Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                color: _parseColor(state.color),
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 4),
            Text(
              state.name,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        );
      }).toList(),
    );
  }

  Color _parseColor(String hexColor) {
    try {
      final hex = hexColor.replaceFirst('#', '');
      return Color(int.parse('FF$hex', radix: 16));
    } catch (e) {
      return Colors.grey;
    }
  }
}

/// Información del día seleccionado
class DayInfoCard extends StatelessWidget {
  final AvailabilityDay day;
  final VoidCallback? onSelect;

  const DayInfoCard({
    super.key,
    required this.day,
    this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final isAvailable = day.state == 'disponible' || day.state == 'abierto';

    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  Icons.calendar_today,
                  color: Theme.of(context).colorScheme.primary,
                ),
                const SizedBox(width: 8),
                Text(
                  _formatDate(day.date),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: _parseColor(day.color).withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    day.stateName,
                    style: TextStyle(
                      color: _parseColor(day.color),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                if (day.schedule.isNotEmpty) ...[
                  const SizedBox(width: 12),
                  Icon(
                    Icons.access_time,
                    size: 16,
                    color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    day.schedule,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                        ),
                  ),
                ],
              ],
            ),
            if (isAvailable && onSelect != null) ...[
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: onSelect,
                  icon: const Icon(Icons.confirmation_number),
                  label: const Text('Seleccionar tickets'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _formatDate(String date) {
    try {
      final parts = date.split('-');
      final months = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
      ];
      final day = int.parse(parts[2]);
      final month = months[int.parse(parts[1]) - 1];
      return '$day de $month';
    } catch (e) {
      return date;
    }
  }

  Color _parseColor(String hexColor) {
    try {
      final hex = hexColor.replaceFirst('#', '');
      return Color(int.parse('FF$hex', radix: 16));
    } catch (e) {
      return Colors.green;
    }
  }
}
