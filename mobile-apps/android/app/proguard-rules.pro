# Flutter wrapper
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.**  { *; }
-keep class io.flutter.util.**  { *; }
-keep class io.flutter.view.**  { *; }
-keep class io.flutter.**  { *; }
-keep class io.flutter.plugins.**  { *; }

# Play Core (deferred components)
-dontwarn com.google.android.play.core.**

# Retrofit / OkHttp
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn javax.annotation.**

# Dio
-keep class dio.** { *; }

# Keep models
-keep class com.gailuwz.chat_ia_reservas.** { *; }
-keep class com.flavorplatform.chatia.** { *; }
