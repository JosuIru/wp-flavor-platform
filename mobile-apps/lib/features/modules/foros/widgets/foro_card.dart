import 'package:flutter/material.dart';

class ForoCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onTap;

  const ForoCard({
    super.key,
    required this.item,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final tituloForo =
        item['titulo'] ?? item['nombre'] ?? item['title'] ?? 'Sin titulo';
    final descripcionForo = item['descripcion'] ?? item['description'] ?? '';
    final autorForo = item['autor'] ?? item['author'] ?? item['usuario'] ?? '';
    final fechaCreacion =
        item['fecha'] ?? item['created_at'] ?? item['fecha_creacion'] ?? '';
    final totalRespuestas =
        item['respuestas'] ?? item['replies'] ?? item['comentarios'] ?? 0;
    final totalVistas = item['vistas'] ?? item['views'] ?? 0;
    final categoriaForo = item['categoria'] ?? item['category'] ?? '';
    final esFijado = item['fijado'] ?? item['pinned'] ?? false;
    final estaCerrado = item['cerrado'] ?? item['closed'] ?? false;
    final ultimaActividad =
        item['ultima_actividad'] ?? item['last_activity'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    backgroundColor: Colors.purple.shade100,
                    child: Icon(
                      esFijado ? Icons.push_pin : Icons.forum,
                      color: Colors.purple.shade700,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            if (esFijado)
                              Container(
                                margin: const EdgeInsets.only(right: 8),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 6,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.orange.shade100,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  'FIJADO',
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.orange.shade800,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            if (estaCerrado)
                              Container(
                                margin: const EdgeInsets.only(right: 8),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 6,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.red.shade100,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  'CERRADO',
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.red.shade800,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            if (categoriaForo.isNotEmpty)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 6,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.purple.shade50,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  categoriaForo,
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.purple.shade700,
                                  ),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          tituloForo,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        if (descripcionForo.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            descripcionForo,
                            style: TextStyle(color: Colors.grey.shade600),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            if (autorForo.isNotEmpty) ...[
                              Icon(
                                Icons.person,
                                size: 14,
                                color: Colors.grey.shade500,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                autorForo,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              const SizedBox(width: 12),
                            ],
                            if (fechaCreacion.isNotEmpty) ...[
                              Icon(
                                Icons.access_time,
                                size: 14,
                                color: Colors.grey.shade500,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                fechaCreacion,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ],
                        ),
                        if (ultimaActividad.toString().isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Actividad reciente: ${ultimaActividad.toString()}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade700,
                              fontStyle: FontStyle.italic,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _ForoStat(
                    icon: Icons.reply,
                    value: totalRespuestas.toString(),
                    label: 'Respuestas',
                  ),
                  _ForoStat(
                    icon: Icons.visibility,
                    value: totalVistas.toString(),
                    label: 'Vistas',
                  ),
                  const Icon(Icons.chevron_right, color: Colors.grey),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ForoStat extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;

  const _ForoStat({
    required this.icon,
    required this.value,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 6),
        Text(
          value,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
        ),
      ],
    );
  }
}
