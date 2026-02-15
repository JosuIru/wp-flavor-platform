import 'package:flutter/material.dart';
import '../models/module_config.dart';
import 'icon_helper.dart';

/// Widget de grid item dinámico
class DynamicGridItem extends StatelessWidget {
  final Map<String, dynamic> item;
  final ModuleConfig config;
  final VoidCallback? onTap;

  const DynamicGridItem({
    super.key,
    required this.item,
    required this.config,
    this.onTap,
  });

  String _getValue(String? field, [String defaultValue = '']) {
    if (field == null) return defaultValue;
    return item[field]?.toString() ??
           item[field.replaceAll('_', '')]?.toString() ??
           defaultValue;
  }

  @override
  Widget build(BuildContext context) {
    final campos = config.campos;

    final titulo = _getValue(campos['titulo']) .isNotEmpty
        ? _getValue(campos['titulo'])
        : _getValue('titulo', _getValue('nombre', 'Sin título'));

    final imagen = _getValue(campos['imagen']);
    final estado = _getValue(campos['estado'] ?? 'estado');
    final badge = _getValue(campos['badge']);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen o placeholder
            Expanded(
              flex: 3,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (imagen.isNotEmpty)
                    Image.network(
                      imagen,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _buildPlaceholder(context),
                    )
                  else
                    _buildPlaceholder(context),

                  // Estado badge
                  if (estado.isNotEmpty)
                    Positioned(
                      top: 8,
                      right: 8,
                      child: Container(
                        width: 12,
                        height: 12,
                        decoration: BoxDecoration(
                          color: IconHelper.getStatusColor(estado),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // Info
            Expanded(
              flex: 2,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (badge.isNotEmpty) ...[
                      const Spacer(),
                      Text(
                        badge,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholder(BuildContext context) {
    return Container(
      color: Colors.grey.shade200,
      child: Center(
        child: Icon(
          IconHelper.getIcon(config.icono),
          size: 48,
          color: Colors.grey.shade400,
        ),
      ),
    );
  }
}
