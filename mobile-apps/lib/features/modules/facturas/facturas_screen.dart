import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';

class FacturasScreen extends ConsumerStatefulWidget {
  const FacturasScreen({super.key});

  @override
  ConsumerState<FacturasScreen> createState() => _FacturasScreenState();
}

class _FacturasScreenState extends ConsumerState<FacturasScreen> {
  List<Map<String, dynamic>> _facturas = [];
  bool _cargando = true;
  String? _error;
  String _filtroEstado = '';

  final List<String> _estados = ['', 'pendiente', 'pagada', 'vencida', 'anulada'];

  @override
  void initState() {
    super.initState();
    _cargarFacturas();
  }

  Future<void> _cargarFacturas() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getFacturas();

      if (response.success && response.data != null) {
        final lista = (response.data!['facturas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        setState(() {
          _facturas = lista;
          _cargando = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar facturas';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  List<Map<String, dynamic>> get _facturasFiltradas {
    if (_filtroEstado.isEmpty) return _facturas;
    return _facturas.where((f) {
      final estado = f['estado']?.toString().toLowerCase() ?? '';
      return estado == _filtroEstado;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Facturas'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarFacturas,
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              children: _estados.map((estado) {
                final seleccionado = _filtroEstado == estado;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(estado.isEmpty ? 'Todas' : _capitalize(estado)),
                    selected: seleccionado,
                    onSelected: (_) {
                      setState(() => _filtroEstado = estado);
                    },
                    selectedColor: _getEstadoColor(estado).withOpacity(0.3),
                  ),
                );
              }).toList(),
            ),
          ),

          // Lista
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_cargando) {
      return const FlavorLoadingState();
    }

    if (_error != null) {
      return FlavorErrorState(
        message: _error!,
        onRetry: _cargarFacturas,
        icon: Icons.receipt_long_outlined,
      );
    }

    final facturas = _facturasFiltradas;

    if (facturas.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.receipt_long_outlined,
        title: _filtroEstado.isEmpty
            ? 'No hay facturas'
            : 'Sin facturas ${_filtroEstado}s',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarFacturas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: facturas.length,
        itemBuilder: (context, index) {
          return _FacturaCard(
            factura: facturas[index],
            onTap: () => _abrirDetalle(facturas[index]),
          );
        },
      ),
    );
  }

  void _abrirDetalle(Map<String, dynamic> factura) async {
    final id = (factura['id'] as num?)?.toInt() ?? 0;
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => FacturaDetailScreen(facturaId: id),
      ),
    );
    if (result == true && mounted) {
      _cargarFacturas();
    }
  }

  String _capitalize(String s) {
    if (s.isEmpty) return s;
    return s[0].toUpperCase() + s.substring(1);
  }

  Color _getEstadoColor(String estado) {
    switch (estado.toLowerCase()) {
      case 'pagada':
        return Colors.green;
      case 'pendiente':
        return Colors.orange;
      case 'vencida':
        return Colors.red;
      case 'anulada':
        return Colors.grey;
      default:
        return Colors.blue;
    }
  }
}

class _FacturaCard extends StatelessWidget {
  final Map<String, dynamic> factura;
  final VoidCallback onTap;

