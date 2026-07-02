import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

// This project keeps JSX inside plenty of `.js` files under resources/pos/src.
// Stable (esbuild-based) Vite handles that with two settings:
//   1. plugin-react's default `include` (/\.[tj]sx?$/) already matches `.js`
//      and runs the Babel `jsx` parser on it -> JSX in .js source compiles.
//   2. optimizeDeps.esbuildOptions.loader -> the dependency *scanner/pre-bundler*
//      (which runs before plugin transforms) parses .js as JSX too.
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/pos/src/index.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],

    css: {
        preprocessorOptions: {
            scss: {
                // Silence Dart Sass deprecation warnings coming from
                // node_modules (Bootstrap, Swiper) and legacy project SCSS.
                // Output is correct; remove once upstream ships modern Sass.
                silenceDeprecations: [
                    'import',
                    'global-builtin',
                    'color-functions',
                    'if-function',
                ],
                quietDeps: true,
            },
        },
    },

    // plugin-react defers the actual JSX->JS compile to Vite's built-in
    // esbuild, which by default only treats .jsx/.tsx as JSX. This project
    // keeps JSX in hundreds of .js files, so tell esbuild to load .js/.jsx
    // under resources/pos/src with the JSX loader. (No .ts/.tsx source here.)
    esbuild: {
        loader: 'jsx',
        include: /resources[\\/]pos[\\/]src[\\/].*\.jsx?$/,
        exclude: [],
    },

    // Same treatment for the dependency scanner / pre-bundler.
    optimizeDeps: {
        esbuildOptions: {
            loader: {
                '.js': 'jsx',
            },
        },
    },

    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources'),
        },
    },

    build: {
        outDir: 'public',
        emptyOutDir: false, // IMPORTANT: you also copy images into public
        rollupOptions: {
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

    server: {
        host: 'localhost',
        port: 5173,
        strictPort: false,
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },
});
