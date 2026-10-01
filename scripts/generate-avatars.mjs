// Generates the avatar catalogue: one SVG per style and seed, written to public/images/avatars/{kind}/{style}/{seed}.svg.
// The catalogue itself (styles, seeds, background colours) lives in resources/avatars/catalogue.json, which config/avatars.php
// also reads, so the files on disk and the choices the app offers cannot drift apart.
//
//   npm run avatars:generate
//
// Every style here is CC0 or free for commercial use with no credit required (checked against each style's licence metadata
// in @dicebear/collection), so nothing needs crediting. Output is deterministic: the same seed always draws the same avatar.
import { createAvatar } from '@dicebear/core';
import * as collection from '@dicebear/collection';
import { mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const catalogue = JSON.parse(readFileSync(join(root, 'resources/avatars/catalogue.json'), 'utf8'));
const out = join(root, 'public/images/avatars');

const allowed = ['CC0 1.0', 'Free for personal and commercial use', 'Free for personal and commercial use.'];

rmSync(out, { recursive: true, force: true });
let count = 0;

for (const kind of ['people', 'stores']) {
    for (const style of catalogue[kind].styles) {
        const definition = collection[style.package];

        if (!definition) throw new Error(`Unknown DiceBear style: ${style.package}`);
        if (!allowed.includes(definition.meta.license.name)) throw new Error(`${style.key} is licensed ${definition.meta.license.name}, which needs a credit`);

        mkdirSync(join(out, kind, style.key), { recursive: true });

        for (const seed of catalogue[kind].seeds) {
            const svg = createAvatar(definition, { seed, backgroundColor: catalogue.backgrounds, radius: 0 }).toString();
            writeFileSync(join(out, kind, style.key, `${seed}.svg`), svg);
            count++;
        }
    }
}

console.log(`Wrote ${count} avatars to public/images/avatars`);
