import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

/// Pantalla de perfil de socio (usuario)
class SociosScreen extends ConsumerStatefulWidget {
  const SociosScreen({super.key});

  @override
  ConsumerState<SociosScreen> createState() => _SociosScreenState();
}

class _SociosScreenState extends ConsumerState<SociosScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  late Future<ApiResponse<Map<String, dynamic>>> _perfilFuture;
  late Future<ApiResponse<Map<String, dynamic>>> _cuotasFuture;

  final _telefonoController = TextEditingController();
  final _direccionController = TextEditingController();
  bool _editMode = false;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  void _loadData() {
    final api = ref.read(apiClientProvider);
    _perfilFuture = api.getSociosPerfil();
    _cuotasFuture = api.getSociosCuotas();
  }

  Future<void> _refresh() async {
    setState(() {
      _loadData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mi Perfil de Socio'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.person), text: 'Perfil'),
            Tab(icon: Icon(Icons.receipt_long), text: 'Cuotas'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildPerfilTab(theme),
          _buildCuotasTab(theme),
        ],
      ),
    );
  }

  Widget _buildPerfilTab(ThemeData theme) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _perfilFuture,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }

        final res = snapshot.data!;
        if (!res.success || res.data == null) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 48, color: theme.colorScheme.error),
                const SizedBox(height: 16),
                Text(res.error ?? 'Error al cargar perfil'),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: _refresh,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Reintentar'),
                ),
              ],
            ),
          );
        }

        final perfil = res.data!['perfil'] as Map<String, dynamic>? ?? res.data!;
        final nombre = perfil['nombre']?.toString() ?? perfil['display_name']?.toString() ?? 'Usuario';
        final email = perfil['email']?.toString() ?? '';
        final telefono = perfil['telefono']?.toString() ?? '';
        final direccion = perfil['direccion']?.toString() ?? '';
        final numeroSocio = perfil['numero_socio']?.toString() ?? perfil['id']?.toString() ?? '';
        final fechaAlta = perfil['fecha_alta']?.toString() ?? perfil['registered']?.toString() ?? '';
        final estado = perfil['estado']?.toString() ?? 'activo';
        final avatar = perfil['avatar']?.toString();

        // Inicializar controllers solo si no estamos en modo edición
        if (!_editMode) {
          _telefonoController.text = telefono;
          _direccionController.text = direccion;
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Avatar y nombre
              Center(
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 50,
                      backgroundImage: avatar != null ? NetworkImage(avatar) : null,
                      child: avatar == null
                          ? Text(
                              nombre.isNotEmpty ? nombre[0].toUpperCase() : 'U',
                              style: const TextStyle(fontSize: 36),
                            )
                          : null,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      nombre,
                      style: theme.textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (numeroSocio.isNotEmpty)
                      Chip(
                        avatar: const Icon(Icons.badge, size: 16),
                        label: Text('Socio #$numeroSocio'),
                        backgroundColor: theme.colorScheme.primaryContainer,
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Estado del socio
              _buildStatusCard(estado, fechaAlta, theme),
              const SizedBox(height: 16),

              // Datos de contacto
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Datos de contacto',
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (!_editMode)
                            IconButton(
                              icon: const Icon(Icons.edit),
                              onPressed: () => setState(() => _editMode = true),
                            )
                          else
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                IconButton(
                                  icon: const Icon(Icons.close),
                                  onPressed: () {
                                    setState(() {
                                      _editMode = false;
                                      _telefonoController.text = telefono;
                                      _direccionController.text = direccion;
                                    });
                                  },
                                ),
                                IconButton(
                                  icon: _saving
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(strokeWidth: 2),
                                        )
                                      : const Icon(Icons.check),
                                  onPressed: _saving ? null : () => _guardarDatos(),
                                ),
                              ],
                            ),
                        ],
                      ),
                      const Divider(),
                      _buildInfoRow(Icons.email, 'Email', email),
                      const SizedBox(height: 12),
                      if (_editMode) ...[
                        TextField(
                          controller: _telefonoController,
                          decoration: const InputDecoration(
                            labelText: 'Teléfono',
                            prefixIcon: Icon(Icons.phone),
                            border: OutlineInputBorder(),
                          ),
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _direccionController,
                          decoration: const InputDecoration(
                            labelText: 'Dirección',
                            prefixIcon: Icon(Icons.location_on),
                            border: OutlineInputBorder(),
                          ),
                          maxLines: 2,
                        ),
                      ] else ...[
                        _buildInfoRow(Icons.phone, 'Teléfono', telefono.isEmpty ? 'No especificado' : telefono),
                        const SizedBox(height: 12),
                        _buildInfoRow(Icons.location_on, 'Dirección', direccion.isEmpty ? 'No especificada' : direccion),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatusCard(String estado, String fechaAlta, ThemeData theme) {
    Color statusColor;
    IconData statusIcon;

    switch (estado.toLowerCase()) {
      case 'activo':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        break;
      case 'pendiente':
        statusColor = Colors.orange;
        statusIcon = Icons.schedule;
        break;
      case 'baja':
      case 'inactivo':
        statusColor = Colors.red;
        statusIcon = Icons.cancel;
        break;
      default:
        statusColor = Colors.grey;
        statusIcon = Icons.help;
    }

    return Card(
      color: statusColor.withOpacity(0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(statusIcon, color: statusColor, size: 40),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Estado: ${estado.toUpperCase()}',
                    style: theme.textTheme.titleMedium?.copyWith(
                      color: statusColor,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (fechaAlta.isNotEmpty)
                    Text(
                      'Socio desde: ${_formatDate(fechaAlta)}',
                      style: theme.textTheme.bodySmall,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey),
        const SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
            Text(value, style: const TextStyle(fontSize: 14)),
          ],
        ),
      ],
    );
  }

  Future<void> _guardarDatos() async {
    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.updateSociosDatos(
        telefono: _telefonoController.text.trim(),
        direccion: _direccionController.text.trim(),
      );

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Datos actualizados correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          setState(() {
            _editMode = false;
          });
          _refresh();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Widget _buildCuotasTab(ThemeData theme) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _cuotasFuture,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }

        final res = snapshot.data!;
        if (!res.success || res.data == null) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 48, color: theme.colorScheme.error),
                const SizedBox(height: 16),
                Text(res.error ?? 'Error al cargar cuotas'),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: _refresh,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Reintentar'),
                ),
              ],
            ),
          );
        }

        final cuotas = (res.data!['cuotas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        final resumen = res.data!['resumen'] as Map<String, dynamic>? ?? {};

        if (cuotas.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.receipt_long, size: 64, color: theme.colorScheme.outline),
                const SizedBox(height: 16),
                const Text('No hay cuotas registradas'),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Resumen
              if (resumen.isNotEmpty) ...[
                _buildResumenCard(resumen, theme),
                const SizedBox(height: 16),
              ],

              // Lista de cuotas
              Text(
                'Historial de cuotas',
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              ...cuotas.map((cuota) => _buildCuotaItem(cuota, theme)),
            ],
          ),
        );
      },
    );
  }

  Widget _buildResumenCard(Map<String, dynamic> resumen, ThemeData theme) {
    final pendientes = resumen['pendientes'] ?? 0;
    final totalPendiente = (resumen['total_pendiente'] as num?)?.toDouble() ?? 0.0;

    return Card(
      color: pendientes > 0 ? Colors.orange.withOpacity(0.1) : Colors.green.withOpacity(0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(
              pendientes > 0 ? Icons.warning : Icons.check_circle,
              color: pendientes > 0 ? Colors.orange : Colors.green,
              size: 40,
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    pendientes > 0 ? '$pendientes cuota(s) pendiente(s)' : 'Al día con las cuotas',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (totalPendiente > 0)
                    Text(
                      'Total: ${totalPendiente.toStringAsFixed(2)} €',
                      style: theme.textTheme.bodyLarge?.copyWith(
                        color: Colors.orange,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCuotaItem(Map<String, dynamic> cuota, ThemeData theme) {
    final id = (cuota['id'] as num?)?.toInt() ?? 0;
    final periodo = cuota['periodo']?.toString() ?? '';
    final importe = (cuota['importe'] as num?)?.toDouble() ?? 0.0;
    final estado = cuota['estado']?.toString() ?? 'pendiente';
    final fechaPago = cuota['fecha_pago']?.toString();
    final metodoPago = cuota['metodo_pago']?.toString();

    final isPagada = estado.toLowerCase() == 'pagada' || estado.toLowerCase() == 'pagado';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: isPagada ? Colors.green : Colors.orange,
          child: Icon(
            isPagada ? Icons.check : Icons.pending,
            color: Colors.white,
          ),
        ),
        title: Text(periodo),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('${importe.toStringAsFixed(2)} €'),
            if (fechaPago != null && isPagada)
              Text(
                'Pagado: ${_formatDate(fechaPago)}${metodoPago != null ? ' ($metodoPago)' : ''}',
                style: const TextStyle(fontSize: 12),
              ),
          ],
        ),
        trailing: isPagada
            ? const Chip(
                label: Text('Pagada', style: TextStyle(fontSize: 12)),
                backgroundColor: Colors.green,
                labelStyle: TextStyle(color: Colors.white),
              )
            : FilledButton(
                onPressed: () => _pagarCuota(id, importe, periodo),
                child: const Text('Pagar'),
              ),
      ),
    );
  }

  Future<void> _pagarCuota(int cuotaId, double importe, String periodo) async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar pago'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('¿Confirmar pago de la cuota de $periodo?'),
            const SizedBox(height: 8),
            Text(
              'Importe: ${importe.toStringAsFixed(2)} €',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirmar pago'),
          ),
        ],
      ),
    );

    if (confirmado != true) return;

    final api = ref.read(apiClientProvider);
    final response = await api.pagarSociosCuota(cuotaId: cuotaId);

    if (mounted) {
      if (response.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Pago registrado correctamente'),
            backgroundColor: Colors.green,
          ),
        );
        _refresh();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al procesar el pago'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    _telefonoController.dispose();
    _direccionController.dispose();
    super.dispose();
  }
}
