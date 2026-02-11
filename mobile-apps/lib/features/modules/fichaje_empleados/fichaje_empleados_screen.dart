import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class FichajeEmpleadosScreen extends ConsumerStatefulWidget {
  const FichajeEmpleadosScreen({super.key});

  @override
  ConsumerState<FichajeEmpleadosScreen> createState() => _FichajeEmpleadosScreenState();
}

class _FichajeEmpleadosScreenState extends ConsumerState<FichajeEmpleadosScreen> {
  bool _isLoading = true;
  bool _isFichado = false;
  Map<String, dynamic>? _estadoActual;
  List<Map<String, dynamic>> _historialReciente = [];
  final _notasController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadEstado();
  }

  @override
  void dispose() {
    _notasController.dispose();
    super.dispose();
  }

  Future<void> _loadEstado() async {
    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);

    // Cargar estado actual
    final estadoResponse = await api.getFichajeEstado();
    if (estadoResponse.success && estadoResponse.data != null) {
      setState(() {
        _estadoActual = estadoResponse.data;
        _isFichado = estadoResponse.data!['fichado'] == true;
      });
    }

    // Cargar historial reciente (últimos 7 días)
    final now = DateTime.now();
    final hace7Dias = now.subtract(const Duration(days: 7));
    final historialResponse = await api.getFichajeHistorial(
      desde: DateFormat('yyyy-MM-dd').format(hace7Dias),
      hasta: DateFormat('yyyy-MM-dd').format(now),
    );

    if (historialResponse.success && historialResponse.data != null) {
      final fichajes = historialResponse.data!['fichajes'] as List<dynamic>? ?? [];
      setState(() {
        _historialReciente = fichajes.whereType<Map<String, dynamic>>().toList();
      });
    }

    setState(() => _isLoading = false);
  }

  Future<void> _registrarEntrada() async {
    final notas = _notasController.text.trim();

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.registrarEntrada(notas: notas.isEmpty ? null : notas);

    if (response.success) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ Entrada registrada correctamente'),
            backgroundColor: Colors.green,
          ),
        );
      }
      _notasController.clear();
      await _loadEstado();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al registrar entrada'),
            backgroundColor: Colors.red,
          ),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _registrarSalida() async {
    final notas = _notasController.text.trim();

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.registrarSalida(notas: notas.isEmpty ? null : notas);

    if (response.success) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ Salida registrada correctamente'),
            backgroundColor: Colors.green,
          ),
        );
      }
      _notasController.clear();
      await _loadEstado();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al registrar salida'),
            backgroundColor: Colors.red,
          ),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Fichaje de Empleados'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadEstado,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadEstado,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _buildEstadoCard(),
                    const SizedBox(height: 16),
                    _buildFichajeCard(),
                    const SizedBox(height: 24),
                    _buildHistorialSection(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildEstadoCard() {
    final horaEntrada = _estadoActual?['hora_entrada'];
    final horaSalida = _estadoActual?['hora_salida'];

    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: _isFichado ? Colors.green.shade100 : Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: Icon(
                    _isFichado ? Icons.check_circle : Icons.access_time,
                    color: _isFichado ? Colors.green : Colors.grey,
                    size: 28,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _isFichado ? 'FICHADO' : 'NO FICHADO',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: _isFichado ? Colors.green : Colors.grey,
                        ),
                      ),
                      Text(
                        DateFormat('EEEE, d MMMM yyyy', 'es').format(DateTime.now()),
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (horaEntrada != null || horaSalida != null) ...[
              const Divider(height: 24),
              Row(
                children: [
                  if (horaEntrada != null)
                    Expanded(
                      child: _buildTimeInfo('Entrada', horaEntrada, Icons.login),
                    ),
                  if (horaEntrada != null && horaSalida != null)
                    Container(width: 1, height: 40, color: Colors.grey.shade300),
                  if (horaSalida != null)
                    Expanded(
                      child: _buildTimeInfo('Salida', horaSalida, Icons.logout),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildTimeInfo(String label, String hora, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: Colors.blue, size: 20),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
        ),
        Text(
          hora,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  Widget _buildFichajeCard() {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Registrar Fichaje',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _notasController,
              decoration: const InputDecoration(
                labelText: 'Notas (opcional)',
                hintText: 'Añade alguna observación...',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.note),
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    onPressed: _isFichado ? null : _registrarEntrada,
                    icon: const Icon(Icons.login),
                    label: const Text('Entrada'),
                    style: FilledButton.styleFrom(
                      backgroundColor: Colors.green,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: !_isFichado ? null : _registrarSalida,
                    icon: const Icon(Icons.logout),
                    label: const Text('Salida'),
                    style: FilledButton.styleFrom(
                      backgroundColor: Colors.orange,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHistorialSection() {
    if (_historialReciente.isEmpty) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            children: [
              Icon(Icons.history, size: 48, color: Colors.grey.shade400),
              const SizedBox(height: 8),
              Text(
                'No hay fichajes recientes',
                style: TextStyle(color: Colors.grey.shade600),
              ),
            ],
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Historial Reciente',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 12),
        ..._historialReciente.map((fichaje) => _buildFichajeItem(fichaje)),
      ],
    );
  }

  Widget _buildFichajeItem(Map<String, dynamic> fichaje) {
    final fecha = fichaje['fecha'] ?? '';
    final horaEntrada = fichaje['hora_entrada'] ?? '';
    final horaSalida = fichaje['hora_salida'] ?? '';
    final tipo = fichaje['tipo'] ?? '';
    final notas = fichaje['notas'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: tipo == 'entrada' ? Colors.green.shade100 : Colors.orange.shade100,
          child: Icon(
            tipo == 'entrada' ? Icons.login : Icons.logout,
            color: tipo == 'entrada' ? Colors.green : Colors.orange,
          ),
        ),
        title: Text(
          fecha,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (horaEntrada.isNotEmpty)
              Text('Entrada: $horaEntrada'),
            if (horaSalida.isNotEmpty)
              Text('Salida: $horaSalida'),
            if (notas.isNotEmpty)
              Text('📝 $notas', style: TextStyle(color: Colors.grey.shade700)),
          ],
        ),
        isThreeLine: true,
      ),
    );
  }
}
