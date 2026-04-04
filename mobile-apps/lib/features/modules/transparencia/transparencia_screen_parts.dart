part of 'transparencia_screen.dart';

extension _TransparenciaScreenActions on _TransparenciaScreenState {
  void _verDetalleDocumento(BuildContext context, Map<String, dynamic> documento) {
    final titulo = documento['titulo'] ?? documento['title'] ?? 'Documento';
    final descripcion = documento['descripcion'] ?? documento['description'] ?? '';
    final categoria = documento['categoria'] ?? documento['category'] ?? '';
    final fecha = documento['fecha'] ?? documento['date'] ?? '';
    final formato = documento['formato'] ?? documento['format'] ?? 'pdf';
    final descargas = documento['descargas'] ?? documento['downloads'] ?? 0;
    final urlDescarga = documento['url'] ?? documento['url_descarga'] ?? '';
    final tamano = documento['tamano'] ?? documento['size'] ?? '';
    final autor = documento['autor'] ?? documento['author'] ?? '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        minChildSize: 0.3,
        maxChildSize: 0.9,
        expand: false,
        builder: (context, scrollController) => ListView(
          controller: scrollController,
          padding: const EdgeInsets.all(16),
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            Text(
              titulo.toString(),
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: [
                if (categoria.toString().isNotEmpty)
                  Chip(
                    label: Text(categoria.toString()),
                    backgroundColor: Colors.blue.withOpacity(0.1),
                  ),
                Chip(
                  avatar: const Icon(Icons.insert_drive_file, size: 16),
                  label: Text(formato.toString().toUpperCase()),
                ),
              ],
            ),
            const Divider(height: 32),
            if (descripcion.toString().isNotEmpty) ...[
              Text(
                'Descripción',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(descripcion.toString()),
              const SizedBox(height: 16),
            ],
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    if (fecha.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.calendar_today),
                        title: const Text('Fecha de publicación'),
                        subtitle: Text(_formatDate(fecha.toString())),
                      ),
                    if (autor.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.person),
                        title: const Text('Autor'),
                        subtitle: Text(autor.toString()),
                      ),
                    if (tamano.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.storage),
                        title: const Text('Tamaño'),
                        subtitle: Text(tamano.toString()),
                      ),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.download),
                      title: const Text('Descargas'),
                      subtitle: Text('$descargas'),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            if (urlDescarga.toString().isNotEmpty)
              FilledButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _descargarDocumento(urlDescarga.toString(), titulo.toString());
                },
                icon: const Icon(Icons.download),
                label: const Text('Descargar documento'),
              ),
          ],
        ),
      ),
    );
  }
}
