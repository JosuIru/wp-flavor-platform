import 'package:flavor_app/core/api/api_client.dart';
import 'package:flavor_app/core/widgets/flavor_error_widget.dart';
import 'package:flavor_app/core/widgets/flavor_loading_widget.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

part 'empresas_screen_parts.dart';

/// Pantalla principal del módulo Empresas
/// Gestiona empresas dentro de comunidades
class EmpresasScreen extends ConsumerStatefulWidget {
  const EmpresasScreen({super.key});

  @override
  ConsumerState<EmpresasScreen> createState() => _EmpresasScreenState();
}

class _EmpresasScreenState extends ConsumerState<EmpresasScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  List<dynamic> _empresas = [];
  List<dynamic> _misEmpresas = [];

  bool _cargandoEmpresas = true;
  bool _cargandoMisEmpresas = true;
  String? _errorEmpresas;
  String? _errorMisEmpresas;

  String _sectorFiltro = '';
  String _busqueda = '';

  final List<Map<String, String>> _sectores = [
    {'id': '', 'nombre': 'Todos los sectores'},
    {'id': 'tecnologia', 'nombre': 'Tecnología'},
    {'id': 'comercio', 'nombre': 'Comercio'},
    {'id': 'servicios', 'nombre': 'Servicios'},
    {'id': 'industria', 'nombre': 'Industria'},
    {'id': 'agricultura', 'nombre': 'Agricultura'},
    {'id': 'construccion', 'nombre': 'Construcción'},
    {'id': 'hosteleria', 'nombre': 'Hostelería'},
    {'id': 'transporte', 'nombre': 'Transporte'},
    {'id': 'educacion', 'nombre': 'Educación'},
    {'id': 'salud', 'nombre': 'Salud'},
    {'id': 'otros', 'nombre': 'Otros'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    await Future.wait([
      _cargarEmpresas(),
      _cargarMisEmpresas(),
    ]);
  }

  Future<void> _cargarEmpresas() async {
    setState(() {
      _cargandoEmpresas = true;
      _errorEmpresas = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      String endpoint = '/flavor/v1/empresas?';

      if (_sectorFiltro.isNotEmpty) {
        endpoint += 'sector=$_sectorFiltro&';
      }
      if (_busqueda.isNotEmpty) {
        endpoint += 's=$_busqueda&';
      }

      final response = await apiClient.get(endpoint);

      if (response != null && response['data'] != null) {
        setState(() {
          _empresas = response['data'] as List<dynamic>;
          _cargandoEmpresas = false;
        });
      } else {
        setState(() {
          _empresas = [];
          _cargandoEmpresas = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorEmpresas = e.toString();
        _cargandoEmpresas = false;
      });
    }
  }

  Future<void> _cargarMisEmpresas() async {
    setState(() {
      _cargandoMisEmpresas = true;
      _errorMisEmpresas = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/flavor/v1/empresas/mis-empresas');

      if (response != null && response['data'] != null) {
        setState(() {
          _misEmpresas = response['data'] as List<dynamic>;
          _cargandoMisEmpresas = false;
        });
      } else {
        setState(() {
          _misEmpresas = [];
          _cargandoMisEmpresas = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMisEmpresas = e.toString();
        _cargandoMisEmpresas = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Empresas'),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () async {
              final resultado = await showSearch(
                context: context,
                delegate: _EmpresaBusquedaDelegate(empresas: _empresas),
              );
              if (resultado != null) {
                _abrirEmpresa(resultado);
              }
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Directorio', icon: Icon(Icons.business)),
            Tab(text: 'Mis Empresas', icon: Icon(Icons.person)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDirectorioTab(),
          _buildMisEmpresasTab(),
        ],
      ),
    );
  }

  Widget _buildDirectorioTab() {
    return Column(
      children: [
        // Filtros
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            border: Border(
              bottom: BorderSide(color: Colors.grey[300]!),
            ),
          ),
          child: Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _sectorFiltro,
                  decoration: const InputDecoration(
                    labelText: 'Sector',
                    border: OutlineInputBorder(),
                    isDense: true,
                    contentPadding:
                        EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: _sectores.map((s) {
                    return DropdownMenuItem(
                      value: s['id'],
                      child: Text(
                        s['nombre']!,
                        style: const TextStyle(fontSize: 13),
                      ),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setState(() {
                      _sectorFiltro = value ?? '';
                    });
                    _cargarEmpresas();
                  },
                ),
              ),
            ],
          ),
        ),

        // Lista de empresas
        Expanded(
          child: _buildListaEmpresas(),
        ),
      ],
    );
  }

  Widget _buildListaEmpresas() {
    if (_cargandoEmpresas) {
      return const FlavorLoadingWidget(message: 'Cargando empresas...');
    }

    if (_errorEmpresas != null) {
      return FlavorErrorWidget(
        message: _errorEmpresas!,
        onRetry: _cargarEmpresas,
      );
    }

    if (_empresas.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.business_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No hay empresas registradas',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEmpresas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _empresas.length,
        itemBuilder: (context, index) {
          final empresa = _empresas[index];
          return _EmpresaCard(
            empresa: empresa,
            onTap: () => _abrirEmpresa(empresa),
          );
        },
      ),
    );
  }

  Widget _buildMisEmpresasTab() {
    if (_cargandoMisEmpresas) {
      return const FlavorLoadingWidget(message: 'Cargando tus empresas...');
    }

    if (_errorMisEmpresas != null) {
      return FlavorErrorWidget(
        message: _errorMisEmpresas!,
        onRetry: _cargarMisEmpresas,
      );
    }

    if (_misEmpresas.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.business_center_outlined,
                size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No perteneces a ninguna empresa',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
            const SizedBox(height: 8),
            Text(
              'Contacta con el administrador para unirte',
              style: TextStyle(color: Colors.grey[500], fontSize: 14),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarMisEmpresas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _misEmpresas.length,
        itemBuilder: (context, index) {
          final empresa = _misEmpresas[index];
          return _MiEmpresaCard(
            empresa: empresa,
            onTap: () => _abrirEmpresa(empresa),
          );
        },
      ),
    );
  }

  void _abrirEmpresa(dynamic empresa) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EmpresaDetalleScreen(
          empresaId: empresa['id'] ?? 0,
          empresaNombre: empresa['nombre'] ?? 'Empresa',
        ),
      ),
    );
  }
}
