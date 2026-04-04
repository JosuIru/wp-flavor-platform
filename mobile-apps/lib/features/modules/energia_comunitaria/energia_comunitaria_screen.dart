import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class EnergiaComunitariaScreen extends ConsumerStatefulWidget {
  const EnergiaComunitariaScreen({super.key});

  @override
  ConsumerState<EnergiaComunitariaScreen> createState() => _EnergiaComunitariaScreenState();
}

class _EnergiaComunitariaScreenState extends ConsumerState<EnergiaComunitariaScreen> {
  static const int _liquidacionesPageSize = 5;

  bool _isLoading = true;
  Map<String, dynamic> _dashboard = {};
  List<Map<String, dynamic>> _comunidades = [];
  List<Map<String, dynamic>> _instalaciones = [];
  List<Map<String, dynamic>> _liquidaciones = [];
  List<String> _periodOptions = [];
  String _selectedOrderBy = 'periodo';
  String _selectedOrderDir = 'desc';
  int? _selectedComunidadId;
  String _selectedEstado = '';
  String _selectedPeriodo = '';
  DateTime? _fechaDesde;
  DateTime? _fechaHasta;
  int _currentLiquidacionesPage = 1;
  bool _hasMoreLiquidaciones = false;
  bool _isLoadingMoreLiquidaciones = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final dashboardResponse = await api.get('/energia-comunitaria/dashboard');
      final comunidadesResponse = await api.get('/energia-comunitaria/comunidades');
      final instalacionesResponse = await api.get('/energia-comunitaria/instalaciones');

