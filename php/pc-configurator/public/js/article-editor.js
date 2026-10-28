document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('markdown-body');
    const preview = document.getElementById('article-preview-body');
    const toolbar = document.getElementById('markdown-toolbar');
    if (!editor || !preview || !toolbar) return;

    const escape = value => value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    const inline = value => {
        let text = escape(value);
        text = text.replace(/!\[([^\]]*)\]\((https:\/\/[^\s)]+)\)/g, (all, alt, url) => {
            try { if (new URL(url.replace(/&amp;/g, '&')).protocol !== 'https:') return all; } catch { return all; }
            return `<img src="${url}" alt="${alt}" loading="lazy">`;
        });
        text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, (all, label, url) => {
            try { if (!['http:', 'https:'].includes(new URL(url.replace(/&amp;/g, '&')).protocol)) return label; } catch { return label; }
            return `<a href="${url}" target="_blank" rel="noopener noreferrer">${label}</a>`;
        });
        return text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>').replace(/`([^`]+)`/g, '<code>$1</code>');
    };
    function render() {
        const lines = editor.value.split(/\r?\n/);
        let html = '', list = '', code = false, codeLines = [];
        const close = () => { if (list) { html += `</${list}>`; list = ''; } };
        for (const line of lines) {
            if (/^\s*```/.test(line)) {
                close();
                if (code) { html += `<pre><code>${escape(codeLines.join('\n'))}</code></pre>`; codeLines = []; }
                code = !code;
                continue;
            }
            if (code) { codeLines.push(line); continue; }
            if (!line.trim()) { close(); continue; }
            const heading = line.match(/^(#{1,3})\s+(.+)$/);
            if (heading) { close(); const n = heading[1].length + 1; html += `<h${n}>${inline(heading[2])}</h${n}>`; continue; }
            const bullet = line.match(/^\s*[-*]\s+(.+)$/);
            const ordered = line.match(/^\s*\d+\.\s+(.+)$/);
            if (bullet || ordered) {
                const next = bullet ? 'ul' : 'ol';
                if (list !== next) { close(); html += `<${next}>`; list = next; }
                html += `<li>${inline((bullet || ordered)[1])}</li>`;
                continue;
            }
            close(); html += `<p>${inline(line)}</p>`;
        }
        close();
        if (code) html += `<pre><code>${escape(codeLines.join('\n'))}</code></pre>`;
        preview.innerHTML = html || '<p>Текст статьи появится здесь.</p>';
    }
    toolbar.addEventListener('click', event => {
        const button = event.target.closest('button[data-markdown]');
        if (!button) return;
        const start = editor.selectionStart, end = editor.selectionEnd;
        const selected = editor.value.slice(start, end);
        const snippets = {
            heading: `## ${selected || 'Заголовок'}`,
            bold: `**${selected || 'жирный текст'}**`,
            italic: `*${selected || 'курсив'}*`,
            list: `- ${selected || 'Элемент списка'}`,
            link: `[${selected || 'Текст ссылки'}](https://example.com)`,
            image: `![${selected || 'Описание изображения'}](https://example.com/image.jpg)`,
            code: `\`${selected || 'код'}\``
        };
        const value = snippets[button.dataset.markdown];
        if (!value) return;
        editor.setRangeText(value, start, end, 'select');
        editor.focus();
        render();
    });
    editor.addEventListener('input', render);
    render();
});