  const _FacturaCard({
    required this.factura,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final id = (factura['id'] as num?)?.toInt() ?? 0;
    final numero = factura['numero_factura']?.toString() ?? '#$id';
    final cliente = factura['cliente_nombre']?.toString() ?? '';
    final total = factura['total']?.toString() ?? '0';
    final estado = factura['estado']?.toString() ?? '';
    final fecha = factura['fecha']?.toString() ?? factura['created_at']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Icono
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: _getEstadoColor(estado).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  Icons.receipt,
                  color: _getEstadoColor(estado),
                ),
              ),
              const SizedBox(width: 16),

              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          numero,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const Spacer(),
                        _EstadoChip(estado: estado),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      cliente,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (fecha.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        _formatFecha(fecha),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ],
                ),
              ),

              const SizedBox(width: 12),

              // Total
              Text(
                '${_formatTotal(total)}€',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: _getEstadoColor(estado),
                ),
              ),

              const SizedBox(width: 8),
              const Icon(Icons.chevron_right, color: Colors.grey),
            ],
          ),
        ),
      ),
    );
  }

  String _formatFecha(String fecha) {
    try {
      final dt = DateTime.parse(fecha);
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (_) {
      return fecha;
    }
  }

  String _formatTotal(String total) {
    try {
      final num = double.parse(total);
      return num.toStringAsFixed(2);
    } catch (_) {
      return total;
    }
  }

  Color _getEstadoColor(String estado) {
    switch (estado.toLowerCase()) {
      case 'pagada':
        return Colors.green;
      case 'pendiente':
        return Colors.orange;
      case 'vencida':
        return Colors.red;
      case 'anulada':
        return Colors.grey;
      default:
        return Colors.blue;
    }
  }
}

class _EstadoChip extends StatelessWidget {
  final String estado;

  const _EstadoChip({required this.estado});

  @override
  Widget build(BuildContext context) {
    final color = _getColor();
    final icon = _getIcon();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            _capitalize(estado),
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Color _getColor() {
    switch (estado.toLowerCase()) {
      case 'pagada':
        return Colors.green;
      case 'pendiente':
        return Colors.orange;
      case 'vencida':
        return Colors.red;
      case 'anulada':
        return Colors.grey;
      default:
        return Colors.blue;
    }
  }

  IconData _getIcon() {
    switch (estado.toLowerCase()) {
      case 'pagada':
        return Icons.check_circle;
      case 'pendiente':
        return Icons.schedule;
      case 'vencida':
        return Icons.warning;
      case 'anulada':
        return Icons.cancel;
      default:
        return Icons.info;
    }
  }

  String _capitalize(String s) {
    if (s.isEmpty) return s;
    return s[0].toUpperCase() + s.substring(1);
  }
}

class FacturaDetailScreen extends ConsumerStatefulWidget {
  final int facturaId;
  const FacturaDetailScreen({super.key, required this.facturaId});

  @override
  ConsumerState<FacturaDetailScreen> createState() => _FacturaDetailScreenState();
}

class _FacturaDetailScreenState extends ConsumerState<FacturaDetailScreen> {
  Map<String, dynamic>? _factura;
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarFactura();
  }

  Future<void> _cargarFactura() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getFactura(widget.facturaId);

