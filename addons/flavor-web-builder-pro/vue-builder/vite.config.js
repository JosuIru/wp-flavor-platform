import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: resolve(__dirname, 'src/main.js'),
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
    },
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: true,
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },
});
