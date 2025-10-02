#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');

const AUTHOR = {
  name: 'Francesco Passeri',
  email: 'info@francescopasseri.com',
  uri: 'https://francescopasseri.com',
};

const DESCRIPTION = 'Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.';
const SUPPORT_URL = 'https://francescopasseri.com';

const args = process.argv.slice(2);
const options = {
  apply: false,
  docsOnly: false,
};

for (const arg of args) {
  if (arg.startsWith('--apply')) {
    const [, value] = arg.split('=');
    options.apply = value === 'true' || value === '1' || value === undefined;
  }

  if (arg === '--docs' || arg.startsWith('--docs=')) {
    options.docsOnly = true;
  }
}

const repoRoot = path.resolve(__dirname, '..');
const projectRoot = repoRoot;
const report = [];

async function readFile(filePath) {
  return fs.promises.readFile(filePath, 'utf8');
}

async function saveFile(filePath, content) {
  if (!options.apply) {
    const backupPath = `${filePath}.bak`;
    try {
      await fs.promises.writeFile(backupPath, await readFile(filePath), 'utf8');
    } catch (error) {
      if (error.code !== 'ENOENT') {
        throw error;
      }
    }

    return;
  }

  await fs.promises.writeFile(filePath, ensureEndingNewline(content), 'utf8');
}

async function fileExists(filePath) {
  try {
    await fs.promises.access(filePath);
    return true;
  } catch (error) {
    if (error.code === 'ENOENT') {
      return false;
    }

    throw error;
  }
}

function ensureEndingNewline(content) {
  return content.endsWith('\n') ? content : `${content}\n`;
}

function recordChange(filePath, fields) {
  if (!fields.length) {
    return;
  }

  report.push({ file: path.relative(projectRoot, filePath), fields });
}

function updateDocblock(content) {
  const docblockRegex = /^\/\*\*[\s\S]*?\*\//m;
  const match = docblockRegex.exec(content);

  if (!match) {
    return { content, changed: false };
  }

  const block = match[0];

  if (block.includes('Plugin Name:')) {
    return { content, changed: false };
  }

  const lines = block.split('\n');
  const endIndex = lines.findIndex((line) => line.trim() === '*/');

  if (endIndex === -1) {
    return { content, changed: false };
  }

  let changed = false;
  const authorLine = ` * @author ${AUTHOR.name}`;
  const linkLine = ` * @link ${AUTHOR.uri}`;

  const existingAuthorIndex = lines.findIndex((line) => line.includes('@author'));
  if (existingAuthorIndex === -1) {
    lines.splice(endIndex, 0, authorLine);
    changed = true;
  } else if (lines[existingAuthorIndex].trim() !== authorLine.trim()) {
    lines[existingAuthorIndex] = authorLine;
    changed = true;
  }

  const existingLinkIndex = lines.findIndex((line) => line.includes('@link'));
  if (existingLinkIndex === -1) {
    lines.splice(endIndex + (existingAuthorIndex === -1 ? 1 : 0), 0, linkLine);
    changed = true;
  } else if (lines[existingLinkIndex].trim() !== linkLine.trim()) {
    lines[existingLinkIndex] = linkLine;
    changed = true;
  }

  if (!changed) {
    return { content, changed: false };
  }

  const updatedBlock = lines.join('\n');
  const updatedContent = content.replace(block, updatedBlock);
  return { content: updatedContent, changed: true };
}

async function updatePhpDocblocks(dir) {
  let entries;
  try {
    entries = await fs.promises.readdir(dir, { withFileTypes: true });
  } catch (error) {
    if (error.code === 'ENOENT') {
      return;
    }

    throw error;
  }
  for (const entry of entries) {
    if (entry.name === 'vendor' || entry.name === 'node_modules') {
      continue;
    }

    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      await updatePhpDocblocks(fullPath);
      continue;
    }

    if (!entry.name.endsWith('.php')) {
      continue;
    }

    const content = await readFile(fullPath);
    const { content: updatedContent, changed } = updateDocblock(content);

    if (changed) {
      recordChange(fullPath, ['docblock']);
      await saveFile(fullPath, updatedContent);
    }
  }
}

