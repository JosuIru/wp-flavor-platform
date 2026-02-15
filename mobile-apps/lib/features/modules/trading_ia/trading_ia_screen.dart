import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

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
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.trending_up,
                          size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
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
        onTap: () {
          // TODO: Ver detalle de senal
        },
      ),
    );
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
