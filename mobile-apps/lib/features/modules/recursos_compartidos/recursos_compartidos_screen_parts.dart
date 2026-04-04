part of 'recursos_compartidos_screen.dart';

extension _RecursosCompartidosScreenParts on _RecursosCompartidosScreenState {
  Future<void> _openResourceDetail(Map<String, dynamic> item) async {
    await _markAsRecent(item);
    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _RecursoCompartidoDetailScreen(
          item: item,
          onOpenInApp: _canOpenInApp(item) ? () => _openResourceInApp(item) : null,
          onOpenExternal: () => _openResource(item),
          onCopyLink: () => _copyResourceLink(item),
          onToggleFavorite: () => _toggleFavorite(item),
          isFavorite: _favoriteIds.contains(_itemId(item)),
        ),
      ),
    );
  }

  Future<void> _openResourceInApp(Map<String, dynamic> item) async {
    await _markAsRecent(item);
    final rawUrl = item['url']?.toString() ?? '';
    if (!_canOpenInApp(item)) {
      await _openResource(item);
      return;
    }

    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => FlavorWebViewPage(
          title: item['title']?.toString() ?? 'Recurso',
          url: rawUrl,
          showLoadingOverlay: true,
          actionsBuilder: (context, controller) => [
            IconButton(
              icon: const Icon(Icons.open_in_new),
              onPressed: () => _openResource(item),
            ),
            IconButton(
              icon: const Icon(Icons.copy_outlined),
              onPressed: () => _copyResourceLink(item),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openResource(Map<String, dynamic> item) async {
    await _markAsRecent(item);
    if (!mounted) return;
    final rawUrl = item['url']?.toString() ?? '';
    await FlavorUrlLauncher.openExternal(
      context,
      rawUrl,
      emptyMessage: 'Este recurso no tiene enlace.',
      errorMessage: 'No se pudo abrir el recurso.',
    );
  }

  Future<void> _copyResourceLink(Map<String, dynamic> item) async {
    final rawUrl = item['url']?.toString() ?? '';
    if (rawUrl.isEmpty) {
      FlavorSnackbar.showInfo(context, 'Este recurso no tiene enlace.');
      return;
    }

    if (!mounted) return;
    await FlavorShareHelper.copyText(
      context,
      rawUrl,
      successMessage: 'Enlace copiado.',
    );
  }

  Future<void> _toggleFavorite(Map<String, dynamic> item) async {
    final id = _itemId(item);
    final isFavorite = _favoriteIds.contains(id);
    // ignore: invalid_use_of_protected_member
    setState(() {
      if (isFavorite) {
        _favoriteIds.remove(id);
      } else {
        _favoriteIds.add(id);
      }
    });
    await _persistLocalState();
    if (!mounted) return;
    FlavorSnackbar.showInfo(
      context,
      isFavorite ? 'Recurso quitado de guardados.' : 'Recurso guardado.',
    );
  }

  Future<void> _markAsRecent(Map<String, dynamic> item) async {
    final id = _itemId(item);
    // ignore: invalid_use_of_protected_member
    setState(() {
      _recentIds.remove(id);
      _recentIds.insert(0, id);
      if (_recentIds.length > 20) {
        _recentIds = _recentIds.take(20).toList();
      }
    });
    await _persistLocalState();
  }
}

class _RecursoCompartidoDetailScreen extends StatelessWidget {
  final Map<String, dynamic> item;
  final Future<void> Function()? onOpenInApp;
  final Future<void> Function() onOpenExternal;
  final Future<void> Function() onCopyLink;
  final Future<void> Function() onToggleFavorite;
  final bool isFavorite;

  const _RecursoCompartidoDetailScreen({
    required this.item,
    this.onOpenInApp,
    required this.onOpenExternal,
    required this.onCopyLink,
    required this.onToggleFavorite,
    required this.isFavorite,
  });

  @override
  Widget build(BuildContext context) {
    final title = item['title']?.toString() ?? 'Recurso';
    final summary = item['summary']?.toString() ?? '';
    final origin = item['origin']?.toString() ?? '';
    final date = item['date']?.toString() ?? '';
    final type = item['type']?.toString() ?? 'general';
    final source = item['source']?.toString() ?? '';
    final rawType = item['raw_type']?.toString() ?? '';
    final accent = _ResourceCard._parseColor(item['accent']?.toString());
    final icon = _ResourceCard._iconForType(item['icon']?.toString(), type);
    final url = item['url']?.toString() ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle del recurso'),
        actions: [
          IconButton(
            icon: Icon(isFavorite ? Icons.bookmark : Icons.bookmark_border),
            onPressed: onToggleFavorite,
          ),
          IconButton(
            icon: const Icon(Icons.link_outlined),
            onPressed: onCopyLink,
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildHero(context, icon, accent, type),
          const SizedBox(height: 20),
          Text(
            title,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              FlavorStatusChip(
                label: _ResourceCard._labelForType(type),
                backgroundColor: accent.withOpacity(0.14),
                foregroundColor: accent,
              ),
              if (source.isNotEmpty)
                FlavorMetaChip(
                  icon: Icons.layers_outlined,
                  label: source == 'network_events' ? 'Red de eventos' : 'Contenido compartido',
                ),
            ],
          ),
          if (summary.isNotEmpty) ...[
            const SizedBox(height: 20),
            Text(summary, style: Theme.of(context).textTheme.bodyLarge),
          ],
          const SizedBox(height: 24),
          if (origin.isNotEmpty)
            FlavorDetailInfoRow(
              icon: Icons.hub_outlined,
              label: 'Origen',
              value: origin,
            ),
          if (date.isNotEmpty)
            FlavorDetailInfoRow(
              icon: Icons.schedule_outlined,
              label: 'Fecha',
              value: _ResourceCard._formatDate(date),
            ),
          FlavorDetailInfoRow(
            icon: Icons.category_outlined,
            label: 'Tipo',
            value: _ResourceCard._labelForType(type),
          ),
          if (rawType.isNotEmpty)
            FlavorDetailInfoRow(
              icon: Icons.label_outline,
              label: 'Clasificación original',
              value: rawType,
            ),
          if (url.isNotEmpty)
            FlavorDetailInfoRow(
              icon: Icons.open_in_new_outlined,
              label: 'Enlace',
              value: url,
            ),
          const SizedBox(height: 24),
          if (onOpenInApp != null)
            FilledButton.icon(
              onPressed: onOpenInApp,
              icon: const Icon(Icons.visibility_outlined),
              label: const Text('Ver en la app'),
            ),
          if (onOpenInApp != null) const SizedBox(height: 12),
          FilledButton.tonalIcon(
            onPressed: onOpenExternal,
            icon: const Icon(Icons.open_in_new),
            label: const Text('Abrir fuera'),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onCopyLink,
            icon: const Icon(Icons.copy_outlined),
            label: const Text('Copiar enlace'),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onToggleFavorite,
            icon: Icon(isFavorite ? Icons.bookmark : Icons.bookmark_border),
            label: Text(isFavorite ? 'Quitar de guardados' : 'Guardar'),
          ),
        ],
      ),
    );
  }

  Widget _buildHero(BuildContext context, IconData icon, Color accent, String type) {
    return Container(
      height: 180,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [accent.withOpacity(0.92), accent.withOpacity(0.58)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.18),
                borderRadius: BorderRadius.circular(18),
              ),
              child: Icon(icon, color: Colors.white, size: 28),
            ),
            const Spacer(),
            Text(
              _ResourceCard._labelForType(type),
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
            ),
            const SizedBox(height: 6),
            Text(
              'Disponible dentro de la red compartida',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.white.withOpacity(0.92),
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
