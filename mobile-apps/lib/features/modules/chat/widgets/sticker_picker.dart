import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';

/// Modelo de sticker
class Sticker {
  final String id;
  final String url;
  final String packId;
  final String? emoji;

  const Sticker({
    required this.id,
    required this.url,
    required this.packId,
    this.emoji,
  });
}

/// Modelo de pack de stickers
class StickerPack {
  final String id;
  final String name;
  final String thumbnailUrl;
  final List<Sticker> stickers;
  final bool isInstalled;

  const StickerPack({
    required this.id,
    required this.name,
    required this.thumbnailUrl,
    required this.stickers,
    this.isInstalled = false,
  });
}

/// Modelo de GIF
class GifItem {
  final String id;
  final String url;
  final String previewUrl;
  final int width;
  final int height;

  const GifItem({
    required this.id,
    required this.url,
    required this.previewUrl,
    required this.width,
    required this.height,
  });
}

/// Widget selector de stickers y GIFs
class StickerGifPicker extends ConsumerStatefulWidget {
  final Function(Sticker) onStickerSelected;
  final Function(GifItem) onGifSelected;
  final VoidCallback? onClose;

  const StickerGifPicker({
    super.key,
    required this.onStickerSelected,
    required this.onGifSelected,
    this.onClose,
  });

  @override
  ConsumerState<StickerGifPicker> createState() => _StickerGifPickerState();
}

