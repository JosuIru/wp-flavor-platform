import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';
import 'camp_inscription_form_screen.dart';

/// Pantalla de detalle de un campamento
class CampDetailScreen extends ConsumerStatefulWidget {
  final int campId;

  const CampDetailScreen({
    super.key,
    required this.campId,
  });

  @override
  ConsumerState<CampDetailScreen> createState() => _CampDetailScreenState();
}

class _CampDetailScreenState extends ConsumerState<CampDetailScreen> {
  Camp? _camp;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadCampDetail();
  }

  Future<void> _loadCampDetail() async {
    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getCampDetail(widget.campId);

    if (response.success && response.data != null) {
      setState(() {
        _camp = Camp.fromJson(response.data!['camp']);
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al cargar campamento'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _goToInscription() {
    if (_camp == null) return;

    if (_camp!.isClosed) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Las inscripciones están cerradas'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    if (_camp!.isFull) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('El campamento está completo'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CampInscriptionFormScreen(camp: _camp!),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_camp == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(
          child: Text('No se encontró el campamento'),
        ),
      );
    }

    final camp = _camp!;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // AppBar con imagen
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                camp.title,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  shadows: [
                    Shadow(
                      offset: Offset(0, 1),
                      blurRadius: 3,
                      color: Colors.black54,
                    ),
                  ],
                ),
              ),
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (camp.featuredImage.isNotEmpty)
                    CachedNetworkImage(
                      imageUrl: camp.featuredImage,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(
                        color: Colors.grey[300],
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: Colors.grey[300],
                        child: const Icon(Icons.image_not_supported, size: 64),
                      ),
                    )
                  else
                    Container(
                      color: Colors.grey[300],
                      child: const Icon(Icons.event, size: 64),
                    ),
                  // Gradiente oscuro en la parte inferior
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                        stops: const [0.5, 1.0],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Contenido
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Estado y etiquetas
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      // Estado
                      Chip(
                        label: Text(camp.statusText),
                        backgroundColor: camp.isClosed
                            ? Colors.red[100]
                            : camp.isFull
                                ? Colors.orange[100]
                                : Colors.green[100],
                        avatar: Icon(
                          camp.isClosed
                              ? Icons.block
                              : camp.isFull
                                  ? Icons.people
                                  : Icons.check_circle,
                          size: 18,
                          color: camp.isClosed
                              ? Colors.red[900]
                              : camp.isFull
                                  ? Colors.orange[900]
                                  : Colors.green[900],
                        ),
                      ),
                      // Etiqueta especial
                      if (camp.label != null && camp.label!.isNotEmpty)
                        Chip(
                          label: Text(camp.label!),
                          backgroundColor: Colors.blue[100],
                        ),
                      // Plazas disponibles
                      if (!camp.isClosed && !camp.isFull)
                        Chip(
                          label: Text('${camp.availablePlaces} plazas'),
                          backgroundColor: Colors.grey[200],
                        ),
                    ],
                  ),
                ),

                // Información básica
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Categorías
                      if (camp.categories.isNotEmpty) ...[
                        _InfoRow(
                          icon: Icons.category,
                          label: 'Categoría',
                          value: camp.categoriesText,
                        ),
                        const SizedBox(height: 12),
                      ],

                      // Edades
                      if (camp.ages.isNotEmpty) ...[
                        _InfoRow(
                          icon: Icons.child_care,
                          label: 'Edades',
                          value: camp.agesText,
                        ),
                        const SizedBox(height: 12),
                      ],

                      // Idiomas
                      if (camp.languages.isNotEmpty) ...[
                        _InfoRow(
                          icon: Icons.language,
                          label: 'Idiomas',
                          value: camp.languagesText,
                        ),
                        const SizedBox(height: 12),
                      ],

                      // Duración
                      _InfoRow(
                        icon: Icons.schedule,
                        label: 'Duración',
                        value: camp.duration,
                      ),
                      const SizedBox(height: 12),

                      // Fechas
                      if (camp.dates != null) ...[
                        _InfoRow(
                          icon: Icons.calendar_today,
                          label: 'Fechas',
                          value: '${camp.dates!.start} - ${camp.dates!.end}',
                        ),
                        const SizedBox(height: 12),
                      ],
                    ],
                  ),
                ),

                const Divider(height: 32),

                // Precios
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Precios',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Inscripción',
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                camp.formattedPrice,
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                'Total',
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                camp.formattedPriceTotal,
                                style: Theme.of(context)
                                    .textTheme
                                    .titleLarge
                                    ?.copyWith(
                                      fontWeight: FontWeight.bold,
                                      color: Theme.of(context).primaryColor,
                                    ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                const Divider(height: 32),

                // Descripción
                if (camp.description != null &&
                    camp.description!.isNotEmpty) ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Descripción',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          camp.description!,
                          style: const TextStyle(height: 1.5),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 32),
                ],

                // Información adicional en cards
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    children: [
                      if (camp.dates?.schedule != null &&
                          camp.dates!.schedule!.isNotEmpty)
                        _InfoCard(
                          icon: Icons.access_time,
                          title: 'Horario',
                          content: camp.dates!.schedule!,
                        ),
                      if (camp.dates?.location != null &&
                          camp.dates!.location!.isNotEmpty)
                        _InfoCard(
                          icon: Icons.location_on,
                          title: 'Ubicación',
                          content: camp.dates!.location!,
                        ),
                      if (camp.dates?.includes != null &&
                          camp.dates!.includes!.isNotEmpty)
                        _InfoCard(
                          icon: Icons.check_circle,
                          title: 'Qué incluye',
                          content: camp.dates!.includes!,
                        ),
                      if (camp.dates?.requirements != null &&
                          camp.dates!.requirements!.isNotEmpty)
                        _InfoCard(
                          icon: Icons.info,
                          title: 'Requisitos',
                          content: camp.dates!.requirements!,
                        ),
                    ],
                  ),
                ),

                const SizedBox(height: 100), // Espacio para el botón flotante
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 8,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: SafeArea(
          child: FilledButton.icon(
            onPressed: camp.isClosed || camp.isFull ? null : _goToInscription,
            icon: const Icon(Icons.app_registration),
            label: Text(
              camp.isClosed
                  ? 'Inscripciones cerradas'
                  : camp.isFull
                      ? 'Completo'
                      : 'Inscribirse',
            ),
            style: FilledButton.styleFrom(
              padding: const EdgeInsets.all(16),
              backgroundColor: camp.isClosed || camp.isFull
                  ? Colors.grey
                  : Theme.of(context).primaryColor,
            ),
          ),
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey[600]),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                ),
              ),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _InfoCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String content;

  const _InfoCard({
    required this.icon,
    required this.title,
    required this.content,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: Theme.of(context).primaryColor),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              content,
              style: const TextStyle(height: 1.5),
            ),
          ],
        ),
      ),
    );
  }
}
