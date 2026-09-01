import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['src/main.js'], // 🔥 ubah dari resources/js/app.js
            refresh: true,
        }),
        vue(),
    ],
})