import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const emptyVarFallback = /var\((--[\w-]+),\)/g;

/**
 * Chrome < 111 treats `var(--registered-property,)` as invalid when the empty
 * fallback has no whitespace, which drops whole declarations such as the
 * `box-shadow` of `ring-*` or the `filter` utilities. Tailwind emits exactly that
 * form, so rewrite it to the equivalent `var(--x, )` after minification.
 */
function preserveEmptyVarFallback() {
    const fix = (css) => css.replace(emptyVarFallback, 'var($1, )');

    return {
        name: 'preserve-empty-var-fallback',
        enforce: 'post',
        transform(code, id) {
            if (/\.css(\?|$)/.test(id)) {
                return { code: fix(code), map: null };
            }
        },
        generateBundle(options, bundle) {
            for (const asset of Object.values(bundle)) {
                if (asset.type === 'asset' && asset.fileName.endsWith('.css')) {
                    asset.source = fix(asset.source.toString());
                }
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        preserveEmptyVarFallback(),
    ],
});
