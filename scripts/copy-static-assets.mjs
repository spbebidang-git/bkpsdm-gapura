import { cpSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'resources/static/public/assets');
const destination = join(root, 'public/assets');

mkdirSync(destination, { recursive: true });
cpSync(source, destination, { recursive: true, force: true });
