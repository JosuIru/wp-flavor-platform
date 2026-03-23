import 'package:flutter/material.dart';
import 'onboarding_service.dart';

/// Pantalla de Onboarding
class OnboardingScreen extends StatefulWidget {
  final OnboardingConfig config;
  final VoidCallback onComplete;
  final VoidCallback? onSkip;

  const OnboardingScreen({
    super.key,
    required this.config,
    required this.onComplete,
    this.onSkip,
  });

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  late PageController _pageController;
  int _currentPage = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  bool get _isLastPage => _currentPage == widget.config.slides.length - 1;

  void _nextPage() {
    if (_isLastPage) {
      widget.onComplete();
    } else {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void _skip() {
    widget.onSkip?.call();
    widget.onComplete();
  }

  Color _parseColor(String? colorString, Color defaultColor) {
    if (colorString == null) return defaultColor;
    try {
      final hex = colorString.replaceFirst('#', '');
      return Color(int.parse('FF$hex', radix: 16));
    } catch (e) {
      return defaultColor;
    }
  }

  IconData _getIcon(String? iconName) {
    final icons = {
      'waving_hand': Icons.waving_hand,
      'apps': Icons.apps,
      'people': Icons.people,
      'star': Icons.star,
      'favorite': Icons.favorite,
      'notifications': Icons.notifications,
      'settings': Icons.settings,
      'home': Icons.home,
      'search': Icons.search,
      'calendar': Icons.calendar_today,
      'shopping': Icons.shopping_bag,
      'chat': Icons.chat,
      'map': Icons.map,
      'check': Icons.check_circle,
    };
    return icons[iconName] ?? Icons.circle;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // PageView de slides
          PageView.builder(
            controller: _pageController,
            onPageChanged: (index) {
              setState(() => _currentPage = index);
            },
            itemCount: widget.config.slides.length,
            itemBuilder: (context, index) {
              final slide = widget.config.slides[index];
              return _buildSlide(slide);
            },
          ),

          // Botón Skip
          if (widget.config.skipEnabled && !_isLastPage)
            Positioned(
              top: MediaQuery.of(context).padding.top + 16,
              right: 16,
              child: TextButton(
                onPressed: _skip,
                child: Text(
                  widget.config.skipButtonText ?? 'Saltar',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 16,
                  ),
                ),
              ),
            ),

          // Indicadores y botones inferiores
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Indicadores de progreso
                    if (widget.config.showProgressDots)
                      _buildProgressDots(),

                    if (widget.config.showProgressBar)
                      _buildProgressBar(),

                    const SizedBox(height: 32),

                    // Botón principal
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton(
                        onPressed: _nextPage,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: _parseColor(
                            widget.config.slides[_currentPage].backgroundColor,
                            Theme.of(context).primaryColor,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          elevation: 0,
                        ),
                        child: Text(
                          _isLastPage
                              ? (widget.config.finishButtonText ?? 'Comenzar')
                              : (widget.config.nextButtonText ?? 'Siguiente'),
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSlide(OnboardingSlide slide) {
    final bgColor = _parseColor(slide.backgroundColor, Theme.of(context).primaryColor);
    final textColor = _parseColor(slide.textColor, Colors.white);

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            bgColor,
            bgColor.withOpacity(0.8),
          ],
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Spacer(flex: 2),

              // Imagen o icono
              if (slide.imageUrl != null)
                ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: Image.network(
                    slide.imageUrl!,
                    height: 280,
                    fit: BoxFit.contain,
                    errorBuilder: (_, __, ___) => _buildIconPlaceholder(slide, textColor),
                  ),
                )
              else
                _buildIconPlaceholder(slide, textColor),

              const Spacer(),

              // Título
              Text(
                slide.title,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: textColor,
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  height: 1.2,
                ),
              ),

              const SizedBox(height: 16),

              // Descripción
              Text(
                slide.description,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: textColor.withOpacity(0.9),
                  fontSize: 18,
                  height: 1.5,
                ),
              ),

              const Spacer(flex: 3),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildIconPlaceholder(OnboardingSlide slide, Color textColor) {
    return Container(
      width: 180,
      height: 180,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        borderRadius: BorderRadius.circular(40),
      ),
      child: Icon(
        _getIcon(slide.iconName),
        size: 100,
        color: textColor,
      ),
    );
  }

  Widget _buildProgressDots() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(
        widget.config.slides.length,
        (index) => AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          margin: const EdgeInsets.symmetric(horizontal: 4),
          width: index == _currentPage ? 32 : 8,
          height: 8,
          decoration: BoxDecoration(
            color: index == _currentPage
                ? Colors.white
                : Colors.white.withOpacity(0.4),
            borderRadius: BorderRadius.circular(4),
          ),
        ),
      ),
    );
  }

  Widget _buildProgressBar() {
    final progress = (_currentPage + 1) / widget.config.slides.length;
    return Container(
      height: 4,
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.3),
        borderRadius: BorderRadius.circular(2),
      ),
      child: FractionallySizedBox(
        alignment: Alignment.centerLeft,
        widthFactor: progress,
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
      ),
    );
  }
}
