import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';

class EconomiaSuficienciaScreen extends ConsumerStatefulWidget {
  const EconomiaSuficienciaScreen({super.key});

  @override
  ConsumerState<EconomiaSuficienciaScreen> createState() => _EconomiaSuficienciaScreenState();
}

class _EconomiaSuficienciaScreenState extends ConsumerState<EconomiaSuficienciaScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _miProgreso;
  List<Map<String, dynamic>> _recursos = [];
  List<Map<String, dynamic>> _retos = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/economia-suficiencia/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _miProgreso = response.data!['mi_progreso'];
          _recursos = (response.data!['recursos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _retos = (response.data!['retos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Economia de Suficiencia')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildProgresoCard(),
                    const SizedBox(height: 24),
                    _buildSeccionRetos(),
                    const SizedBox(height: 24),
                    _buildSeccionRecursos(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildProgresoCard() {
    final nivel = _miProgreso?['nivel'] ?? 'Principiante';
    final puntos = _miProgreso?['puntos'] ?? 0;
    final retosCompletados = _miProgreso?['retos_completados'] ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            CircleAvatar(
              radius: 40,
              backgroundColor: Colors.teal.shade100,
              child: Icon(Icons.eco, size: 40, color: Colors.teal.shade600),
            ),
            const SizedBox(height: 16),
            Text('Nivel: $nivel', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildStatItem(Icons.stars, '$puntos pts'),
                const SizedBox(width: 24),
                _buildStatItem(Icons.check_circle, '$retosCompletados retos'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.teal),
        const SizedBox(width: 4),
        Text(text),
      ],
    );
  }

  Widget _buildSeccionRetos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Retos activos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_retos.isEmpty)
          const Text('No hay retos disponibles')
        else
          ..._retos.take(3).map((reto) => _buildRetoCard(reto)),
      ],
    );
  }

  Widget _buildRetoCard(Map<String, dynamic> reto) {
    final retoId = reto['id'];
    final retoTitulo = reto['titulo'] ?? 'Reto';
    final retoDescripcion = reto['descripcion'] ?? '';
    final yaParticipa = reto['participa'] == true || reto['joined'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: yaParticipa ? Colors.green.shade100 : Colors.amber.shade100,
          child: Icon(
            yaParticipa ? Icons.check : Icons.flag,
            color: yaParticipa ? Colors.green.shade700 : Colors.amber.shade700,
          ),
        ),
        title: Text(retoTitulo),
        subtitle: Text(retoDescripcion),
        trailing: yaParticipa
            ? const Chip(label: Text('Participando'))
            : TextButton(
                onPressed: () => _unirseAReto(retoId, retoTitulo),
                child: const Text('Unirse'),
              ),
        onTap: () => _verDetalleReto(reto),
      ),
    );
  }

  void _verDetalleReto(Map<String, dynamic> reto) {
    final retoId = reto['id'];
    final retoTitulo = reto['titulo'] ?? 'Reto';
    final retoDescripcion = reto['descripcion'] ?? '';
    final retoPuntos = reto['puntos'] ?? 0;
    final retoDuracion = reto['duracion'] ?? '';
    final retoParticipantes = reto['participantes'] ?? 0;
    final yaParticipa = reto['participa'] == true || reto['joined'] == true;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
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
                    radius: 24,
                    backgroundColor: Colors.amber.shade100,
                    child: Icon(Icons.flag, color: Colors.amber.shade700),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Text(
                      retoTitulo,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              if (retoDescripcion.isNotEmpty) ...[
                Text(retoDescripcion),
                const SizedBox(height: 16),
              ],
              Wrap(
                spacing: 12,
                runSpacing: 8,
                children: [
                  Chip(
                    avatar: const Icon(Icons.stars, size: 16),
                    label: Text('$retoPuntos puntos'),
                  ),
                  if (retoDuracion.toString().isNotEmpty)
                    Chip(
                      avatar: const Icon(Icons.timer, size: 16),
                      label: Text(retoDuracion.toString()),
                    ),
                  Chip(
                    avatar: const Icon(Icons.people, size: 16),
                    label: Text('$retoParticipantes participantes'),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: yaParticipa
                    ? OutlinedButton.icon(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.check),
                        label: const Text('Ya participas en este reto'),
                      )
                    : FilledButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          _unirseAReto(retoId, retoTitulo);
                        },
                        icon: const Icon(Icons.flag),
                        label: const Text('Unirse al reto'),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _unirseAReto(dynamic retoId, String titulo) async {
    if (retoId == null) return;

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Unirse al reto'),
        content: Text('¿Deseas unirte al reto "$titulo"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Unirme'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/economia-suficiencia/retos/$retoId/unirse');

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Te has unido al reto'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al unirse'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Widget _buildSeccionRecursos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Recursos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_recursos.isEmpty)
          const Text('No hay recursos disponibles')
        else
          ..._recursos.take(5).map((recurso) => ListTile(
                leading: Icon(
                  _getIconoRecurso(recurso['tipo'] ?? ''),
                  color: Colors.teal,
                ),
                title: Text(recurso['titulo'] ?? ''),
                subtitle: Text(recurso['tipo'] ?? ''),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => _verRecurso(recurso),
              )),
      ],
    );
  }

  IconData _getIconoRecurso(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'video':
        return Icons.play_circle;
      case 'pdf':
      case 'documento':
        return Icons.picture_as_pdf;
      case 'guia':
        return Icons.menu_book;
      case 'enlace':
      case 'link':
        return Icons.link;
      default:
        return Icons.article;
    }
  }

  void _verRecurso(Map<String, dynamic> recurso) {
    final recursoId = recurso['id'];
    final titulo = recurso['titulo'] ?? 'Recurso';
    final descripcion = recurso['descripcion'] ?? '';
    final tipo = recurso['tipo'] ?? 'articulo';
    final contenido = recurso['contenido'] ?? '';
    final url = recurso['url'] ?? recurso['enlace'] ?? '';

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
                    backgroundColor: Colors.teal.shade100,
                    child: Icon(_getIconoRecurso(tipo), color: Colors.teal.shade700),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          titulo,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        Text(
                          tipo.toUpperCase(),
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
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
              const SizedBox(height: 20),
              if (descripcion.isNotEmpty) ...[
                Text(
                  'Descripcion',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 8),
                Text(descripcion),
                const SizedBox(height: 16),
              ],
              if (contenido.isNotEmpty) ...[
                Text(
                  'Contenido',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 8),
                Text(contenido),
                const SizedBox(height: 16),
              ],
              if (url.toString().isNotEmpty)
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      Navigator.pop(context);
                      final urlString = url.toString();
                      final uri = Uri.tryParse(
                        urlString.startsWith('http') ? urlString : 'https://$urlString',
                      );
                      if (uri != null && await canLaunchUrl(uri)) {
                        await launchUrl(uri, mode: LaunchMode.externalApplication);
                      } else if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('No se puede abrir el recurso'),
                            backgroundColor: Colors.red,
                          ),
                        );
                      }
                    },
                    icon: const Icon(Icons.open_in_new),
                    label: const Text('Abrir recurso'),
                  ),
                ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => _marcarRecursoCompletado(recursoId, titulo),
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Marcar como completado'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _marcarRecursoCompletado(dynamic recursoId, String titulo) async {
    Navigator.pop(context);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/economia-suficiencia/recursos/$recursoId/completar');

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Recurso "$titulo" completado'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al marcar como completado'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
