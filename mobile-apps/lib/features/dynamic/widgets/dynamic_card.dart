import 'package:flutter/material.dart';
import '../models/module_config.dart';
import 'icon_helper.dart';

/// Widget de tarjeta dinámica que renderiza items según la configuración
class DynamicCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final ModuleConfig config;
  final VoidCallback? onTap;
  final List<ModuleAction> acciones;
  final Function(ModuleAction)? onAction;

  const DynamicCard({
    super.key,
    required this.item,
    required this.config,
    this.onTap,
    this.acciones = const [],
    this.onAction,
  });

  String _getValue(String? field) {
    if (field == null) return '';

    // Soportar campos anidados con punto
    if (field.contains('.')) {
      final parts = field.split('.');
      dynamic value = item;
      for (final part in parts) {
        if (value is Map) {
          value = value[part];
        } else {
          return '';
        }
      }
      return value?.toString() ?? '';
    }

    // Intentar varias variantes del campo
    final variants = [
      field,
      field.replaceAll('_', ''),
      '${field}_es',
      'nombre',
      'titulo',
      'title',
      'name',
    ];

    for (final variant in variants) {
      if (item[variant] != null) {
        return item[variant].toString();
      }
    }

    return '';
  }

  @override
  Widget build(BuildContext context) {
    final campos = config.campos;

    // Obtener valores de los campos configurados
    final titulo = _getValue(campos['titulo']) .isNotEmpty
        ? _getValue(campos['titulo'])
        : _getValue('titulo') .isNotEmpty
            ? _getValue('titulo')
            : _getValue('nombre');

    final subtitulo = _getValue(campos['subtitulo']);
    final descripcion = _getValue(campos['descripcion']);
    final imagen = _getValue(campos['imagen']);
    final estado = _getValue(campos['estado'] ?? 'estado');
    final fecha = _getValue(campos['fecha']);
    final badge = _getValue(campos['badge']);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            if (imagen.isNotEmpty)
              Stack(
                children: [
                  Image.network(
                    imagen,
                    height: 180,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _buildPlaceholder(),
                  ),
                  // Badge de estado
                  if (estado.isNotEmpty)
                    Positioned(
                      top: 12,
                      right: 12,
                      child: _buildStatusChip(estado),
                    ),
                ],
              )
            else if (estado.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: _buildStatusChip(estado),
              ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Título
                  if (titulo.isNotEmpty)
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),

                  // Subtítulo
                  if (subtitulo.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      subtitulo,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],

                  // Descripción
                  if (descripcion.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      descripcion,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey.shade700,
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],

                  // Fecha y badge
                  if (fecha.isNotEmpty || badge.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        if (fecha.isNotEmpty) ...[
                          Icon(Icons.schedule, size: 16, color: Colors.grey.shade500),
                          const SizedBox(width: 4),
                          Text(
                            _formatDate(fecha),
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                        if (badge.isNotEmpty) ...[
                          const Spacer(),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: Theme.of(context).primaryColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              badge,
                              style: TextStyle(
                                fontSize: 12,
                                color: Theme.of(context).primaryColor,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],

                  // Acciones
                  if (acciones.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      children: acciones
                          .where((a) => a.tipo != 'navigate')
                          .take(2)
                          .map((action) => ActionChip(
                                avatar: Icon(
                                  IconHelper.getIcon(action.icono),
                                  size: 18,
                                ),
                                label: Text(action.label),
                                onPressed: () => onAction?.call(action),
                              ))
                          .toList(),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      height: 180,
      color: Colors.grey.shade200,
      child: Center(
        child: Icon(
          IconHelper.getIcon(config.icono),
          size: 64,
          color: Colors.grey.shade400,
        ),
      ),
    );
  }

  Widget _buildStatusChip(String estado) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: IconHelper.getStatusColor(estado),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        IconHelper.getStatusLabel(estado),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  String _formatDate(String date) {
    try {
      final parsed = DateTime.parse(date);
      return '${parsed.day}/${parsed.month}/${parsed.year}';
    } catch (_) {
      return date;
    }
  }
}
