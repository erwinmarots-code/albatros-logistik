import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    rollupOptions: {
      input: 'src/main.js',
      external: (id) => {
        // Abaikan semua file gambar di folder public
        return id.startsWith('/images/')
      }
    }
  },
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8000'
    }
  }
})