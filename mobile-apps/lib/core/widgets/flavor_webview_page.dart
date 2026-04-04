import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class FlavorWebViewPage extends StatefulWidget {
  final String title;
  final String url;
  final Color? backgroundColor;
  final Color? foregroundColor;
  final Widget? leading;
  final List<Widget> Function(BuildContext context, WebViewController controller)?
      actionsBuilder;
  final NavigationDecision Function(NavigationRequest request)?
      onNavigationRequest;
  final void Function(String url)? onPageFinished;
  final void Function(WebResourceError error)? onWebResourceError;
  final bool showLoadingOverlay;
  final Widget Function(
    BuildContext context,
    String errorMessage,
    VoidCallback retry,
  )? errorBuilder;
  final Widget Function(BuildContext context)? loadingBuilder;

  const FlavorWebViewPage({
    super.key,
    required this.title,
    required this.url,
    this.backgroundColor,
    this.foregroundColor,
    this.leading,
    this.actionsBuilder,
    this.onNavigationRequest,
    this.onPageFinished,
    this.onWebResourceError,
    this.showLoadingOverlay = false,
    this.errorBuilder,
    this.loadingBuilder,
  });

  @override
  State<FlavorWebViewPage> createState() => _FlavorWebViewPageState();
}

class _FlavorWebViewPageState extends State<FlavorWebViewPage> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _initController();
  }

  void _initController() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: (_) {
            setState(() {
              _isLoading = true;
              _errorMessage = null;
            });
          },
          onPageFinished: (url) {
            setState(() {
              _isLoading = false;
            });
            widget.onPageFinished?.call(url);
          },
          onNavigationRequest: (request) {
            return widget.onNavigationRequest?.call(request) ??
                NavigationDecision.navigate;
          },
          onWebResourceError: (error) {
            setState(() {
              _isLoading = false;
              _errorMessage = error.description;
            });
            widget.onWebResourceError?.call(error);
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  void _retry() {
    setState(() {
      _errorMessage = null;
      _isLoading = true;
    });
    _controller.loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        backgroundColor: widget.backgroundColor,
        foregroundColor: widget.foregroundColor,
        leading: widget.leading,
        actions: widget.actionsBuilder?.call(context, _controller),
        bottom: _isLoading
            ? PreferredSize(
                preferredSize: const Size.fromHeight(4),
                child: LinearProgressIndicator(value: _progress),
              )
            : null,
      ),
      body: Stack(
        children: [
          if (_errorMessage != null && widget.errorBuilder != null)
            widget.errorBuilder!(context, _errorMessage!, _retry)
          else
            WebViewWidget(controller: _controller),
          if (_isLoading && widget.showLoadingOverlay)
            Positioned.fill(
              child: widget.loadingBuilder?.call(context) ??
                  Container(
                    color: Theme.of(context).colorScheme.surface,
                    child: Center(
                      child: CircularProgressIndicator(
                        color: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                  ),
            ),
        ],
      ),
    );
  }
}
