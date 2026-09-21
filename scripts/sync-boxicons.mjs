import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const src = join(root, 'node_modules/boxicons/svg');
const dest = join(root, 'public/vendor/boxicons/svg');

if (!existsSync(src)) {
    console.warn('boxicons svg missing — run npm install');
    process.exit(0);
}

mkdirSync(dirname(dest), { recursive: true });
rmSync(dest, { recursive: true, force: true });
cpSync(src, dest, { recursive: true });
console.log('synced boxicons svg → public/vendor/boxicons/svg');
