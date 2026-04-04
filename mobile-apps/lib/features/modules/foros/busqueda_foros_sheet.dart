import 'package:flutter/material.dart';

class BusquedaForosSheet extends StatefulWidget {
  final List<dynamic> foros;
  final ValueChanged<dynamic> onSelect;

  const BusquedaForosSheet({
    super.key,
    required this.foros,
    required this.onSelect,
  });

  @override
  State<BusquedaForosSheet> createState() => _BusquedaForosSheetState();
}

class _BusquedaForosSheetState extends State<BusquedaForosSheet> {
  final _controladorBusqueda = TextEditingController();
  late List<dynamic> _resultadosFiltrados;

  @override
  void initState() {
    super.initState();
    _resultadosFiltrados = List.from(widget.foros);
  }

  @override
  void dispose() {
    _controladorBusqueda.dispose();
    super.dispose();
  }

  void _filtrar(String textoBusqueda) {
    final busquedaMinusculas = textoBusqueda.toLowerCase();
    setState(() {
      if (textoBusqueda.isEmpty) {
        _resultadosFiltrados = List.from(widget.foros);
      } else {
        _resultadosFiltrados = widget.foros.where((foro) {
          final mapaForo = foro as Map<String, dynamic>;
          final titulo =
              (mapaForo['titulo'] ?? mapaForo['nombre'] ?? '')
                  .toString()
                  .toLowerCase();
          final descripcion =
              (mapaForo['descripcion'] ?? '').toString().toLowerCase();
          final autor = (mapaForo['autor'] ?? '').toString().toLowerCase();
          final categoria =
              (mapaForo['categoria'] ?? '').toString().toLowerCase();
          return titulo.contains(busquedaMinusculas) ||
              descripcion.contains(busquedaMinusculas) ||
              autor.contains(busquedaMinusculas) ||
              categoria.contains(busquedaMinusculas);
        }).toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.85,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) => Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    Icon(Icons.search, color: Colors.purple.shade400),
                    const SizedBox(width: 12),
                    const Text(
                      'Buscar en foros',
                      style:
                          TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _controladorBusqueda,
                  autofocus: true,
                  decoration: InputDecoration(
                    hintText: 'Escribe para buscar...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _controladorBusqueda.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _controladorBusqueda.clear();
                              _filtrar('');
                            },
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onChanged: _filtrar,
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: _resultadosFiltrados.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.search_off,
                          size: 48,
                          color: Colors.grey.shade400,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _controladorBusqueda.text.isEmpty
                              ? 'No hay foros'
                              : 'Sin resultados para "${_controladorBusqueda.text}"',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  )
                : ListView.separated(
                    controller: scrollController,
                    padding: const EdgeInsets.all(16),
                    itemCount: _resultadosFiltrados.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, indice) {
                      final foro =
                          _resultadosFiltrados[indice] as Map<String, dynamic>;
                      final tituloForo =
                          foro['titulo'] ?? foro['nombre'] ?? 'Sin titulo';
                      final categoriaForo = foro['categoria'] ?? '';
                      final autorForo = foro['autor'] ?? '';
                      final respuestasForo = foro['respuestas'] ?? 0;

                      return ListTile(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        tileColor: Colors.grey.shade100,
                        leading: CircleAvatar(
                          backgroundColor: Colors.purple.shade100,
                          child: Icon(
                            Icons.forum,
                            color: Colors.purple.shade700,
                            size: 20,
                          ),
                        ),
                        title: Text(
                          tituloForo,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        subtitle: Row(
                          children: [
                            if (categoriaForo.isNotEmpty) ...[
                              Text(
                                categoriaForo,
                                style: const TextStyle(fontSize: 12),
                              ),
                              const Text(
                                ' • ',
                                style: TextStyle(fontSize: 12),
                              ),
                            ],
                            if (autorForo.isNotEmpty)
                              Text(
                                autorForo,
                                style: const TextStyle(fontSize: 12),
                              ),
                            if (respuestasForo > 0) ...[
                              const Text(
                                ' • ',
                                style: TextStyle(fontSize: 12),
                              ),
                              Text(
                                '$respuestasForo resp.',
                                style: const TextStyle(fontSize: 12),
                              ),
                            ],
                          ],
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => widget.onSelect(foro),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
