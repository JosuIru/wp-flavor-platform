import 'package:flutter/material.dart';
import '../models/module_config.dart';
import 'icon_helper.dart';

/// Widget de dashboard dinámico con estadísticas
class DynamicDashboard extends StatelessWidget {
  final Map<String, dynamic> data;
  final ModuleConfig config;
  final List<dynamic> items;
  final Function(dynamic)? onItemTap;
  final VoidCallback? onRefresh;

  const DynamicDashboard({
    super.key,
    required this.data,
    required this.config,
    required this.items,
    this.onItemTap,
    this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    // Extraer estadísticas del data
    final stats = _extractStats();

    return RefreshIndicator(
      onRefresh: () async => onRefresh?.call(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Grid de estadísticas
            if (stats.isNotEmpty) ...[
              _buildStatsGrid(context, stats),
              const SizedBox(height: 24),
            ],

            // Acciones rápidas
            if (config.acciones.isNotEmpty) ...[
              Text(
                'Acciones rápidas',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              _buildQuickActions(context),
              const SizedBox(height: 24),
            ],

            // Lista de items recientes
            if (items.isNotEmpty) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Recientes',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  TextButton(
                    onPressed: () {},
                    child: const Text('Ver todos'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ...items.take(5).map((item) => _buildItemTile(context, item)),
            ],
          ],
        ),
      ),
    );
  }

  List<StatItem> _extractStats() {
    final stats = <StatItem>[];

    // Buscar keys comunes de estadísticas
    final statsKeys = [
      'total', 'count', 'cantidad',
      'activos', 'pendientes', 'completados',
      'hoy', 'semana', 'mes',
      'ingresos', 'gastos', 'balance',
    ];

    // Buscar en data y en data['stats'] o data['resumen']
    final sources = [
      data,
      data['stats'] as Map<String, dynamic>? ?? {},
      data['resumen'] as Map<String, dynamic>? ?? {},
      data['dashboard'] as Map<String, dynamic>? ?? {},
    ];

    for (final source in sources) {
      for (final entry in source.entries) {
        if (entry.value is num || entry.value is int || entry.value is double) {
          // Solo incluir si parece una estadística
          final key = entry.key.toLowerCase();
          if (_isStatKey(key)) {
            stats.add(StatItem(
              label: _formatLabel(entry.key),
              value: entry.value,
              icon: _getIconForStat(key),
              color: _getColorForStat(key),
            ));
          }
        }
      }
    }

    return stats.take(4).toList();
  }

  bool _isStatKey(String key) {
    final statPatterns = [
      'total', 'count', 'cantidad', 'num',
      'activo', 'pendiente', 'completado',
      'hoy', 'semana', 'mes', 'anual',
      'ingreso', 'gasto', 'balance', 'saldo',
      'nuevo', 'cerrado', 'abierto',
    ];
    return statPatterns.any((p) => key.contains(p));
  }

  String _formatLabel(String key) {
    return key
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .split(' ')
        .map((word) => word.isNotEmpty
            ? '${word[0].toUpperCase()}${word.substring(1)}'
            : '')
        .join(' ');
  }

  String _getIconForStat(String key) {
    if (key.contains('total') || key.contains('count')) return 'numbers';
    if (key.contains('activo')) return 'check_circle';
    if (key.contains('pendiente')) return 'pending';
    if (key.contains('completado')) return 'task_alt';
    if (key.contains('ingreso')) return 'trending_up';
    if (key.contains('gasto')) return 'trending_down';
    if (key.contains('balance') || key.contains('saldo')) return 'account_balance_wallet';
    if (key.contains('hoy')) return 'today';
    if (key.contains('semana')) return 'date_range';
    if (key.contains('mes')) return 'calendar_month';
    return 'analytics';
  }

  Color _getColorForStat(String key) {
    if (key.contains('activo') || key.contains('completado') || key.contains('ingreso')) {
      return Colors.green;
    }
    if (key.contains('pendiente')) return Colors.orange;
    if (key.contains('gasto') || key.contains('cancelado')) return Colors.red;
    if (key.contains('nuevo')) return Colors.blue;
    return Colors.indigo;
  }

  Widget _buildStatsGrid(BuildContext context, List<StatItem> stats) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: 1.5,
      ),
      itemCount: stats.length,
      itemBuilder: (context, index) {
        final stat = stats[index];
        return _buildStatCard(context, stat);
      },
    );
  }

  Widget _buildStatCard(BuildContext context, StatItem stat) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: stat.color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    IconHelper.getIcon(stat.icon),
                    color: stat.color,
                    size: 20,
                  ),
                ),
                const Spacer(),
              ],
            ),
            const Spacer(),
            Text(
              _formatValue(stat.value),
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              stat.label,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  String _formatValue(dynamic value) {
    if (value is double) {
      if (value >= 1000000) {
        return '${(value / 1000000).toStringAsFixed(1)}M';
      }
      if (value >= 1000) {
        return '${(value / 1000).toStringAsFixed(1)}K';
      }
      return value.toStringAsFixed(2);
    }
    if (value is int) {
      if (value >= 1000000) {
        return '${(value / 1000000).toStringAsFixed(1)}M';
      }
      if (value >= 1000) {
        return '${(value / 1000).toStringAsFixed(1)}K';
      }
    }
    return value.toString();
  }

  Widget _buildQuickActions(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: config.acciones.map((action) {
          return Padding(
            padding: const EdgeInsets.only(right: 12),
            child: ActionChip(
              avatar: Icon(IconHelper.getIcon(action.icono), size: 18),
              label: Text(action.label),
              onPressed: () {},
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildItemTile(BuildContext context, dynamic item) {
    final map = item as Map<String, dynamic>;
    final titulo = map['titulo'] ?? map['nombre'] ?? map['title'] ?? 'Sin título';
    final subtitulo = map['descripcion'] ?? map['fecha'] ?? '';
    final estado = map['estado']?.toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).primaryColor.withOpacity(0.1),
          child: Icon(
            IconHelper.getIcon(config.icono),
            color: Theme.of(context).primaryColor,
          ),
        ),
        title: Text(
          titulo.toString(),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: subtitulo.toString().isNotEmpty
            ? Text(
                subtitulo.toString(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              )
            : null,
        trailing: estado != null
            ? Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  color: IconHelper.getStatusColor(estado),
                  shape: BoxShape.circle,
                ),
              )
            : const Icon(Icons.chevron_right),
        onTap: () => onItemTap?.call(item),
      ),
    );
  }
}

class StatItem {
  final String label;
  final dynamic value;
  final String icon;
  final Color color;

  StatItem({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });
}
