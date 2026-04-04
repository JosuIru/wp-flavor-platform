import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class TradingIaScreen extends ConsumerStatefulWidget {
  const TradingIaScreen({super.key});

  @override
  ConsumerState<TradingIaScreen> createState() => _TradingIaScreenState();
}

class _TradingIaScreenState extends ConsumerState<TradingIaScreen> {
  Map<String, dynamic> _dashboard = {};
  List<dynamic> _senales = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/trading-ia/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _dashboard = response.data!;
          _senales = response.data!['senales'] ??
              response.data!['signals'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar dashboard';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Trading IA'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  icon: Icons.trending_up,
                  onRetry: _loadData,
                )
              : RefreshIndicator(
                  onRefresh: _loadData,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildResumenCard(),
                        const SizedBox(height: 16),
                        const Text(
                          'Senales de Trading',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        if (_senales.isEmpty)
                          Center(
                            child: Padding(
                              padding: const EdgeInsets.all(32),
                              child: Column(
                                children: [
                                  Icon(Icons.trending_up,
                                      size: 48, color: Colors.grey.shade400),
                                  const SizedBox(height: 8),
                                  const Text('No hay senales activas'),
                                ],
                              ),
                            ),
                          )
                        else
                          ...List.generate(
                            _senales.length,
                            (index) => _buildSenalCard(_senales[index]),
                          ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildResumenCard() {
    final balance = _dashboard['balance'] ??
        _dashboard['saldo'] ??
        0;
    final rendimiento = _dashboard['rendimiento'] ??
        _dashboard['performance'] ??
        _dashboard['profit'] ??
        0;
    final operacionesActivas = _dashboard['operaciones_activas'] ??
        _dashboard['active_trades'] ??
        0;
    final winRate = _dashboard['win_rate'] ??
        _dashboard['tasa_exito'] ??
        0;

    final rendimientoPositivo = rendimiento is num && rendimiento >= 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.blue.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.trending_up, color: Colors.blue),
                ),
                const SizedBox(width: 12),
                const Text(
                  'Resumen de Trading',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildStatItem(
                    'Balance',
                    '\$${_formatNumber(balance)}',
                    Icons.account_balance_wallet,
                    Colors.blue,
                  ),
                ),
                Expanded(
                  child: _buildStatItem(
                    'Rendimiento',
                    '${rendimientoPositivo ? '+' : ''}${_formatNumber(rendimiento)}%',
                    rendimientoPositivo
                        ? Icons.arrow_upward
                        : Icons.arrow_downward,
                    rendimientoPositivo ? Colors.green : Colors.red,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildStatItem(
                    'Operaciones',
                    operacionesActivas.toString(),
                    Icons.swap_horiz,
                    Colors.orange,
                  ),
                ),
                Expanded(
                  child: _buildStatItem(
                    'Win Rate',
                    '${_formatNumber(winRate)}%',
                    Icons.emoji_events,
                    Colors.purple,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(
      String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.05),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 4),
              Text(
                label,
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSenalCard(dynamic item) {
    final senalMap = item as Map<String, dynamic>;
    final simbolo = senalMap['simbolo'] ??
        senalMap['symbol'] ??
        senalMap['par'] ??
        'N/A';
    final tipo = senalMap['tipo'] ??
        senalMap['type'] ??
        senalMap['accion'] ??
        'hold';
    final precio = senalMap['precio'] ??
        senalMap['price'] ??
        senalMap['entrada'] ??
        0;
    final confianza = senalMap['confianza'] ??
        senalMap['confidence'] ??
        senalMap['probabilidad'] ??
        0;
    final descripcion = senalMap['descripcion'] ??
        senalMap['description'] ??
        senalMap['razon'] ??
        '';

    Color tipoColor;
    IconData tipoIcon;
    switch (tipo.toString().toLowerCase()) {
      case 'compra':
      case 'buy':
      case 'long':
        tipoColor = Colors.green;
        tipoIcon = Icons.arrow_upward;
        break;
      case 'venta':
      case 'sell':
      case 'short':
        tipoColor = Colors.red;
        tipoIcon = Icons.arrow_downward;
        break;
      default:
        tipoColor = Colors.grey;
        tipoIcon = Icons.remove;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: tipoColor.withOpacity(0.1),
          child: Icon(tipoIcon, color: tipoColor),
        ),
        title: Row(
          children: [
            Text(
              simbolo.toString(),
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: tipoColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                tipo.toString().toUpperCase(),
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: tipoColor,
                ),
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text('Precio: \$${_formatNumber(precio)}'),
                const SizedBox(width: 12),
                Text('Confianza: ${_formatNumber(confianza)}%'),
              ],
            ),
            if (descripcion.toString().isNotEmpty)
              Text(
                descripcion.toString(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: Colors.grey[600]),
              ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => _verDetalleSenial(senalMap),
      ),
    );
  }

  void _verDetalleSenial(Map<String, dynamic> senal) {
    final simbolo = senal['simbolo'] ?? senal['symbol'] ?? senal['par'] ?? 'N/A';
    final tipo = senal['tipo'] ?? senal['type'] ?? senal['accion'] ?? 'hold';
    final precio = senal['precio'] ?? senal['price'] ?? senal['entrada'] ?? 0;
    final confianza = senal['confianza'] ?? senal['confidence'] ?? senal['probabilidad'] ?? 0;
    final descripcion = senal['descripcion'] ?? senal['description'] ?? senal['razon'] ?? '';
    final takeProfit = senal['take_profit'] ?? senal['tp'] ?? '';
    final stopLoss = senal['stop_loss'] ?? senal['sl'] ?? '';
    final timestamp = senal['timestamp'] ?? senal['fecha'] ?? senal['created_at'] ?? '';
    final indicadores = senal['indicadores'] ?? senal['indicators'] ?? <String, dynamic>{};

    Color tipoColor;
    IconData tipoIcon;
    switch (tipo.toString().toLowerCase()) {
      case 'compra':
      case 'buy':
      case 'long':
        tipoColor = Colors.green;
        tipoIcon = Icons.arrow_upward;
        break;
      case 'venta':
      case 'sell':
      case 'short':
        tipoColor = Colors.red;
        tipoIcon = Icons.arrow_downward;
        break;
      default:
        tipoColor = Colors.grey;
        tipoIcon = Icons.remove;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: tipoColor.withOpacity(0.1),
                    child: Icon(tipoIcon, color: tipoColor, size: 28),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          simbolo.toString(),
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            color: tipoColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Text(
                            tipo.toString().toUpperCase(),
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: tipoColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildDetalleRow(Icons.attach_money, 'Precio entrada', '\$${_formatNumber(precio)}'),
                      const Divider(),
                      _buildDetalleRow(Icons.analytics, 'Confianza', '${_formatNumber(confianza)}%'),
                      if (takeProfit.toString().isNotEmpty) ...[
                        const Divider(),
                        _buildDetalleRow(Icons.trending_up, 'Take Profit', '\$${_formatNumber(takeProfit)}'),
                      ],
                      if (stopLoss.toString().isNotEmpty) ...[
                        const Divider(),
                        _buildDetalleRow(Icons.trending_down, 'Stop Loss', '\$${_formatNumber(stopLoss)}'),
                      ],
                      if (timestamp.toString().isNotEmpty) ...[
                        const Divider(),
                        _buildDetalleRow(Icons.schedule, 'Fecha', timestamp.toString()),
                      ],
                    ],
                  ),
                ),
              ),
              if (descripcion.toString().isNotEmpty) ...[
                const SizedBox(height: 16),
                const Text('Analisis', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 8),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(descripcion.toString()),
                  ),
                ),
              ],
              if (indicadores is Map && indicadores.isNotEmpty) ...[
                const SizedBox(height: 16),
                const Text('Indicadores', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 8),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: (indicadores as Map<String, dynamic>).entries.map((entry) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(entry.key, style: TextStyle(color: Colors.grey.shade600)),
                              Text(_formatNumber(entry.value), style: const TextStyle(fontWeight: FontWeight.w500)),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        FlavorSnackbar.showInfo(context, 'Senal $simbolo ignorada');
                      },
                      icon: const Icon(Icons.close),
                      label: const Text('Ignorar'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () => _ejecutarSenal(senal),
                      icon: Icon(tipoIcon),
                      label: Text(tipo.toString().toUpperCase()),
                      style: FilledButton.styleFrom(backgroundColor: tipoColor),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetalleRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  Future<void> _ejecutarSenal(Map<String, dynamic> senal) async {
    Navigator.pop(context);
    final simbolo = senal['simbolo'] ?? senal['symbol'] ?? 'N/A';
    final tipo = senal['tipo'] ?? senal['type'] ?? 'compra';
    final senalId = senal['id'];

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Ejecutar ${tipo.toString().toUpperCase()}'),
        content: Text('¿Confirmar operacion de ${tipo.toString().toLowerCase()} en $simbolo?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Ejecutar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post('/trading-ia/ejecutar', data: {
        'senal_id': senalId,
        'simbolo': simbolo,
        'tipo': tipo,
      });

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Orden de $tipo ejecutada para $simbolo');
          _loadData();
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al ejecutar orden');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  String _formatNumber(dynamic number) {
    if (number is num) {
      if (number == number.toInt()) {
        return number.toInt().toString();
      }
      return number.toStringAsFixed(2);
    }
    return number.toString();
  }
}
