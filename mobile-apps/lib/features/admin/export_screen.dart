import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:share_plus/share_plus.dart';
import 'package:path_provider/path_provider.dart';
import 'dart:io';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';

/// Pantalla de visualización y exportación de resúmenes
class ExportScreen extends ConsumerStatefulWidget {
  const ExportScreen({super.key});

  @override
  ConsumerState<ExportScreen> createState() => _ExportScreenState();
}

class _ExportScreenState extends ConsumerState<ExportScreen> {
  String _selectedType = 'reservations';
  String? _selectedTicketType;
  DateTimeRange? _dateRange;
  bool _isLoading = false;
  String? _lastExportPath;
  AppLocalizations get i18n => AppLocalizations.of(context)!;

  @override
  void initState() {
    super.initState();
    // Rango por defecto: ultimo mes
    final now = DateTime.now();
    _dateRange = DateTimeRange(
      start: DateTime(now.year, now.month - 1, now.day),
      end: now,
    );
  }

  Future<void> _selectDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      initialDateRange: _dateRange,
      firstDate: DateTime.now().subtract(const Duration(days: 365 * 2)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      helpText: i18n.exportSelectDateRangeHelp,
      cancelText: i18n.commonCancel,
      confirmText: i18n.commonAccept,
      saveText: i18n.commonSave,
    );

    if (range != null) {
      setState(() {
        _dateRange = range;
      });
    }
  }

  Future<void> _viewData() async {
    if (_dateRange == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(i18n.exportSelectDateRange)),
      );
      return;
    }

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final from = _formatDate(_dateRange!.start);
    final to = _formatDate(_dateRange!.end);

    final response = await api.viewDataWithTicket(
      type: _selectedType,
      from: from,
      to: to,
      ticketType: _selectedType == 'reservations' ? _selectedTicketType : null,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (response.success && response.data != null) {
      final headers = List<String>.from(response.data!['headers'] ?? []);
      final data = List<Map<String, dynamic>>.from(
        (response.data!['data'] as List).map((e) => Map<String, dynamic>.from(e)),
      );
      final total = response.data!['total'] ?? 0;

      if (data.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(i18n.exportNoDataToShow)),
        );
        return;
      }

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => _DataViewScreen(
            type: _selectedType,
            headers: headers,
            data: data,
            total: total,
            from: from,
            to: to,
            ticketType: _selectedTicketType,
          ),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response.error ?? i18n.exportErrorFetchingData)),
      );
    }
  }

  Future<void> _exportData() async {
    if (_dateRange == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(i18n.exportSelectDateRange)),
      );
      return;
    }

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final from = _formatDate(_dateRange!.start);
    final to = _formatDate(_dateRange!.end);

    final response = await api.exportCsvWithTicket(
      type: _selectedType,
      from: from,
      to: to,
      ticketType: _selectedType == 'reservations' ? _selectedTicketType : null,
    );

    if (!mounted) return;

    if (response.success && response.data != null) {
      final csvContent = response.data!['csv'] as String?;
      final filename = response.data!['filename'] as String? ??
          '${_selectedType}_$from-$to.csv';

      if (csvContent != null) {
        try {
          final tempDir = await getTemporaryDirectory();
          final file = File('${tempDir.path}/$filename');
          await file.writeAsString(csvContent);

          setState(() {
            _lastExportPath = file.path;
            _isLoading = false;
          });

          _showShareOptions(file.path, filename);
        } catch (e) {
          setState(() => _isLoading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(i18n.exportFileSaveError(e.toString()))),
          );
        }
      } else {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(i18n.exportNoDataToExport)),
        );
      }
    } else {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response.error ?? i18n.exportErrorExporting)),
      );
    }
  }

  void _showShareOptions(String filePath, String filename) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.check_circle,
              size: 48,
              color: Colors.green,
            ),
            const SizedBox(height: 16),
            Text(
              i18n.exportCompletedTitle,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              filename,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                  ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  Share.shareXFiles(
                    [XFile(filePath)],
                    subject: i18n.exportShareSubject(filename),
                  );
                },
                icon: const Icon(Icons.share),
                label: Text(i18n.exportShareFile),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => Navigator.pop(context),
                child: Text(i18n.commonClose),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  String _formatDisplayDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final ticketsAsync = ref.watch(ticketsProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.exportTitle),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Descripcion
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: colorScheme.primary,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        i18n.exportDescription,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // Tipo de exportacion
            Text(
              i18n.exportDataTypeTitle,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            _ExportTypeSelector(
              selectedType: _selectedType,
              onTypeChanged: (type) {
                setState(() {
                  _selectedType = type;
                  // Limpiar filtro de ticket al cambiar tipo
                  if (type != 'reservations') {
                    _selectedTicketType = null;
                  }
                });
              },
            ),

            // Filtro por tipo de ticket (solo para reservas)
            if (_selectedType == 'reservations') ...[
              const SizedBox(height: 24),
              Text(
                i18n.exportFilterTicketTypeTitle,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              ticketsAsync.when(
                data: (tickets) => _TicketTypeFilter(
                  tickets: tickets,
                  selectedTicketType: _selectedTicketType,
                  onTicketTypeChanged: (ticketType) {
                    setState(() {
                      _selectedTicketType = ticketType;
                    });
                  },
                ),
                loading: () => const Center(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                ),
                error: (_, __) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Icon(Icons.warning, color: colorScheme.error),
                        const SizedBox(width: 12),
                        Text(i18n.exportErrorLoadingTicketTypes),
                      ],
                    ),
                  ),
                ),
              ),
            ],

            const SizedBox(height: 24),

            // Rango de fechas
            Text(
              i18n.exportDateRangeTitle,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            Card(
              child: InkWell(
                onTap: _selectDateRange,
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(
                        Icons.date_range,
                        color: colorScheme.primary,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _dateRange != null
                                  ? '${_formatDisplayDate(_dateRange!.start)} - ${_formatDisplayDate(_dateRange!.end)}'
                                  : i18n.exportSelectDates,
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                            if (_dateRange != null)
                              Text(
                                i18n.exportDaysCount(_dateRange!.duration.inDays + 1),
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color: colorScheme.onSurface.withOpacity(0.7),
                                    ),
                              ),
                          ],
                        ),
                      ),
                      Icon(
                        Icons.chevron_right,
                        color: colorScheme.onSurface.withOpacity(0.5),
                      ),
                    ],
                  ),
                ),
              ),
            ),

            const SizedBox(height: 24),

            // Atajos de rango
            Text(
              i18n.exportQuickRangesTitle,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    color: colorScheme.onSurface.withOpacity(0.7),
                  ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _QuickRangeChip(
                  label: i18n.exportQuickToday,
                  onTap: () {
                    final now = DateTime.now();
                    setState(() {
                      _dateRange = DateTimeRange(start: now, end: now);
                    });
                  },
                ),
                _QuickRangeChip(
                  label: i18n.exportQuickWeek,
                  onTap: () {
                    final now = DateTime.now();
                    final start = now.subtract(Duration(days: now.weekday - 1));
                    setState(() {
                      _dateRange = DateTimeRange(start: start, end: now);
                    });
                  },
                ),
                _QuickRangeChip(
                  label: i18n.exportQuickMonth,
                  onTap: () {
                    final now = DateTime.now();
                    final start = DateTime(now.year, now.month, 1);
                    setState(() {
                      _dateRange = DateTimeRange(start: start, end: now);
                    });
                  },
                ),
                _QuickRangeChip(
                  label: i18n.exportQuickLast30,
                  onTap: () {
                    final now = DateTime.now();
                    final start = now.subtract(const Duration(days: 30));
                    setState(() {
                      _dateRange = DateTimeRange(start: start, end: now);
                    });
                  },
                ),
                _QuickRangeChip(
                  label: i18n.exportQuickYear,
                  onTap: () {
                    final now = DateTime.now();
                    final start = DateTime(now.year, 1, 1);
                    setState(() {
                      _dateRange = DateTimeRange(start: start, end: now);
                    });
                  },
                ),
              ],
            ),

            const SizedBox(height: 32),

            // Botones de accion
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _isLoading ? null : _viewData,
                    icon: const Icon(Icons.visibility),
                    label: Text(i18n.exportViewData),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: _isLoading ? null : _exportData,
                    icon: _isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.file_download),
                    label: Text(_isLoading ? i18n.commonLoading : i18n.exportCsv),
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

