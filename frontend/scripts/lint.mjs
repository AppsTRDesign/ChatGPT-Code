import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, extname } from 'node:path';

const ROOT = new URL('../src', import.meta.url);
const EXTENSIONS = new Set(['.js', '.jsx', '.mjs']);
const violations = [];

function walk(dir) {
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    const stat = statSync(full);
    if (stat.isDirectory()) {
      walk(full);
      continue;
    }

    if (!EXTENSIONS.has(extname(full))) continue;

    const text = readFileSync(full, 'utf8');
    if (text.includes('\t')) {
      violations.push(`${full}: contains tab indentation`);
    }
    if (text.includes('<<<<<<<') || text.includes('>>>>>>>') || text.includes('=======')) {
      violations.push(`${full}: contains merge-conflict markers`);
    }
  }
}

walk(ROOT.pathname);

if (violations.length > 0) {
  console.error('Frontend lint checks failed:');
  for (const line of violations) console.error(`- ${line}`);
  process.exit(1);
}

console.log('Frontend lint checks passed.');
