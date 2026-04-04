import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

class FlavorImageViewerItem {
  final String url;
  final String? caption;

  const FlavorImageViewerItem({
    required this.url,
    this.caption,
  });
}

class FlavorImageViewer extends StatefulWidget {
  final List<FlavorImageViewerItem> images;
  final int initialIndex;
  final Color accentColor;

  const FlavorImageViewer({
    super.key,
    required this.images,
    this.initialIndex = 0,
    this.accentColor = Colors.white,
  });

  @override
  State<FlavorImageViewer> createState() => _FlavorImageViewerState();
}

class _FlavorImageViewerState extends State<FlavorImageViewer> {
  late final PageController _pageController;
  late int _currentIndex;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: widget.initialIndex);
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final current = widget.images[_currentIndex];

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text('${_currentIndex + 1}/${widget.images.length}'),
      ),
      body: Stack(
        children: [
          PageView.builder(
            controller: _pageController,
            itemCount: widget.images.length,
            onPageChanged: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            itemBuilder: (context, index) {
              final image = widget.images[index];
              return InteractiveViewer(
                minScale: 0.8,
                maxScale: 4,
                child: Center(
                  child: CachedNetworkImage(
                    imageUrl: image.url,
                    fit: BoxFit.contain,
                    errorWidget: (_, __, ___) => Icon(
                      Icons.broken_image_outlined,
                      size: 72,
                      color: widget.accentColor,
                    ),
                  ),
                ),
              );
            },
          ),
          if ((current.caption ?? '').isNotEmpty)
            Positioned(
              left: 16,
              right: 16,
              bottom: 24,
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.black.withOpacity(0.55),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Text(
                  current.caption!,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