      setState(() {
        _dashboard = dashboardResponse.data ?? {};
        _comunidades = (comunidadesResponse.data?['items'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        _instalaciones = (instalacionesResponse.data?['items'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        _selectedComunidadId ??= _parseInt(_comunidades.isNotEmpty ? _comunidades.first['id'] : null);
      });

      await _loadLiquidaciones(reset: true);
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadLiquidaciones({bool reset = false}) async {
    final comunidadId = _selectedComunidadId;
    if (comunidadId == null || comunidadId <= 0) {
      if (mounted) {
        setState(() {
          _liquidaciones = [];
          _periodOptions = [];
          _currentLiquidacionesPage = 1;
          _hasMoreLiquidaciones = false;
          _isLoadingMoreLiquidaciones = false;
        });
      }
      return;
    }

    if (reset) {
      _currentLiquidacionesPage = 1;
    }

    final api = ref.read(apiClientProvider);
    final pageToLoad = reset ? 1 : _currentLiquidacionesPage;
    final queryParameters = <String, dynamic>{
      'page': pageToLoad,
      'per_page': _liquidacionesPageSize,
    };

    if (_selectedPeriodo.isNotEmpty) {
      queryParameters['periodo'] = _selectedPeriodo;
    }
    if (_selectedEstado.isNotEmpty) {
      queryParameters['estado'] = _selectedEstado;
    }
    queryParameters['order_by'] = _selectedOrderBy;
    queryParameters['order_dir'] = _selectedOrderDir;
    if (_fechaDesde != null) {
      queryParameters['fecha_desde'] = _formatDate(_fechaDesde!);
    }
    if (_fechaHasta != null) {
      queryParameters['fecha_hasta'] = _formatDate(_fechaHasta!);
    }

    final liquidacionesResponse = await api.get(
      '/energia-comunitaria/liquidaciones/$comunidadId',
      queryParameters: queryParameters,
    );

    final items = (liquidacionesResponse.data?['items'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();
    final pagination = liquidacionesResponse.data?['pagination'] as Map<String, dynamic>? ?? {};

    final periodSet = <String>{
      ..._periodOptions,
      ...items
          .map((item) => item['periodo']?.toString() ?? '')
          .where((periodo) => periodo.isNotEmpty),
    };

    if (mounted) {
      setState(() {
        _liquidaciones = reset ? items : [..._liquidaciones, ...items];
        _periodOptions = periodSet.toList()..sort((a, b) => b.compareTo(a));
        _currentLiquidacionesPage = pageToLoad + 1;
        _hasMoreLiquidaciones = pagination['has_more'] == true;
        _isLoadingMoreLiquidaciones = false;
      });
    }
  }

  Future<void> _pickDate({
    required bool isFromDate,
  }) async {
    final initialDate = isFromDate
        ? (_fechaDesde ?? DateTime.now())
        : (_fechaHasta ?? _fechaDesde ?? DateTime.now());
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );

    if (picked == null) {
      return;
    }

    setState(() {
      if (isFromDate) {
        _fechaDesde = picked;
      } else {
        _fechaHasta = picked;
      }
    });

    await _loadLiquidaciones(reset: true);
  }

  void _clearFilters() {
    setState(() {
      _selectedEstado = '';
      _selectedPeriodo = '';
      _fechaDesde = null;
      _fechaHasta = null;
      _selectedOrderBy = 'periodo';
      _selectedOrderDir = 'desc';
      _currentLiquidacionesPage = 1;
      _hasMoreLiquidaciones = false;
    });
    _loadLiquidaciones(reset: true);
  }

  int? _parseInt(dynamic value) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return int.tryParse(value?.toString() ?? '');
  }

  String _formatDate(DateTime date) {
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '${date.year}-$month-$day';
  }

  String _formatMonthLabel(String value) {
    if (!RegExp(r'^\d{4}-\d{2}$').hasMatch(value)) {
      return value;
    }

    final parts = value.split('-');
    return '${parts[1]}/${parts[0]}';
  }

  void _showMoreLiquidaciones() {
    if (_isLoadingMoreLiquidaciones || !_hasMoreLiquidaciones) {
      return;
    }

    setState(() => _isLoadingMoreLiquidaciones = true);
    _loadLiquidaciones();
  }

  Future<void> _updateLiquidacionEstado(
    Map<String, dynamic> item,
    String estado,
    BuildContext sheetContext,
  ) async {
    final navigator = Navigator.of(sheetContext);
    final liquidacionId = _parseInt(item['id']);
    if (liquidacionId == null || liquidacionId <= 0) {
      return;
    }

    final api = ref.read(apiClientProvider);
    final response = await api.post(
      '/energia-comunitaria/liquidacion/$liquidacionId/estado',
      data: {'estado': estado},
    );

    if (!mounted) {
      return;
    }

    if (response.data?['success'] == true) {
      navigator.pop();
      await _loadLiquidaciones(reset: true);
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response.data?['message']?.toString() ?? 'Estado actualizado')),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(response.error ?? 'No se pudo actualizar el estado')),
    );
  }

  Future<void> _shareLiquidacion(Map<String, dynamic> item) async {
    final liquidacionId = _parseInt(item['id']);
    if (liquidacionId == null || liquidacionId <= 0) {
      return;
    }

    final api = ref.read(apiClientProvider);
    final response = await api.get('/energia-comunitaria/liquidacion/$liquidacionId/export');

    if (!mounted) {
      return;
    }

    if (response.data?['success'] == true) {
      final csv = response.data?['csv']?.toString() ?? '';
      final subject = response.data?['filename']?.toString() ?? 'liquidacion.csv';

      if (csv.isNotEmpty) {
        await Share.share(csv, subject: subject);
        return;
      }
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(response.error ?? 'No se pudo exportar la liquidación')),
    );
  }

