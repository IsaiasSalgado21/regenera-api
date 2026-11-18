function parseInline(text) {
    const nodes = [];
    const pattern = /\[([^\]]+)\]\(([^)\s]+)\)/g;
    let offset = 0;
    let match;

    while ((match = pattern.exec(text))) {
        if (match.index > offset) {
            nodes.push({ type: 'text', value: text.slice(offset, match.index) });
        }

        let url = null;
        try {
            const parsed = new URL(match[2]);
            if (parsed.protocol === 'https:' && !parsed.username && !parsed.password) {
                url = parsed.href;
            }
        } catch {
            url = null;
        }

        if (url) {
            nodes.push({ type: 'link', value: match[1], url });
        } else {
            nodes.push({ type: 'text', value: match[0] });
        }
        offset = pattern.lastIndex;
    }

    if (offset < text.length) {
        nodes.push({ type: 'text', value: text.slice(offset) });
    }

    return nodes;
}

export function parseStudyGuide(markdown) {
    const blocks = [];
    let paragraph = [];
    let list = [];

    function flushParagraph() {
        if (paragraph.length) {
            blocks.push({ type: 'paragraph', children: parseInline(paragraph.join(' ')) });
            paragraph = [];
        }
    }

    function flushList() {
        if (list.length) {
            blocks.push({ type: 'list', items: list });
            list = [];
        }
    }

    for (const line of String(markdown ?? '').split(/\r?\n/)) {
        const heading = line.match(/^(#{1,3})\s+(.+?)\s*$/);
        const item = line.match(/^\s*[-*]\s+(.+?)\s*$/);

        if (!line.trim()) {
            flushParagraph();
            flushList();
        } else if (heading) {
            flushParagraph();
            flushList();
            blocks.push({
                type: 'heading',
                level: Math.min(heading[1].length + 2, 6),
                children: parseInline(heading[2]),
            });
        } else if (item) {
            flushParagraph();
            list.push(parseInline(item[1]));
        } else {
            flushList();
            paragraph.push(line.trim());
        }
    }

    flushParagraph();
    flushList();

    return blocks;
}

export function renderStudyGuide(container, markdown) {
    container.replaceChildren();

    for (const block of parseStudyGuide(markdown)) {
        const element = document.createElement(
            block.type === 'heading' ? `h${block.level}` : block.type === 'list' ? 'ul' : 'p',
        );

        if (block.type === 'list') {
            for (const item of block.items) {
                const listItem = document.createElement('li');
                appendInline(listItem, item);
                element.append(listItem);
            }
        } else {
            appendInline(element, block.children);
        }

        container.append(element);
    }
}

function appendInline(container, nodes) {
    for (const node of nodes) {
        if (node.type === 'text') {
            container.append(document.createTextNode(node.value));
            continue;
        }

        const link = document.createElement('a');
        link.href = node.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = node.value;
        container.append(link);
    }
}
