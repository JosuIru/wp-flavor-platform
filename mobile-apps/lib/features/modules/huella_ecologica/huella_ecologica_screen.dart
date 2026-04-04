import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class HuellaEcologicaScreen extends ConsumerStatefulWidget {
  const HuellaEcologicaScreen({super.key});

  @override
  ConsumerState<HuellaEcologicaScreen> createState() => _HuellaEcologicaScreenState();
}

class _HuellaEcologicaScreenState extends ConsumerState<HuellaEcologicaScreen> {
  bool _isLoading = true;
  double _huellaTotal = 0;
  Map<String, double> _categorias = {};
  List<Map<String, dynamic>> _consejos = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/huella-ecologica/mi-huella');
      if (response.success && response.data != null) {
        setState(() {
          _huellaTotal = (response.data!['huella_total'] as num?)?.toDouble() ?? 0;
          _categorias = Map<String, double>.from(response.data!['categorias'] ?? {});
          _consejos = (response.data!['consejos'] as List<dynamic>? ?? [])
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
      appBar: AppBar(
        title: const Text('Huella Ecologica'),
        actions: [
          IconButton(icon: const Icon(Icons.history), onPressed: _verHistorial),
        ],
      ),
      body: _isLoading
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _buildHuellaCard(),
                    const SizedBox(height: 24),
                    _buildCategoriasCard(),
                    const SizedBox(height: 24),
                    _buildConsejosCard(),
                  ],
                ),
              ),
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _calcularHuella,
        icon: const Icon(Icons.calculate),
        label: const Text('Calcular'),
      ),
    );
  }

  Widget _buildHuellaCard() {
    final color = _huellaTotal < 4 ? Colors.green : _huellaTotal < 6 ? Colors.orange : Colors.red;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Text('Tu huella ecologica', style: TextStyle(color: Colors.grey.shade600)),
            const SizedBox(height: 16),
            Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  width: 150,
                  height: 150,
                  child: CircularProgressIndicator(
                    value: _huellaTotal / 10,
                    strokeWidth: 12,
                    backgroundColor: Colors.grey.shade200,
                    color: color,
                  ),
                ),
                Column(
                  children: [
                    Text(
                      _huellaTotal.toStringAsFixed(1),
                      style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: color),
                    ),
                    const Text('toneladas CO2/ano'),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              _huellaTotal < 4 ? 'Excelente!' : _huellaTotal < 6 ? 'Puedes mejorar' : 'Hay trabajo por hacer',
              style: TextStyle(fontWeight: FontWeight.w500, color: color),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoriasCard() {
    final categoriaIcons = {
      'transporte': Icons.directions_car,
      'hogar': Icons.home,
      'alimentacion': Icons.restaurant,
      'consumo': Icons.shopping_bag,
    };

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Por categoria', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 16),
            ..._categorias.entries.map((e) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    children: [
                      Icon(categoriaIcons[e.key] ?? Icons.eco, color: Colors.teal),
                      const SizedBox(width: 12),
                      Expanded(child: Text(e.key[0].toUpperCase() + e.key.substring(1))),
                      Text('${e.value.toStringAsFixed(1)} t'),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildConsejosCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Consejos para reducir', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            if (_consejos.isEmpty)
              const Text('Calcula tu huella para obtener consejos personalizados')
            else
              ..._consejos.take(3).map((c) => ListTile(
                    leading: Icon(Icons.lightbulb, color: Colors.amber.shade600),
                    title: Text(c['titulo'] ?? ''),
                    subtitle: Text(c['descripcion'] ?? ''),
                    contentPadding: EdgeInsets.zero,
                  )),
          ],
        ),
      ),
    );
  }

  void _calcularHuella() {
    int pasoActual = 0;
    final respuestas = <String, double>{};

    final preguntas = [
      {
        'categoria': 'transporte',
        'pregunta': 'Como te desplazas habitualmente?',
        'icono': Icons.directions_car,
        'opciones': [
          {'texto': 'Coche particular', 'valor': 2.5},
          {'texto': 'Transporte publico', 'valor': 0.8},
          {'texto': 'Bicicleta/Andando', 'valor': 0.1},
          {'texto': 'Mixto', 'valor': 1.2},
        ],
      },
      {
        'categoria': 'hogar',
        'pregunta': 'Que tipo de energia usas en casa?',
        'icono': Icons.home,
        'opciones': [
          {'texto': 'Electricidad convencional', 'valor': 1.8},
          {'texto': 'Gas natural', 'valor': 1.5},
          {'texto': 'Energia renovable', 'valor': 0.3},
          {'texto': 'Mixto', 'valor': 1.0},
        ],
      },
      {
        'categoria': 'alimentacion',
        'pregunta': 'Como describirias tu dieta?',
        'icono': Icons.restaurant,
        'opciones': [
          {'texto': 'Alta en carne', 'valor': 2.0},
          {'texto': 'Equilibrada', 'valor': 1.2},
          {'texto': 'Vegetariana', 'valor': 0.6},
          {'texto': 'Vegana', 'valor': 0.4},
        ],
      },
      {
        'categoria': 'consumo',
        'pregunta': 'Como describes tus habitos de consumo?',
        'icono': Icons.shopping_bag,
        'opciones': [
          {'texto': 'Compro frecuentemente', 'valor': 1.5},
          {'texto': 'Compro lo necesario', 'valor': 0.8},
          {'texto': 'Minimalista/Segunda mano', 'valor': 0.3},
          {'texto': 'Consciente/Local', 'valor': 0.5},
        ],
      },
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          final pregunta = preguntas[pasoActual];
          final opciones = pregunta['opciones'] as List<Map<String, dynamic>>;

          return Container(
            height: MediaQuery.of(context).size.height * 0.7,
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                Row(
                  children: [
                    Icon(Icons.eco, color: Colors.teal.shade600),
                    const SizedBox(width: 12),
                    const Text(
                      'Calculadora de Huella',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                // Progreso
                LinearProgressIndicator(
                  value: (pasoActual + 1) / preguntas.length,
                  backgroundColor: Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.teal.shade400),
                ),
                Text(
                  'Paso ${pasoActual + 1} de ${preguntas.length}',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 24),
                // Pregunta
                Icon(pregunta['icono'] as IconData, size: 48, color: Colors.teal.shade400),
                const SizedBox(height: 16),
                Text(
                  pregunta['pregunta'] as String,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w500),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                // Opciones
                Expanded(
                  child: ListView.builder(
                    itemCount: opciones.length,
                    itemBuilder: (context, index) {
                      final opcion = opciones[index];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: ListTile(
                          title: Text(opcion['texto'] as String),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () {
                            respuestas[pregunta['categoria'] as String] =
                                opcion['valor'] as double;

                            if (pasoActual < preguntas.length - 1) {
                              setModalState(() => pasoActual++);
                            } else {
                              // Calcular resultado
                              Navigator.pop(context);
                              _mostrarResultadoCalculo(respuestas);
                            }
                          },
                        ),
                      );
                    },
                  ),
                ),
                if (pasoActual > 0)
                  TextButton.icon(
                    onPressed: () => setModalState(() => pasoActual--),
                    icon: const Icon(Icons.arrow_back),
                    label: const Text('Anterior'),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _mostrarResultadoCalculo(Map<String, double> respuestas) async {
    final total = respuestas.values.fold<double>(0, (sum, val) => sum + val);
    final api = ref.read(apiClientProvider);

    // Guardar resultado
    try {
      await api.post('/huella-ecologica/registrar', data: {
        'huella_total': total,
        'categorias': respuestas,
        'fecha': DateTime.now().toIso8601String(),
      });
    } catch (e) {
      // Ignorar error de guardado
    }

    if (!mounted) return;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.eco, color: Colors.teal.shade600),
            const SizedBox(width: 12),
            const Text('Tu Huella Ecologica'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              '${total.toStringAsFixed(1)} toneladas CO2/ano',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: total < 4 ? Colors.green : total < 6 ? Colors.orange : Colors.red,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              total < 4
                  ? 'Excelente! Tu huella es baja.'
                  : total < 6
                      ? 'Tu huella es moderada. Puedes mejorar.'
                      : 'Tu huella es alta. Hay mucho trabajo por hacer.',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ...respuestas.entries.map((e) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(e.key[0].toUpperCase() + e.key.substring(1)),
                      Text('${e.value.toStringAsFixed(1)} t'),
                    ],
                  ),
                )),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _loadData();
            },
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }

  void _verHistorial() async {
    final api = ref.read(apiClientProvider);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => FutureBuilder(
        future: api.get('/huella-ecologica/historial'),
        builder: (context, snapshot) {
          return DraggableScrollableSheet(
            initialChildSize: 0.7,
            minChildSize: 0.5,
            maxChildSize: 0.95,
            expand: false,
            builder: (context, scrollController) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const FlavorLoadingState();
              }

              final historial = (snapshot.data?.data?['historial'] as List<dynamic>? ?? [])
                  .whereType<Map<String, dynamic>>()
                  .toList();

              return Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        const Icon(Icons.history, color: Colors.teal),
                        const SizedBox(width: 12),
                        const Text(
                          'Historial de mediciones',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: historial.isEmpty
                        ? FlavorEmptyState(
                            icon: Icons.history,
                            title: 'No hay mediciones previas',
                            action: TextButton.icon(
                              onPressed: () {
                                Navigator.pop(context);
                                _calcularHuella();
                              },
                              icon: const Icon(Icons.calculate),
                              label: const Text('Calcular ahora'),
                            ),
                          )
                        : ListView.builder(
                            controller: scrollController,
                            padding: const EdgeInsets.all(16),
                            itemCount: historial.length,
                            itemBuilder: (context, index) {
                              final item = historial[index];
                              final huella = (item['huella_total'] as num?)?.toDouble() ?? 0;
                              final fecha = item['fecha'] ?? '';

                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                child: ListTile(
                                  leading: CircleAvatar(
                                    backgroundColor: huella < 4
                                        ? Colors.green.shade100
                                        : huella < 6
                                            ? Colors.orange.shade100
                                            : Colors.red.shade100,
                                    child: Text(
                                      huella.toStringAsFixed(1),
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: huella < 4
                                            ? Colors.green.shade700
                                            : huella < 6
                                                ? Colors.orange.shade700
                                                : Colors.red.shade700,
                                      ),
                                    ),
                                  ),
                                  title: Text('$huella toneladas CO2/ano'),
                                  subtitle: Text(fecha),
                                ),
                              );
                            },
                          ),
                  ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}