class _StickerGifPickerState extends ConsumerState<StickerGifPicker>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  Timer? _debounce;

  // Stickers
  List<StickerPack> _stickerPacks = [];
  int _selectedPackIndex = 0;
  bool _isLoadingStickers = false;

  // GIFs
  List<GifItem> _trendingGifs = [];
  List<GifItem> _searchGifs = [];
  bool _isLoadingGifs = false;
  bool _isSearchingGifs = false;

  // Recientes
  List<Sticker> _recentStickers = [];
  List<GifItem> _recentGifs = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(_onTabChanged);
    _loadStickers();
    _loadTrendingGifs();
    _loadRecents();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onTabChanged() {
    if (_tabController.index == 1 && _trendingGifs.isEmpty) {
      _loadTrendingGifs();
    }
  }

  Future<void> _loadStickers() async {
    setState(() => _isLoadingStickers = true);

    try {
      // Simular carga de packs
      await Future.delayed(const Duration(milliseconds: 500));

      final packs = _getMockStickerPacks();
      setState(() {
        _stickerPacks = packs;
        _isLoadingStickers = false;
      });
    } catch (e) {
      setState(() => _isLoadingStickers = false);
    }
  }

  Future<void> _loadTrendingGifs() async {
    setState(() => _isLoadingGifs = true);

    try {
      // TODO: Integrar con Giphy API
      await Future.delayed(const Duration(milliseconds: 500));

      final gifs = _getMockGifs();
      setState(() {
        _trendingGifs = gifs;
        _isLoadingGifs = false;
      });
    } catch (e) {
      setState(() => _isLoadingGifs = false);
    }
  }

  Future<void> _searchGifsHandler(String query) async {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () async {
      if (query.isEmpty) {
        setState(() {
          _searchGifs = [];
          _isSearchingGifs = false;
        });
        return;
      }

      setState(() => _isSearchingGifs = true);

      try {
        // TODO: Integrar con Giphy Search API
        await Future.delayed(const Duration(milliseconds: 500));

        final gifs = _getMockGifs(); // Simular resultados
        setState(() {
          _searchGifs = gifs;
          _isSearchingGifs = false;
        });
      } catch (e) {
        setState(() => _isSearchingGifs = false);
      }
    });
  }

  Future<void> _loadRecents() async {
    // TODO: Cargar de SharedPreferences
    setState(() {
      _recentStickers = [];
      _recentGifs = [];
    });
  }

  void _addToRecentStickers(Sticker sticker) {
    setState(() {
      _recentStickers.removeWhere((s) => s.id == sticker.id);
      _recentStickers.insert(0, sticker);
      if (_recentStickers.length > 20) {
        _recentStickers.removeLast();
      }
    });
    // TODO: Guardar en SharedPreferences
  }

  void _addToRecentGifs(GifItem gif) {
    setState(() {
      _recentGifs.removeWhere((g) => g.id == gif.id);
      _recentGifs.insert(0, gif);
      if (_recentGifs.length > 20) {
        _recentGifs.removeLast();
      }
    });
    // TODO: Guardar en SharedPreferences
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Container(
      height: 350,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Handle
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              color: Colors.grey[400],
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Tabs
          TabBar(
            controller: _tabController,
            tabs: const [
              Tab(icon: Icon(Icons.emoji_emotions), text: 'Stickers'),
              Tab(icon: Icon(Icons.gif), text: 'GIFs'),
            ],
          ),

          // Contenido
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildStickersTab(),
                _buildGifsTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStickersTab() {
    if (_isLoadingStickers) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_stickerPacks.isEmpty) {
      return _buildEmptyState(
        icon: Icons.emoji_emotions_outlined,
        message: 'No hay packs de stickers',
        action: 'Explorar tienda',
        onAction: () {
          // TODO: Abrir tienda de stickers
        },
      );
    }

    return Column(
      children: [
        // Packs selector
        SizedBox(
          height: 56,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            itemCount: _stickerPacks.length + 1, // +1 para recientes
            itemBuilder: (context, index) {
              if (index == 0) {
                return _buildPackTab(
                  icon: Icons.history,
                  isSelected: _selectedPackIndex == -1,
                  onTap: () => setState(() => _selectedPackIndex = -1),
                );
              }

              final pack = _stickerPacks[index - 1];
              return _buildPackTab(
                imageUrl: pack.thumbnailUrl,
                isSelected: _selectedPackIndex == index - 1,
                onTap: () => setState(() => _selectedPackIndex = index - 1),
              );
            },
          ),
        ),

        const Divider(height: 1),

        // Grid de stickers
        Expanded(
          child: _selectedPackIndex == -1
              ? _buildRecentStickers()
              : _buildStickerGrid(_stickerPacks[_selectedPackIndex]),
        ),
      ],
    );
  }

  Widget _buildPackTab({
    IconData? icon,
    String? imageUrl,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    final colorScheme = Theme.of(context).colorScheme;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 48,
        height: 48,
        margin: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
        decoration: BoxDecoration(
          color: isSelected ? colorScheme.primaryContainer : null,
          borderRadius: BorderRadius.circular(8),
          border: isSelected
              ? Border.all(color: colorScheme.primary, width: 2)
              : null,
        ),
        child: icon != null
            ? Icon(icon, color: isSelected ? colorScheme.primary : null)
            : ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: CachedNetworkImage(
                  imageUrl: imageUrl!,
                  fit: BoxFit.cover,
                ),
              ),
      ),
    );
  }

  Widget _buildRecentStickers() {
    if (_recentStickers.isEmpty) {
      return _buildEmptyState(
        icon: Icons.history,
        message: 'No hay stickers recientes',
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(8),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
      ),
      itemCount: _recentStickers.length,
      itemBuilder: (context, index) {
        final sticker = _recentStickers[index];
        return _buildStickerItem(sticker);
      },
    );
  }

  Widget _buildStickerGrid(StickerPack pack) {
    return GridView.builder(
      padding: const EdgeInsets.all(8),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
      ),
      itemCount: pack.stickers.length,
      itemBuilder: (context, index) {
        final sticker = pack.stickers[index];
        return _buildStickerItem(sticker);
      },
    );
  }

  Widget _buildStickerItem(Sticker sticker) {
    return GestureDetector(
      onTap: () {
        _addToRecentStickers(sticker);
        widget.onStickerSelected(sticker);
      },
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
        ),
        child: CachedNetworkImage(
          imageUrl: sticker.url,
          fit: BoxFit.contain,
          placeholder: (context, url) => Container(
            color: Colors.grey[200],
          ),
          errorWidget: (context, url, error) => const Icon(Icons.error),
        ),
      ),
    );
  }

  Widget _buildGifsTab() {
    return Column(
      children: [
        // Búsqueda
        Padding(
          padding: const EdgeInsets.all(8),
          child: TextField(
            controller: _searchController,
            onChanged: _searchGifsHandler,
            decoration: InputDecoration(
              hintText: 'Buscar GIFs...',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: _searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _searchController.clear();
                        setState(() => _searchGifs = []);
                      },
                    )
                  : null,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16),
              isDense: true,
            ),
          ),
        ),

        // Powered by Giphy
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Row(
            children: [
              Text(
                'Powered by',
                style: TextStyle(
                  fontSize: 10,
                  color: Colors.grey[600],
                ),
              ),
              const SizedBox(width: 4),
              Text(
                'GIPHY',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey[800],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 4),

        // Grid de GIFs
        Expanded(
          child: _buildGifGrid(),
        ),
      ],
    );
  }

  Widget _buildGifGrid() {
    final gifs = _searchController.text.isNotEmpty ? _searchGifs : _trendingGifs;
    final isLoading = _isSearchingGifs || (_isLoadingGifs && gifs.isEmpty);

    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (gifs.isEmpty) {
      return _buildEmptyState(
        icon: Icons.gif_box_outlined,
        message: _searchController.text.isNotEmpty
            ? 'No se encontraron GIFs'
            : 'No hay GIFs disponibles',
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(8),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
        childAspectRatio: 1.2,
      ),
      itemCount: gifs.length,
      itemBuilder: (context, index) {
        final gif = gifs[index];
        return _buildGifItem(gif);
      },
    );
  }

  Widget _buildGifItem(GifItem gif) {
    return GestureDetector(
      onTap: () {
        _addToRecentGifs(gif);
        widget.onGifSelected(gif);
      },
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: CachedNetworkImage(
          imageUrl: gif.previewUrl,
          fit: BoxFit.cover,
          placeholder: (context, url) => Container(
            color: Colors.grey[200],
            child: const Center(
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
          errorWidget: (context, url, error) => Container(
            color: Colors.grey[200],
            child: const Icon(Icons.error),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState({
    required IconData icon,
    required String message,
    String? action,
    VoidCallback? onAction,
  }) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 48, color: Colors.grey[400]),
          const SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(color: Colors.grey[600]),
          ),
          if (action != null && onAction != null) ...[
            const SizedBox(height: 16),
            TextButton(
              onPressed: onAction,
              child: Text(action),
            ),
          ],
        ],
      ),
    );
  }

  // Mock data
  List<StickerPack> _getMockStickerPacks() {
    return [
      StickerPack(
        id: 'pack1',
        name: 'Emojis animados',
        thumbnailUrl: 'https://via.placeholder.com/100',
        isInstalled: true,
        stickers: List.generate(
          20,
          (i) => Sticker(
            id: 'sticker_1_$i',
            url: 'https://via.placeholder.com/150',
            packId: 'pack1',
          ),
        ),
      ),
      StickerPack(
        id: 'pack2',
        name: 'Gatos',
        thumbnailUrl: 'https://via.placeholder.com/100',
        isInstalled: true,
        stickers: List.generate(
          16,
          (i) => Sticker(
            id: 'sticker_2_$i',
            url: 'https://via.placeholder.com/150',
            packId: 'pack2',
          ),
        ),
      ),
      StickerPack(
        id: 'pack3',
        name: 'Perros',
        thumbnailUrl: 'https://via.placeholder.com/100',
        isInstalled: true,
        stickers: List.generate(
          12,
          (i) => Sticker(
            id: 'sticker_3_$i',
            url: 'https://via.placeholder.com/150',
            packId: 'pack3',
          ),
        ),
      ),
    ];
  }

  List<GifItem> _getMockGifs() {
    return List.generate(
      20,
      (i) => GifItem(
        id: 'gif_$i',
        url: 'https://via.placeholder.com/300x200',
        previewUrl: 'https://via.placeholder.com/150x100',
        width: 300,
        height: 200,
      ),
    );
  }
}