/// Filtro de tipos de ticket
class _TicketTypeFilter extends StatelessWidget {
  final List<TicketType> tickets;
  final String? selectedTicketType;
  final Function(String?) onTicketTypeChanged;

  const _TicketTypeFilter({
    required this.tickets,
    required this.selectedTicketType,
    required this.onTicketTypeChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Opción "Todos"
            RadioListTile<String?>(
              title: Text(AppLocalizations.of(context)!.exportAllTickets),
              subtitle: Text(AppLocalizations.of(context)!.exportNoTicketFilter),
              value: null,
              groupValue: selectedTicketType,
              onChanged: onTicketTypeChanged,
              dense: true,
            ),
            const Divider(),
            // Lista de tipos de ticket
            ...tickets.map((ticket) => RadioListTile<String?>(
              title: Text(ticket.name),
              subtitle: Text(ticket.formattedPrice),
              value: ticket.slug,
              groupValue: selectedTicketType,
              onChanged: onTicketTypeChanged,
              dense: true,
            )),
          ],
        ),
      ),
    );
  }
}

/// Pantalla para visualizar datos
class _DataViewScreen extends StatelessWidget {
  final String type;
  final List<String> headers;
  final List<Map<String, dynamic>> data;
  final int total;
  final String from;
  final String to;
  final String? ticketType;

  const _DataViewScreen({
    required this.type,
    required this.headers,
    required this.data,
    required this.total,
    required this.from,
    required this.to,
    this.ticketType,
  });