      if (response.success && response.data != null) {
        setState(() {
          _factura = response.data!;
          _cargando = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar factura';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  Future<void> _openPdf() async {
    final url = await ref.read(apiClientProvider).getFacturaPdfUrl(widget.facturaId);
    if (!mounted) return;
    await FlavorUrlLauncher.openExternal(
      context,
      url,
      emptyMessage: 'La factura no tiene PDF disponible.',
      errorMessage: 'No se pudo abrir el PDF',
    );
  }

  Future<void> _marcarPagada() async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Marcar como pagada'),
        content: const Text('¿Confirmas que esta factura ha sido pagada?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/facturas/${widget.facturaId}/pagar',
        data: {},
      );

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Factura marcada como pagada');
          _cargarFactura();
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error al actualizar');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de factura'),
        actions: [
          IconButton(
            icon: const Icon(Icons.picture_as_pdf),
            onPressed: _openPdf,
            tooltip: 'Ver PDF',
          ),
        ],
      ),
      body: _buildBody(theme),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_cargando) {
      return const FlavorLoadingState();
    }

    if (_error != null || _factura == null) {
      return FlavorErrorState(
        message: _error ?? 'Error desconocido',
        onRetry: _cargarFactura,
        icon: Icons.receipt_outlined,
      );
    }

    final f = _factura!;
    final numero = f['numero_factura']?.toString() ?? 'Factura';
    final cliente = f['cliente_nombre']?.toString() ?? '';
    final clienteEmail = f['cliente_email']?.toString() ?? '';
    final estado = f['estado']?.toString() ?? '';
    final total = f['total']?.toString() ?? '0';
    final subtotal = f['subtotal']?.toString() ?? '';
    final iva = f['iva']?.toString() ?? '';
    final fecha = f['fecha']?.toString() ?? f['created_at']?.toString() ?? '';
    final fechaVencimiento = f['fecha_vencimiento']?.toString() ?? '';
    final lineas = (f['lineas'] as List<dynamic>?) ?? (f['items'] as List<dynamic>?) ?? [];
    final notas = f['notas']?.toString() ?? '';

    final puedeMarcarPagada = estado.toLowerCase() == 'pendiente' || estado.toLowerCase() == 'vencida';

    return RefreshIndicator(
      onRefresh: _cargarFactura,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Cabecera
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          numero,
                          style: theme.textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      _EstadoChip(estado: estado),
                    ],
                  ),
                  const Divider(height: 24),
                  _InfoRow(label: 'Cliente', value: cliente),
                  if (clienteEmail.isNotEmpty)
                    _InfoRow(label: 'Email', value: clienteEmail),
                  if (fecha.isNotEmpty)
                    _InfoRow(label: 'Fecha', value: _formatFecha(fecha)),
                  if (fechaVencimiento.isNotEmpty)
                    _InfoRow(label: 'Vencimiento', value: _formatFecha(fechaVencimiento)),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Líneas de factura
          if (lineas.isNotEmpty) ...[
            Text(
              'Conceptos',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Card(
              child: Column(
                children: [
                  ...lineas.map((linea) {
                    final l = linea as Map<String, dynamic>;
                    final concepto = l['concepto']?.toString() ?? l['descripcion']?.toString() ?? '';
                    final cantidad = l['cantidad']?.toString() ?? '1';
                    final precio = l['precio']?.toString() ?? l['precio_unitario']?.toString() ?? '0';
                    final subtotalLinea = l['subtotal']?.toString() ?? '';

                    return ListTile(
                      title: Text(concepto),
                      subtitle: Text('$cantidad x ${_formatTotal(precio)}€'),
                      trailing: Text(
                        '${_formatTotal(subtotalLinea.isNotEmpty ? subtotalLinea : precio)}€',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    );
                  }),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],

          // Totales
          Card(
            color: theme.colorScheme.primaryContainer.withOpacity(0.3),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  if (subtotal.isNotEmpty)
                    _TotalRow(label: 'Subtotal', value: '${_formatTotal(subtotal)}€'),
                  if (iva.isNotEmpty)
                    _TotalRow(label: 'IVA', value: '${_formatTotal(iva)}€'),
                  const Divider(),
                  _TotalRow(
                    label: 'Total',
                    value: '${_formatTotal(total)}€',
                    isTotal: true,
                  ),
                ],
              ),
            ),
          ),

          // Notas
          if (notas.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              'Notas',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Text(notas),
              ),
            ),
          ],

          const SizedBox(height: 24),

          // Acciones
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _openPdf,
                  icon: const Icon(Icons.picture_as_pdf),
                  label: const Text('Ver PDF'),
                ),
              ),
              if (puedeMarcarPagada) ...[
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: _marcarPagada,
                    icon: const Icon(Icons.check),
                    label: const Text('Marcar pagada'),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  String _formatFecha(String fecha) {
    try {
      final dt = DateTime.parse(fecha);
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (_) {
      return fecha;
    }
  }

  String _formatTotal(String total) {
    try {
      final num = double.parse(total);
      return num.toStringAsFixed(2);
    } catch (_) {
      return total;
    }
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}

class _TotalRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isTotal;

  const _TotalRow({
    required this.label,
    required this.value,
    this.isTotal = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
              fontSize: isTotal ? 16 : 14,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: isTotal ? 18 : 14,
              color: isTotal ? Theme.of(context).colorScheme.primary : null,
            ),
          ),
        ],
      ),
    );
  }
}