function updatePluginHeader(content) {
  let changed = false;
  let updated = content;

  const replacements = [
    { regex: /(\* Description:\s*)(.*)/, value: `$1${DESCRIPTION}` },
    { regex: /(\* Author:\s*)(.*)/, value: `$1${AUTHOR.name}` },
    { regex: /(\* Author URI:\s*)(.*)/, value: `$1${AUTHOR.uri}` },
    { regex: /(\* Plugin URI:\s*)(.*)/, value: `$1${AUTHOR.uri}` },
  ];

  replacements.forEach(({ regex, value }) => {
    if (!regex.test(updated)) {
      return;
    }

    const next = updated.replace(regex, value);
    if (next !== updated) {
      changed = true;
      updated = next;
    }
  });

  return { content: updated, changed };
}

async function updateMainPluginFile() {
  const mainFile = path.join(projectRoot, 'fp-privacy-cookie-policy.php');
  try {
    const content = await readFile(mainFile);
    const { content: updatedContent, changed } = updatePluginHeader(content);

    if (changed) {
      recordChange(mainFile, ['plugin-header']);
      await saveFile(mainFile, updatedContent);
    }
  } catch (error) {
    if (error.code !== 'ENOENT') {
      throw error;
    }
  }
}

async function getPluginVersion() {
  const mainFile = path.join(projectRoot, 'fp-privacy-cookie-policy.php');
  if (!(await fileExists(mainFile))) {
    return null;
  }

  const content = await readFile(mainFile);
  const match = content.match(/^[ \t]*\* Version:\s*(.+)$/m);
  if (!match) {
    return null;
  }

  return match[1].trim();
}

async function updateReadmeTxt() {
  const readmePath = path.join(projectRoot, 'readme.txt');
  if (!(await fileExists(readmePath))) {
    return;
  }

  const raw = await readFile(readmePath);
  const lines = raw.split('\n');
  let changed = false;

  const licenseIndex = lines.findIndex((line) => line.startsWith('License URI:'));
  if (licenseIndex !== -1) {
    const shortDescriptionIndex = licenseIndex + 2;
    if (lines[shortDescriptionIndex] !== DESCRIPTION) {
      lines[shortDescriptionIndex] = DESCRIPTION;
      changed = true;
    }
  }

  const descriptionHeaderIndex = lines.indexOf('== Description ==');
  if (descriptionHeaderIndex !== -1) {
    const firstParagraphIndex = descriptionHeaderIndex + 2;
    if (lines[firstParagraphIndex] !== DESCRIPTION) {
      lines[firstParagraphIndex] = DESCRIPTION;
      changed = true;
    }
  }

  if (!changed) {
    return;
  }

  const content = lines.join('\n');
  await saveFile(readmePath, content);
  recordChange(readmePath, ['short-description']);
}

async function updateReadmeMd(version) {
  const readmePath = path.join(projectRoot, 'README.md');
  if (!(await fileExists(readmePath))) {
    return;
  }

  const raw = await readFile(readmePath);
  const lines = raw.split('\n');
  let changed = false;

  const desiredBlockquote = `> ${DESCRIPTION}`;
  const blockquoteIndex = lines.findIndex((line) => line.startsWith('> '));
  if (blockquoteIndex !== -1) {
    if (lines[blockquoteIndex] !== desiredBlockquote) {
      lines[blockquoteIndex] = desiredBlockquote;
      changed = true;
    }
  } else {
    const headingIndex = lines.findIndex((line) => line.startsWith('# '));
    if (headingIndex !== -1) {
      lines.splice(headingIndex + 1, 0, '', desiredBlockquote);
      changed = true;
    }
  }

  if (version) {
    const versionRow = `| Version | ${version} |`;
    const versionIndex = lines.findIndex((line) => line.startsWith('| Version |'));
    if (versionIndex !== -1 && lines[versionIndex] !== versionRow) {
      lines[versionIndex] = versionRow;
      changed = true;
    }
  }

  const authorRow = `| Author | [${AUTHOR.name}](${AUTHOR.uri}) |`;
  const authorIndex = lines.findIndex((line) => line.startsWith('| Author |'));
  if (authorIndex !== -1 && lines[authorIndex] !== authorRow) {
    lines[authorIndex] = authorRow;
    changed = true;
  }

  const authorEmailRow = `| Author Email | [${AUTHOR.email}](mailto:${AUTHOR.email}) |`;
  const authorEmailIndex = lines.findIndex((line) => line.startsWith('| Author Email |'));
  if (authorEmailIndex !== -1 && lines[authorEmailIndex] !== authorEmailRow) {
    lines[authorEmailIndex] = authorEmailRow;
    changed = true;
  }

  if (!changed) {
    return;
  }

  const content = lines.join('\n');
  await saveFile(readmePath, content);
  recordChange(readmePath, ['readme']);
}