  String get _typeLabel {
    switch (type) {
      case 'reservations':
        return 'reservations';
      case 'customers':
        return 'customers';
      case 'revenue':
        return 'revenue';
      default:
        return type;
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final typeLabel = switch (_typeLabel) {
      'reservations' => i18n.exportTypeReservations,
      'customers' => i18n.exportTypeCustomers,
      'revenue' => i18n.exportTypeRevenue,
      _ => _typeLabel,
    };
    return Scaffold(
      appBar: AppBar(
        title: Text(typeLabel),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Text(
                i18n.exportTotalRecords(total),
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // Info del rango y filtro
          Container(
            padding: const EdgeInsets.all(12),
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.date_range, size: 16),
                    const SizedBox(width: 8),
                    Text('$from  -  $to'),
                  ],
                ),
                if (ticketType != null) ...[
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.confirmation_number, size: 16),
                      const SizedBox(width: 8),
                      Text(i18n.exportTicketLabel(ticketType!)),
                    ],
                  ),
                ],
              ],
            ),
          ),
          // Tabla de datos
          Expanded(
            child: data.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.inbox,
                          size: 64,
                          color: Theme.of(context).colorScheme.outline,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          i18n.exportNoData,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ],
                    ),
                  )
                : SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: SingleChildScrollView(
                      child: DataTable(
                        columns: headers.map((h) => DataColumn(
                          label: Text(
                            _formatHeader(h),
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                        )).toList(),
                        rows: data.map((row) {
                          return DataRow(
                            cells: headers.map((header) {
                              final value = row[header] ?? row[header.toLowerCase()] ?? '';
                              return DataCell(
                                Text(
                                  _formatCellValue(header, value),
                                  style: _getCellStyle(context, header, value),
                                ),
                              );
                            }).toList(),
                          );
                        }).toList(),
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  String _formatHeader(String header) {
    // Convertir snake_case a título legible
    final words = header.split('_');
    return words.map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '').join(' ');
  }

  String _formatCellValue(String header, dynamic value) {
    if (value == null || value.toString().isEmpty) {
      return '-';
    }

    // Formatear fechas
    if (header.toLowerCase().contains('fecha') || header.toLowerCase().contains('date')) {
      try {
        final date = DateTime.parse(value.toString());
        return '${date.day}/${date.month}/${date.year}';
      } catch (_) {
        return value.toString();
      }
    }

    // Formatear precios
    if (header.toLowerCase().contains('precio') ||
        header.toLowerCase().contains('total') ||
        header.toLowerCase().contains('revenue')) {
      try {
        final num = double.parse(value.toString());
        return '${num.toStringAsFixed(2)}€';
      } catch (_) {
        return value.toString();
      }
    }

    return value.toString();
  }

  TextStyle? _getCellStyle(BuildContext context, String header, dynamic value) {
    // Resaltar estados
    if (header.toLowerCase().contains('estado') || header.toLowerCase().contains('status')) {
      final status = value.toString().toLowerCase();
      if (status == 'pendiente') {
        return TextStyle(color: Colors.orange[700], fontWeight: FontWeight.bold);
      } else if (status == 'usado' || status == 'completado') {
        return TextStyle(color: Colors.green[700], fontWeight: FontWeight.bold);
      } else if (status == 'cancelado') {
        return TextStyle(color: Colors.red[700], fontWeight: FontWeight.bold);
      }
    }
    return null;
  }
}

class _ExportTypeSelector extends StatelessWidget {
  final String selectedType;
  final Function(String) onTypeChanged;

  const _ExportTypeSelector({
    required this.selectedType,
    required this.onTypeChanged,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final types = [
      (
        type: 'reservations',
        icon: Icons.calendar_today,
        label: i18n.exportTypeReservations,
        description: i18n.exportTypeReservationsDesc,
      ),
      (
        type: 'customers',
        icon: Icons.people,
        label: i18n.exportTypeCustomers,
        description: i18n.exportTypeCustomersDesc,
      ),
      (
        type: 'revenue',
        icon: Icons.euro,
        label: i18n.exportTypeRevenue,
        description: i18n.exportTypeRevenueDesc,
      ),
    ];

    return Column(
      children: types.map((item) {
        final isSelected = selectedType == item.type;
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          color: isSelected
              ? Theme.of(context).colorScheme.primaryContainer
              : null,
          child: InkWell(
            onTap: () => onTypeChanged(item.type),
            borderRadius: BorderRadius.circular(12),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? Theme.of(context).colorScheme.primary
                          : Theme.of(context).colorScheme.surfaceContainerHighest,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      item.icon,
                      color: isSelected
                          ? Theme.of(context).colorScheme.onPrimary
                          : Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.label,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        Text(
                          item.description,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withOpacity(0.7),
                              ),
                        ),
                      ],
                    ),
                  ),
                  if (isSelected)
                    Icon(
                      Icons.check_circle,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _QuickRangeChip extends StatelessWidget {
  final String label;
  final VoidCallback onTap;

  const _QuickRangeChip({
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text(label),
      onPressed: onTap,
    );
  }
}