  void _showLiquidacionDetail(Map<String, dynamic> item) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.receipt_long, color: Colors.amber.shade700),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      item['participante_nombre']?.toString() ?? 'Liquidación',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _buildDetailRow('Referencia', item['referencia']?.toString() ?? ''),
              _buildDetailRow('Periodo', item['periodo']?.toString() ?? ''),
              _buildDetailRow('Estado', item['estado']?.toString() ?? ''),
              _buildDetailRow('kWh liquidados', '${item['kwh_liquidados'] ?? 0} kWh'),
              _buildDetailRow('Precio kWh', '${item['precio_kwh'] ?? 0} €/kWh'),
              _buildDetailRow('Ahorro estimado', '${item['importe_ahorro_eur'] ?? 0} €'),
              _buildDetailRow('Notificada', _formatDateTime(item['fecha_notificacion'])),
              _buildDetailRow('Aceptada', _formatDateTime(item['fecha_aceptacion'])),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _buildEstadoActionChip(item, sheetContext, 'generada'),
                  _buildEstadoActionChip(item, sheetContext, 'notificada'),
                  _buildEstadoActionChip(item, sheetContext, 'aceptada'),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  TextButton.icon(
                    onPressed: () => _shareLiquidacion(item),
                    icon: const Icon(Icons.share),
                    label: const Text('Compartir'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.of(sheetContext).pop(),
                    child: const Text('Cerrar'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEstadoActionChip(
    Map<String, dynamic> item,
    BuildContext sheetContext,
    String estado,
  ) {
    final currentEstado = item['estado']?.toString() ?? '';
    final isSelected = currentEstado == estado;

    return ActionChip(
      avatar: Icon(
        isSelected ? Icons.check_circle : Icons.sync,
        size: 18,
        color: isSelected ? Colors.green.shade700 : Colors.amber.shade700,
      ),
      label: Text(
        estado[0].toUpperCase() + estado.substring(1),
      ),
      onPressed: isSelected ? null : () => _updateLiquidacionEstado(item, estado, sheetContext),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.black54),
            ),
          ),
          Expanded(child: Text(value.isEmpty ? 'Sin dato' : value)),
        ],
      ),
    );
  }

  String _formatDateTime(dynamic value) {
    final raw = value?.toString() ?? '';
    if (raw.isEmpty) {
      return '';
    }

    final parsed = DateTime.tryParse(raw.replaceFirst(' ', 'T'));
    if (parsed == null) {
      return raw;
    }

    final month = parsed.month.toString().padLeft(2, '0');
    final day = parsed.day.toString().padLeft(2, '0');
    final hour = parsed.hour.toString().padLeft(2, '0');
    final minute = parsed.minute.toString().padLeft(2, '0');
    return '$day/$month/${parsed.year} $hour:$minute';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Energia Comunitaria')),
      body: _isLoading
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _loadData,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildKpiCard(),
                  const SizedBox(height: 20),
                  _buildComunidadesSection(),
                  const SizedBox(height: 20),
                  _buildLiquidacionesSection(),
                  const SizedBox(height: 20),
                  _buildInstalacionesSection(),
                ],
              ),
            ),
    );
  }

  Widget _buildKpiCard() {
    final generados = _dashboard['kwh_generados_mes'] ?? 0;
    final autosuficiencia = _dashboard['autosuficiencia_pct'] ?? 0;
    final incidencias = _dashboard['incidencias_abiertas'] ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Resumen energetico', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            Wrap(
              spacing: 16,
              runSpacing: 16,
              children: [
                _buildStat(Icons.bolt, '$generados kWh', 'Generados'),
                _buildStat(Icons.solar_power, '$autosuficiencia%', 'Autosuficiencia'),
                _buildStat(Icons.warning_amber_rounded, '$incidencias', 'Incidencias'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStat(IconData icon, String value, String label) {
    return SizedBox(
      width: 140,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: Colors.amber.shade700),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
                Text(label, style: const TextStyle(color: Colors.black54)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildComunidadesSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Comunidades energeticas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_comunidades.isEmpty)
          const FlavorEmptyState(
            icon: Icons.groups_outlined,
            title: 'No hay comunidades energéticas registradas',
          )
        else
          ..._comunidades.take(5).map((item) => Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  leading: const Icon(Icons.groups),
                  title: Text(item['nombre']?.toString() ?? ''),
                  subtitle: Text(item['comunidad_nombre']?.toString() ?? item['descripcion']?.toString() ?? ''),
                  trailing: Text(item['tipo_instalacion_principal']?.toString() ?? ''),
                ),
              )),
      ],
    );
  }

  Widget _buildInstalacionesSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Instalaciones', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_instalaciones.isEmpty)
          const FlavorEmptyState(
            icon: Icons.electrical_services_outlined,
            title: 'No hay instalaciones registradas',
          )
        else
          ..._instalaciones.take(8).map((item) => ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.electrical_services),
                title: Text(item['nombre']?.toString() ?? ''),
                subtitle: Text(item['tipo']?.toString() ?? ''),
                trailing: Text('${item['potencia_kw'] ?? 0} kW'),
              )),
      ],
    );
  }

  Widget _buildLiquidacionesSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Liquidaciones', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                DropdownButtonFormField<int>(
                  value: _comunidades.isEmpty ? null : _selectedComunidadId,
                  decoration: const InputDecoration(labelText: 'Comunidad energética'),
                  items: _comunidades
                      .map(
                        (item) => DropdownMenuItem<int>(
                          value: _parseInt(item['id']),
                          child: Text(item['nombre']?.toString() ?? ''),
                        ),
                      )
                      .toList(),
                  onChanged: (value) async {
                    setState(() {
                      _selectedComunidadId = value;
                      _selectedPeriodo = '';
                    });
                    await _loadLiquidaciones(reset: true);
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: _selectedEstado.isEmpty ? null : _selectedEstado,
                  decoration: const InputDecoration(labelText: 'Estado'),
                  items: const [
                    DropdownMenuItem<String>(value: '', child: Text('Todos')),
                    DropdownMenuItem<String>(value: 'generada', child: Text('Generada')),
                    DropdownMenuItem<String>(value: 'notificada', child: Text('Notificada')),
                    DropdownMenuItem<String>(value: 'aceptada', child: Text('Aceptada')),
                  ],
                  onChanged: (value) async {
                    setState(() => _selectedEstado = value ?? '');
                    await _loadLiquidaciones(reset: true);
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: _selectedPeriodo.isEmpty ? null : _selectedPeriodo,
                  decoration: const InputDecoration(labelText: 'Periodo'),
                  items: [
                    const DropdownMenuItem<String>(value: '', child: Text('Todos')),
                    ..._periodOptions.map(
                      (periodo) => DropdownMenuItem<String>(
                        value: periodo,
                        child: Text(_formatMonthLabel(periodo)),
                      ),
                    ),
                  ],
                  onChanged: (value) async {
                    setState(() => _selectedPeriodo = value ?? '');
                    await _loadLiquidaciones(reset: true);
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: '$_selectedOrderBy:$_selectedOrderDir',
                  decoration: const InputDecoration(labelText: 'Orden'),
                  items: const [
                    DropdownMenuItem<String>(value: 'periodo:desc', child: Text('Periodo descendente')),
                    DropdownMenuItem<String>(value: 'periodo:asc', child: Text('Periodo ascendente')),
                    DropdownMenuItem<String>(value: 'importe:desc', child: Text('Ahorro descendente')),
                    DropdownMenuItem<String>(value: 'importe:asc', child: Text('Ahorro ascendente')),
                    DropdownMenuItem<String>(value: 'estado:asc', child: Text('Estado A-Z')),
                    DropdownMenuItem<String>(value: 'fecha:desc', child: Text('Más recientes')),
                  ],
                  onChanged: (value) async {
                    final parts = (value ?? 'periodo:desc').split(':');
                    setState(() {
                      _selectedOrderBy = parts.isNotEmpty ? parts.first : 'periodo';
                      _selectedOrderDir = parts.length > 1 ? parts[1] : 'desc';
                    });
                    await _loadLiquidaciones(reset: true);
                  },
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _pickDate(isFromDate: true),
                        icon: const Icon(Icons.event),
                        label: Text(_fechaDesde == null ? 'Desde' : _formatDate(_fechaDesde!)),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _pickDate(isFromDate: false),
                        icon: const Icon(Icons.event_available),
                        label: Text(_fechaHasta == null ? 'Hasta' : _formatDate(_fechaHasta!)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton.icon(
                    onPressed: _clearFilters,
                    icon: const Icon(Icons.filter_alt_off),
                    label: const Text('Limpiar filtros'),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        if (_liquidaciones.isEmpty)
          const FlavorEmptyState(
            icon: Icons.receipt_long_outlined,
            title: 'No hay liquidaciones para los filtros seleccionados',
          )
        else
            ...[
            ..._liquidaciones.map(
              (item) => Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  onTap: () => _showLiquidacionDetail(item),
                  leading: const Icon(Icons.receipt_long),
                  title: Text(item['participante_nombre']?.toString() ?? ''),
                  subtitle: Text(
                    '${item['periodo'] ?? ''} • ${item['referencia'] ?? ''}\n'
                    'Estado: ${item['estado'] ?? ''}',
                  ),
                  isThreeLine: true,
                  dense: true,
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text('${item['importe_ahorro_eur'] ?? 0} €',
                          style: const TextStyle(fontWeight: FontWeight.bold)),
                      Text('${item['kwh_liquidados'] ?? 0} kWh'),
                    ],
                  ),
                ),
              ),
            ),
            if (_hasMoreLiquidaciones || _isLoadingMoreLiquidaciones)
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: _isLoadingMoreLiquidaciones ? null : _showMoreLiquidaciones,
                  icon: Icon(_isLoadingMoreLiquidaciones ? Icons.hourglass_bottom : Icons.expand_more),
                  label: Text(
                    _isLoadingMoreLiquidaciones ? 'Cargando...' : 'Ver más',
                  ),
                ),
              ),
          ],
      ],
    );
  }
}
