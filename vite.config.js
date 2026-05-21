import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import Components from "unplugin-vue-components/vite";
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig(({ command, mode }) => ({
    define: {
        __APP_BUILD_INFO__: JSON.stringify({
            builtAt: new Date().toISOString(),
            mode,
            command,
        }),
    },
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue(),
        Components({
            dirs: ["resources/js/components", "resources/js/layouts"],
            extensions: ["vue"],
            deep: true,
            dts: false,
            directoryAsNamespace: true,
            collapseSamePrefixes: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
}));
