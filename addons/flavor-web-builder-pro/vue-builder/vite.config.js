import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const currentDir = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: resolve(currentDir, 'src/main.js'),
      name: 'FlavorVuePageBuilder',
      fileName: (format) => `vue-page-builder.${format}.js`,
      formats: ['umd'],
    },
    rollupOptions: {
      external: ['vue', 'pinia'],
      output: {
        globals: {
          vue: 'Vue',
          pinia: 'Pinia',
        },
        assetFileNames: 'vue-page-builder.[ext]',
      },
      treeshake: {
        moduleSideEffects: false,
        propertyReadSideEffects: false,
      },
    },
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: false, // Desactivar en producción para reducir tamaño
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
        pure_funcs: ['console.log', 'console.info', 'console.debug'],
      },
      mangle: {
        safari10: true,
      },
      format: {
        comments: false,
      },
    },
    chunkSizeWarningLimit: 500,
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },
});