/// Selector de emojis
class EmojiPicker extends StatefulWidget {
  final Function(String) onEmojiSelected;

  const EmojiPicker({super.key, required this.onEmojiSelected});

  @override
  State<EmojiPicker> createState() => _EmojiPickerState();
}

class _EmojiPickerState extends State<EmojiPicker>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final List<String> _recentEmojis = [];

  // Categorías de emojis
  static const _categories = [
    {'name': 'Recientes', 'icon': Icons.history},
    {'name': 'Caras', 'icon': Icons.emoji_emotions},
    {'name': 'Personas', 'icon': Icons.people},
    {'name': 'Animales', 'icon': Icons.pets},
    {'name': 'Comida', 'icon': Icons.restaurant},
    {'name': 'Actividades', 'icon': Icons.sports_soccer},
    {'name': 'Viajes', 'icon': Icons.flight},
    {'name': 'Objetos', 'icon': Icons.lightbulb},
    {'name': 'Símbolos', 'icon': Icons.favorite},
    {'name': 'Banderas', 'icon': Icons.flag},
  ];

  // Emojis por categoría (subset)
  static const _emojis = {
    'Caras': [
      '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉',
      '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😜', '🤪', '😝',
      '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒',
      '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢',
    ],
    'Personas': [
      '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘',
      '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛',
      '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾',
    ],
    'Animales': [
      '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮',
      '🐷', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣',
      '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌',
    ],
    'Comida': [
      '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑',
      '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶', '🫑',
      '🍔', '🍟', '🍕', '🌭', '🥪', '🌮', '🌯', '🥙', '🧆', '🥚', '🍳', '🥘',
    ],
    'Actividades': [
      '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓',
      '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿',
      '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸', '🥌', '🎿', '⛷', '🏂', '🪂',
    ],
    'Viajes': [
      '🚗', '🚕', '🚙', '🚌', '🚎', '🏎', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚',
      '🚛', '🚜', '🏍', '🛵', '🚲', '🛴', '🛹', '🛼', '🚁', '🛸', '✈️', '🛩',
      '⛵', '🚤', '🛥', '🛳', '⛴', '🚢', '⚓', '🪝', '⛽', '🚧', '🚦', '🚥',
    ],
    'Objetos': [
      '💡', '🔦', '🏮', '🪔', '📱', '💻', '🖥', '🖨', '⌨️', '🖱', '🖲', '💾',
      '💿', '📀', '🧮', '🎥', '🎞', '📽', '📺', '📷', '📸', '📹', '📼', '🔍',
      '💎', '💰', '💵', '💴', '💶', '💷', '💳', '💸', '✉️', '📧', '📨', '📩',
    ],
    'Símbolos': [
      '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕',
      '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️',
      '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌',
    ],
    'Banderas': [
      '🏳️', '🏴', '🏁', '🚩', '🏳️‍🌈', '🏳️‍⚧️', '🇪🇸', '🇺🇸', '🇬🇧', '🇫🇷', '🇩🇪', '🇮🇹',
      '🇵🇹', '🇧🇷', '🇲🇽', '🇦🇷', '🇨🇴', '🇨🇱', '🇵🇪', '🇻🇪', '🇪🇨', '🇨🇺', '🇯🇵', '🇨🇳',
    ],
  };

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: _categories.length,
      vsync: this,
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 280,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Categorías
          SizedBox(
            height: 44,
            child: TabBar(
              controller: _tabController,
              isScrollable: true,
              tabs: _categories.map((cat) => Tab(
                icon: Icon(cat['icon'] as IconData, size: 20),
              )).toList(),
            ),
          ),

          // Emojis
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: _categories.map((cat) {
                final name = cat['name'] as String;
                if (name == 'Recientes') {
                  return _buildRecentEmojis();
                }
                return _buildEmojiGrid(_emojis[name] ?? []);
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRecentEmojis() {
    if (_recentEmojis.isEmpty) {
      return const Center(
        child: Text(
          'No hay emojis recientes',
          style: TextStyle(color: Colors.grey),
        ),
      );
    }

    return _buildEmojiGrid(_recentEmojis);
  }

  Widget _buildEmojiGrid(List<String> emojis) {
    return GridView.builder(
      padding: const EdgeInsets.all(8),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 8,
        crossAxisSpacing: 4,
        mainAxisSpacing: 4,
      ),
      itemCount: emojis.length,
      itemBuilder: (context, index) {
        final emoji = emojis[index];
        return GestureDetector(
          onTap: () {
            setState(() {
              _recentEmojis.remove(emoji);
              _recentEmojis.insert(0, emoji);
              if (_recentEmojis.length > 30) {
                _recentEmojis.removeLast();
              }
            });
            widget.onEmojiSelected(emoji);
          },
          child: Center(
            child: Text(
              emoji,
              style: const TextStyle(fontSize: 24),
            ),
          ),
        );
      },
    );
  }
}
