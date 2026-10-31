import fs from 'node:fs/promises';
import path from 'node:path';
import { parseHTML } from 'linkedom';

const root = process.cwd();
const source = await fs.readFile(process.argv[2] || 'C:/Adapt360.html', 'utf8');
const { document } = parseHTML(source);
await fs.mkdir(path.join(root, 'docs/reference'), { recursive: true });
await fs.writeFile(path.join(root, 'docs/reference/Adapt360.html'), source);
await fs.mkdir(path.join(root, 'public/images'), { recursive: true });
const logo = document.querySelector('.brand-logo-full').getAttribute('src');
const [, format, data] = logo.match(/^data:image\/(\w+);base64,(.+)$/s);
await fs.writeFile(path.join(root, `public/images/adapt360-logo.${format}`), Buffer.from(data, 'base64'));

const pages = path.join(root, 'resources/views/adapt360/pages');
await fs.mkdir(pages, { recursive: true });

// Preserve the reference journeys and DOM IDs; visual styling comes from this app.
function cleanMarkup(element) {
    const heading = element.querySelector('.hero h3');
    if (heading) {
        const title = document.createElement('h1');
        title.innerHTML = heading.innerHTML;
        heading.replaceWith(title);
    }
    for (const node of element.querySelectorAll('[style]')) {
        const style = node.getAttribute('style');
        if (!/width:\s*\d+%/.test(style)) node.removeAttribute('style');
    }
    for (const node of element.querySelectorAll('button')) {
        node.setAttribute('type', node.getAttribute('type') || 'button');
        if (node.textContent.trim() === '\u00d7') node.setAttribute('aria-label', 'Close dialog');
    }
    for (const node of element.querySelectorAll('a[target="_blank"]')) node.setAttribute('rel', 'noopener noreferrer');
    for (const node of element.querySelectorAll('input, select, textarea')) {
        if (node.id) {
            const label = node.previousElementSibling?.tagName === 'LABEL' ? node.previousElementSibling : null;
            if (label && !label.querySelector('input')) label.setAttribute('for', node.id);
        }
    }
    return element.innerHTML.replace(/[\u{1F000}-\u{1FAFF}\u2600-\u27BF\uFE0F]/gu, '').replace(/\s+\u2014\s+/g, ' / ')
        .replaceAll('AI-style topic draft', 'Assign custom topic')
        .replaceAll('report upload, recommendations', 'report references, recommendations')
        .replaceAll("openModal('evidenceModal')", 'openEvidenceIndex()')
        .replaceAll('This is the input layer for both ideas:', 'Your workplace profile')
        .replaceAll('These shortcuts are here so the dashboard is not only eye-catching, but also useful. Tap any tile to jump directly into the connected workflow.', 'Open a connected workflow to update records and review what needs attention.');
}
for (const section of document.querySelectorAll('section.section')) {
    const content = cleanMarkup(section);
    await fs.writeFile(path.join(pages, `${section.id}.blade.php`), `@verbatim\n${content}\n@endverbatim\n`);
}
const modals = [...document.querySelectorAll('.modal')].map(modal => {
    const title = modal.querySelector('h3');
    if (title && !title.id) title.id = `${modal.id}-title`;
    return `<dialog class="adapt-modal" id="${modal.id}" aria-labelledby="${title?.id || ''}">${cleanMarkup(modal)}</dialog>`;
});
await fs.writeFile(path.join(root, 'resources/views/adapt360/dialogs.blade.php'), `@verbatim\n${modals.join('\n')}\n@endverbatim\n`);

let code = [...document.querySelectorAll('script:not([src])')].map(script => script.textContent).join('\n');
code = code.replace(/adapt360_proto_v11_state/g, 'adapt360_workspace_v1');
code = code.replace(/localStorage.getItem\('adapt360_proto_v10_state'\)[^;]+?\|\|\s*'null'/, "'null'");
code = code.replace(/function go\(id\)\{[^\n]+\}/, 'function go(id){ window.adaptNavigate?.(id); }');
code = code.replace("function openModal(id){$(id).classList.add('open')} function closeModal(id){$(id).classList.remove('open')}", "function openModal(id){ const modal=$(id); if(modal && !modal.open) modal.showModal(); } function closeModal(id){ $(id)?.close(); }");
code = code.replace(/^document.querySelectorAll\('\.modal'\).*$/gm, '');
code = code.replace("location.href.split('#')[0]+'#worker-portal'", "new URL('/workerportal',location.origin).href");
code = code.replace(/\$\{(x\.[A-Za-z]+)\}/g, '${esc($1)}');
code = code.replace(/\$\{((?:a|r|w)\.[A-Za-z]+)\}/g, '${esc($1)}');
code = code.replaceAll('${p.title}', '${esc(p.title)}').replaceAll('${p.why}', '${esc(p.why)}');
code = code.replace('${s}</span>', '${esc(s)}</span>');
code = code.replace("let c=/compliant|complete|current|approved|closed|ready|yes/i.test(s)?'green':", "let c=/not compliant|not complete|not ready/i.test(s)?'red':/compliant|complete|current|approved|closed|ready|yes/i.test(s)?'green':");
code = code.replace(/[\u{1F000}-\u{1FAFF}\u2600-\u27BF\uFE0F]/gu, '');
const functions = [...new Set([...code.matchAll(/function\s+(\w+)\s*\(/g)].map(match => match[1]))];
code = `import QRCode from 'qrcode';\nwindow.QRCode = QRCode;\n\n${code}\nObject.assign(window, { state, ${functions.join(', ')} });\n`;
await fs.writeFile(path.join(root, 'resources/js/adapt360-reference.js'), code);
console.log(JSON.stringify({ pages: document.querySelectorAll('section.section').length, dialogs:modals.length, logo:format, functions:functions.length }));
