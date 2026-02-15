import 'package:flutter/material.dart';
import '../models/module_config.dart';
import 'icon_helper.dart';

/// Widget de ListTile dinámico
class DynamicListTile extends StatelessWidget {
  final Map<String, dynamic> item;
  final ModuleConfig config;
  final VoidCallback? onTap;
  final List<ModuleAction> acciones;
  final Function(ModuleAction)? onAction;

  const DynamicListTile({
    super.key,
    required this.item,
    required this.config,
    this.onTap,
    this.acciones = const [],
    this.onAction,
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
        : _getValue('titulo', _getValue('nombre', _getValue('title', 'Sin título')));

    final subtitulo = _getValue(campos['subtitulo']);
    final imagen = _getValue(campos['imagen']);
    final estado = _getValue(campos['estado'] ?? 'estado');

    return ListTile(
      leading: imagen.isNotEmpty
          ? CircleAvatar(
              backgroundImage: NetworkImage(imagen),
              onBackgroundImageError: (_, __) {},
              child: imagen.isEmpty
                  ? Icon(IconHelper.getIcon(config.icono))
                  : null,
            )
          : CircleAvatar(
              backgroundColor: Theme.of(context).primaryColor.withOpacity(0.1),
              child: Icon(
                IconHelper.getIcon(config.icono),
                color: Theme.of(context).primaryColor,
              ),
            ),
      title: Text(
        titulo,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: subtitulo.isNotEmpty
          ? Text(
              subtitulo,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            )
          : null,
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (estado.isNotEmpty)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: IconHelper.getStatusColor(estado).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                IconHelper.getStatusLabel(estado),
                style: TextStyle(
                  fontSize: 12,
                  color: IconHelper.getStatusColor(estado),
                ),
              ),
            ),
          if (acciones.isNotEmpty)
            PopupMenuButton<ModuleAction>(
              icon: const Icon(Icons.more_vert),
              onSelected: onAction,
              itemBuilder: (context) => acciones.map((action) {
                return PopupMenuItem(
                  value: action,
                  child: Row(
                    children: [
                      Icon(IconHelper.getIcon(action.icono), size: 20),
                      const SizedBox(width: 12),
                      Text(action.label),
                    ],
                  ),
                );
              }).toList(),
            )
          else
            const Icon(Icons.chevron_right),
        ],
      ),
      onTap: onTap,
    );
  }
}
