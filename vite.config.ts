import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

/**
 * Make sure the `php` that build-time plugins shell out to is new enough.
 *
 * The Wayfinder plugin runs `php artisan wayfinder:generate` during the build.
 * On a typical Windows dev box an old XAMPP PHP often sits earlier on PATH than
 * Herd's, and Laravel 13 requires PHP >= 8.3 — so the build dies with an opaque
 * "Command failed" from rolldown. Rather than depend on every machine's PATH
 * being ordered correctly, prepend the newest Herd PHP when we can find one.
 *
 * No-op off Windows and when no Herd install is present, so CI and the Linux
 * deploy server are unaffected.
 */
function preferHerdPhp(): void {
    if (process.platform !== 'win32') return;

    const phpIsNewEnough = (dir: string): boolean => {
        try {
            const version = execFileSync(
                path.join(dir, 'php.exe'),
                ['-r', 'echo PHP_VERSION_ID;'],
                {
                    encoding: 'utf8',
                },
            );
            return Number(version.trim()) >= 80300;
        } catch {
            return false;
        }
    };

    // Already fine? Leave PATH alone.
    try {
        const current = execFileSync('php', ['-r', 'echo PHP_VERSION_ID;'], {
            encoding: 'utf8',
        });
        if (Number(current.trim()) >= 80300) return;
    } catch {
        // No php on PATH at all — fall through and try to supply one.
    }

    const herdBin = path.join(os.homedir(), '.config', 'herd', 'bin');
    if (!fs.existsSync(herdBin)) return;

    const candidate = fs
        .readdirSync(herdBin)
        .filter((entry) => /^php\d+$/.test(entry))
        .map((entry) => path.join(herdBin, entry))
        .filter((dir) => fs.existsSync(path.join(dir, 'php.exe')))
        .sort()
        .reverse()
        .find(phpIsNewEnough);

    if (candidate) {
        process.env.PATH = `${candidate}${path.delimiter}${process.env.PATH ?? ''}`;
    }
}

preferHerdPhp();

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                // Inertia admin / clinic-owner panel.
                'resources/js/app.ts',
                // Vue islands mounted into the public Blade pages. Kept as a
                // separate entry so SEO pages never load the Inertia runtime.
                'resources/js/public.ts',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});
