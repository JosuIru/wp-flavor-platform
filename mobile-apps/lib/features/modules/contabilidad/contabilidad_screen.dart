import 'package:flavor_app/core/api/api_client.dart';
import 'package:flavor_app/core/widgets/flavor_error_widget.dart';
import 'package:flavor_app/core/widgets/flavor_loading_widget.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

part 'contabilidad_screen_parts.dart';

/// Pantalla principal del módulo Contabilidad
/// Muestra dashboard con resumen, movimientos y gráficos
class ContabilidadScreen extends ConsumerStatefulWidget {
  const ContabilidadScreen({super.key});

  @override
  ConsumerState<ContabilidadScreen> createState() => _ContabilidadScreenState();
}

class _ContabilidadScreenState extends ConsumerState<ContabilidadScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  Map<String, dynamic>? _dashboard;
  List<dynamic> _movimientos = [];
  List<dynamic> _graficoMensual = [];

  bool _cargandoDashboard = true;
  bool _cargandoMovimientos = true;
  String? _error;

  String _tipoFiltro = '';
  int _mesSeleccionado = DateTime.now().month;
  int _anoSeleccionado = DateTime.now().year;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    await Future.wait([
      _cargarDashboard(),
      _cargarMovimientos(),
      _cargarGraficoMensual(),
    ]);
  }

  Future<void> _cargarDashboard() async {
    setState(() {
      _cargandoDashboard = true;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/flavor/v1/contabilidad/dashboard?ano=$_anoSeleccionado&mes=${_mesSeleccionado.toString().padLeft(2, '0')}',
      );

      if (response != null && response['data'] != null) {
        setState(() {
          _dashboard = response['data'] as Map<String, dynamic>;
          _cargandoDashboard = false;
        });
      } else {
        setState(() {
          _dashboard = null;
          _cargandoDashboard = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargandoDashboard = false;
      });
    }
  }

  Future<void> _cargarMovimientos() async {
    setState(() {
      _cargandoMovimientos = true;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      String endpoint =
          '/flavor/v1/contabilidad/movimientos?per_page=100&desde=$_anoSeleccionado-${_mesSeleccionado.toString().padLeft(2, '0')}-01&hasta=${_anoSeleccionado}-${_mesSeleccionado.toString().padLeft(2, '0')}-31';

      if (_tipoFiltro.isNotEmpty) {
        endpoint += '&tipo=$_tipoFiltro';
      }

      final response = await apiClient.get(endpoint);

      if (response != null && response['data'] != null) {
        setState(() {
          _movimientos = response['data'] as List<dynamic>;
          _cargandoMovimientos = false;
        });
      } else {
        setState(() {
          _movimientos = [];
          _cargandoMovimientos = false;
        });
      }
    } catch (e) {
      setState(() {
        _movimientos = [];
        _cargandoMovimientos = false;
      });
    }
  }

  Future<void> _cargarGraficoMensual() async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/flavor/v1/contabilidad/grafico-mensual');

      if (response != null && response['data'] != null) {
        setState(() {
          _graficoMensual = response['data'] as List<dynamic>;
        });
      }
    } catch (e) {
      // Ignorar error del gráfico
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Contabilidad'),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_month),
            onPressed: _seleccionarMes,
            tooltip: 'Seleccionar mes',
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Resumen', icon: Icon(Icons.dashboard)),
            Tab(text: 'Movimientos', icon: Icon(Icons.list)),
            Tab(text: 'Gráficos', icon: Icon(Icons.show_chart)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildResumenTab(),
          _buildMovimientosTab(),
          _buildGraficosTab(),
        ],
      ),
    );
  }

  Widget _buildResumenTab() {
    if (_cargandoDashboard) {
      return const FlavorLoadingWidget(message: 'Cargando resumen...');
    }

    if (_error != null) {
      return FlavorErrorWidget(
        message: _error!,
        onRetry: _cargarDashboard,
      );
    }

    if (_dashboard == null) {
      return const Center(child: Text('Sin datos de contabilidad'));
    }

    final mes = _dashboard!['mes'] as Map<String, dynamic>?;
    final ano = _dashboard!['ano'] as Map<String, dynamic>?;
    final ultimos = _dashboard!['ultimos_movimientos'] as List<dynamic>? ?? [];

    return RefreshIndicator(
      onRefresh: _cargarDashboard,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Selector de período
            _PeriodoSelector(
              mes: _mesSeleccionado,
              ano: _anoSeleccionado,
              onChanged: _cambiarPeriodo,
            ),
            const SizedBox(height: 20),

            // Resumen del mes
            if (mes != null) ...[
              const Text(
                'Resumen del mes',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              _ResumenMesCard(datos: mes),
            ],

            const SizedBox(height: 24),

            // Resumen del año
            if (ano != null) ...[
              const Text(
                'Acumulado anual',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              _ResumenAnoCard(datos: ano),
            ],

            const SizedBox(height: 24),

            // Últimos movimientos
            if (ultimos.isNotEmpty) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Últimos movimientos',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  TextButton(
                    onPressed: () => _tabController.animateTo(1),
                    child: const Text('Ver todos'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ...ultimos.take(5).map((m) => _MovimientoItem(
                    movimiento: m,
                    onTap: () => _verMovimiento(m),
                  )),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildMovimientosTab() {
    return Column(
      children: [
        // Filtros
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            border: Border(
              bottom: BorderSide(color: Colors.grey[300]!),
            ),
          ),
          child: Row(
            children: [
              Expanded(
                child: _TipoFilterChips(
                  tipoSeleccionado: _tipoFiltro,
                  onChanged: (tipo) {
                    setState(() {
                      _tipoFiltro = tipo;
                    });
                    _cargarMovimientos();
                  },
                ),
              ),
            ],
          ),
        ),

        // Lista de movimientos
        Expanded(
          child: _cargandoMovimientos
              ? const FlavorLoadingWidget(message: 'Cargando...')
              : _movimientos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.receipt_long,
                              size: 64, color: Colors.grey[400]),
                          const SizedBox(height: 16),
                          Text(
                            'Sin movimientos este mes',
                            style:
                                TextStyle(color: Colors.grey[600], fontSize: 16),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarMovimientos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _movimientos.length,
                        itemBuilder: (context, index) {
                          final mov = _movimientos[index];
                          return _MovimientoCard(
                            movimiento: mov,
                            onTap: () => _verMovimiento(mov),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildGraficosTab() {
    if (_graficoMensual.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.insert_chart_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'Sin datos para gráficos',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarGraficoMensual,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Evolución últimos 12 meses',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 20),
            _GraficoEvolucion(datos: _graficoMensual),
            const SizedBox(height: 32),
            const Text(
              'Detalle mensual',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            _TablaEvolucion(datos: _graficoMensual),
          ],
        ),
      ),
    );
  }

  void _seleccionarMes() async {
    final result = await showDatePicker(
      context: context,
      initialDate: DateTime(_anoSeleccionado, _mesSeleccionado),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDatePickerMode: DatePickerMode.year,
    );

    if (result != null) {
      _cambiarPeriodo(result.month, result.year);
    }
  }

  void _cambiarPeriodo(int mes, int ano) {
    setState(() {
      _mesSeleccionado = mes;
      _anoSeleccionado = ano;
    });
    _cargarDashboard();
    _cargarMovimientos();
  }

  void _verMovimiento(dynamic movimiento) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => _MovimientoDetalleSheet(movimiento: movimiento),
    );
  }
}
