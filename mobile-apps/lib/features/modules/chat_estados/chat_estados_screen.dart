import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/utils/haptics.dart';

part 'chat_estados_screen_parts.dart';

/// Pantalla principal del módulo de Estados/Stories
/// Sistema de publicaciones efímeras tipo WhatsApp Status
class ChatEstadosScreen extends ConsumerStatefulWidget {
  const ChatEstadosScreen({super.key});

  @override
  ConsumerState<ChatEstadosScreen> createState() => _ChatEstadosScreenState();
}

class _ChatEstadosScreenState extends ConsumerState<ChatEstadosScreen> {
  _EstadosData? _estadosData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarEstados();
  }

  Future<void> _cargarEstados() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/flavor/v1/estados');

      if (response.success && response.data != null) {
        final data = response.data!;
        setState(() {
          _estadosData = _EstadosData.fromJson(data);
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar estados';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Estados'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarEstados,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton(
        onPressed: _crearEstado,
        tooltip: 'Nuevo estado',
        child: const Icon(Icons.add_a_photo),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const FlavorLoadingState();
    }

    if (_error != null) {
      return FlavorErrorState(
        message: _error!,
        onRetry: _cargarEstados,
      );
    }

    if (_estadosData == null) {
      return const FlavorEmptyState(
        icon: Icons.amp_stories_outlined,
        title: 'Sin estados',
        message: 'No hay estados disponibles',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEstados,
      child: ListView(
        padding: const EdgeInsets.symmetric(vertical: 8),
        children: [
          // Mi estado
          _MiEstadoTile(
            misEstados: _estadosData!.misEstados,
            onTap: _estadosData!.misEstados != null
                ? () => _verMisEstados()
                : _crearEstado,
            onCrear: _crearEstado,
          ),

          if (_estadosData!.contactos.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
              child: Text(
                'Actualizaciones recientes',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: Colors.grey,
                ),
              ),
            ),

            // Estados de contactos
            ...(_estadosData!.contactos).map((contacto) {
              return _ContactoEstadoTile(
                contacto: contacto,
                onTap: () => _verEstadosContacto(contacto),
              );
            }),
          ] else ...[
            const Padding(
              padding: EdgeInsets.all(32),
              child: Center(
                child: Column(
                  children: [
                    Icon(
                      Icons.people_outline,
                      size: 64,
                      color: Colors.grey,
                    ),
                    SizedBox(height: 16),
                    Text(
                      'Tus contactos no tienen estados recientes',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  void _crearEstado() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const _CrearEstadoScreen(),
      ),
    ).then((created) {
      if (created == true) {
        _cargarEstados();
      }
    });
  }

  void _verMisEstados() {
    if (_estadosData?.misEstados == null) return;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EstadoViewerScreen(
          contacto: _estadosData!.misEstados!,
          esMio: true,
          onEstadoVisto: (_) {},
        ),
      ),
    ).then((_) => _cargarEstados());
  }

  void _verEstadosContacto(_ContactoEstados contacto) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EstadoViewerScreen(
          contacto: contacto,
          esMio: false,
          onEstadoVisto: _marcarVisto,
        ),
      ),
    ).then((_) => _cargarEstados());
  }

  Future<void> _marcarVisto(int estadoId) async {
    try {
      final apiClient = ref.read(apiClientProvider);
      await apiClient.post('/flavor/v1/estados/$estadoId/ver', data: {});
    } catch (e) {
      debugPrint('Error marcando visto: $e');
    }
  }
}
