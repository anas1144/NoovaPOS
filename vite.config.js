import { defineConfig, transformWithOxc } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';
import fs from 'fs';

const posJsxInJs = {
    name: 'noovapos:pos-jsx-in-js',
    enforce: 'pre',
    async load(id) {
        const cleanId = id.split('?')[0];
        const normalizedId = cleanId.replace(/\\/g, '/');

        if (
            cleanId.endsWith('.js') &&
            normalizedId.includes('/resources/pos/src/') &&
            fs.existsSync(cleanId)
        ) {
            const code = fs.readFileSync(cleanId, 'utf8');
            const result = await transformWithOxc(code, cleanId, {
                lang: 'jsx',
                jsx: {
                    runtime: 'automatic',
                },
            });

            return {
                code: result.code,
                map: result.map,
            };
        }

        return null;
    },
};

export default defineConfig({
    plugins: [
        posJsxInJs,
        laravel({
            input: [
                // 'resources/js/app.js',
                'resources/pos/src/index.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],

    oxc: {
        include: /resources[\\/]pos[\\/]src[\\/].*\.[jt]sx?$/,
        exclude: /node_modules/,
        jsx: {
            runtime: 'automatic',
        },
    },

    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources'),
        },
    },

    build: {
        outDir: 'public',
        emptyOutDir: false, // IMPORTANT: because you also copy images
        rollupOptions: {
            moduleTypes: {
                '.js': 'jsx',
            },
            transform: {
                jsx: 'react-jsx',
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/chunks/[name].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }
                    return 'assets/[name][extname]';
                },
            },
        },
    },

    optimizeDeps: {
        rolldownOptions: {
            moduleTypes: {
                '.js': 'jsx',
            },
        },
    },

    server: {
        host: 'localhost',
        port: 5173,
        strictPort: false,
        // Allow requests from noovapos.test (Laragon domain)
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },
});