async function updateDocsOverview() {
  const overviewPath = path.resolve(projectRoot, '..', 'docs', 'overview.md');
  if (!(await fileExists(overviewPath))) {
    return;
  }

  const raw = await readFile(overviewPath);
  const lines = raw.split('\n');
  const descriptionIndex = lines.findIndex((line, idx) => idx > 0 && line.trim() && !line.startsWith('#'));
  if (descriptionIndex === -1) {
    return;
  }

  if (lines[descriptionIndex] === DESCRIPTION) {
    return;
  }

  lines[descriptionIndex] = DESCRIPTION;
  const content = lines.join('\n');
  await saveFile(overviewPath, content);
  recordChange(overviewPath, ['overview-description']);
}
async function updateComposerJson() {
  const composerPath = path.join(projectRoot, 'composer.json');
  try {
    const raw = await readFile(composerPath);
    const data = JSON.parse(raw);
    let changed = false;

    const desiredAuthors = [
      {
        name: AUTHOR.name,
        email: AUTHOR.email,
        homepage: AUTHOR.uri,
        role: 'Developer',
      },
    ];

    if (JSON.stringify(data.authors || []) !== JSON.stringify(desiredAuthors)) {
      data.authors = desiredAuthors;
      changed = true;
    }

    if (data.homepage !== AUTHOR.uri) {
      data.homepage = AUTHOR.uri;
      changed = true;
    }

    const issuesUrl = data?.support?.issues || '';
    if (!data.support) {
      data.support = {};
    }

    if (issuesUrl !== SUPPORT_URL) {
      data.support.issues = SUPPORT_URL;
      changed = true;
    }

    if (changed) {
      const content = `${JSON.stringify(data, null, 4)}\n`;
      await saveFile(composerPath, content);
      recordChange(composerPath, ['authors']);
    }
  } catch (error) {
    if (error.code !== 'ENOENT') {
      throw error;
    }
  }
}

async function run() {
  if (!options.docsOnly) {
    await updateMainPluginFile();
    await updatePhpDocblocks(path.join(projectRoot, 'src'));
    await updatePhpDocblocks(path.join(projectRoot, 'inc'));
    await updateComposerJson();
    await updateReadmeTxt();
    const version = await getPluginVersion();
    await updateReadmeMd(version);
    await updateDocsOverview();
  } else {
    const version = await getPluginVersion();
    await updateReadmeTxt();
    await updateReadmeMd(version);
    await updateDocsOverview();
  }

  if (!report.length) {
    console.log('No changes needed.');
    return;
  }

  const header = ['File', 'Updated Fields'];
  const rows = report.map((item) => [item.file, item.fields.join(', ')]);
  const widths = header.map((h, idx) => Math.max(h.length, ...rows.map((row) => row[idx].length)));

  const divider = `${widths.map((w) => '-'.repeat(w + 2)).join('+')}`;
  const formatRow = (cols) => cols.map((col, idx) => ` ${col.padEnd(widths[idx])} `).join('|');

  console.log(formatRow(header));
  console.log(divider);
  rows.forEach((row) => console.log(formatRow(row)));
}

run().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
