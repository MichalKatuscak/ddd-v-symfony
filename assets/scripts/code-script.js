// code-script.js — Dark Mode Code Blocks

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('pre code').forEach(function (codeBlock) {
        addLineNumbers(codeBlock);
        addCodeBlockHeader(codeBlock);
    });

    processAugmentCodeSnippets();
});

// Detect language from highlight.js class (e.g. "language-php" → "PHP")
function detectLanguage(codeBlock) {
    const classes = Array.from(codeBlock.classList);
    for (const cls of classes) {
        if (cls.startsWith('language-')) {
            return cls.replace('language-', '').toUpperCase();
        }
    }
    // Fallback: check hljs data attribute
    if (codeBlock.classList.contains('hljs')) {
        const hljsLang = codeBlock.getAttribute('data-highlighted-language');
        if (hljsLang) return hljsLang.toUpperCase();
    }
    return null;
}

// Wrap <pre> in .code-block-wrapper and inject header with language badge + copy button
function addCodeBlockHeader(codeBlock) {
    const pre = codeBlock.parentNode;
    if (pre.tagName !== 'PRE') return;
    if (pre.parentNode.classList.contains('code-block-wrapper')) return; // already wrapped

    // Build wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'code-block-wrapper';

    // Build header
    const header = document.createElement('div');
    header.className = 'code-block-header';

    const lang = detectLanguage(codeBlock);

    // Left side: language badge + optional filename
    const leftSide = document.createElement('div');
    leftSide.style.display = 'flex';
    leftSide.style.alignItems = 'center';
    leftSide.style.gap = '0.5rem';
    leftSide.style.minWidth = '0';

    if (lang) {
        const badge = document.createElement('span');
        badge.className = 'code-lang-badge';
        badge.textContent = lang;
        leftSide.appendChild(badge);
    }

    // Optional filename from data-filename attribute on <pre>
    const filename = pre.getAttribute('data-filename');
    if (filename) {
        const filenameSpan = document.createElement('span');
        filenameSpan.className = 'code-filename';
        filenameSpan.textContent = filename;
        leftSide.appendChild(filenameSpan);
    }

    header.appendChild(leftSide);

    // Copy button
    const copyBtn = document.createElement('button');
    copyBtn.className = 'copy-button';
    copyBtn.textContent = 'Kopírovat'; // matches existing Czech label
    copyBtn.addEventListener('click', function () {
        const code = codeBlock.getAttribute('data-original') || codeBlock.textContent;
        navigator.clipboard.writeText(code).then(function () {
            copyBtn.textContent = 'Zkopírováno!';
            copyBtn.classList.add('copied');
            setTimeout(function () {
                copyBtn.textContent = 'Kopírovat';
                copyBtn.classList.remove('copied');
            }, 2000);
        }).catch(function (err) {
            console.error('Copy failed:', err);
            copyBtn.textContent = 'Chyba!';
            setTimeout(function () { copyBtn.textContent = 'Kopírovat'; }, 2000);
        });
    });
    header.appendChild(copyBtn);

    // Insert wrapper: replace <pre> with wrapper containing header + pre
    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(header);
    wrapper.appendChild(pre);
}

// Add line numbers — unchanged from original
function addLineNumbers(codeBlock) {
    const originalContent = codeBlock.textContent;
    codeBlock.setAttribute('data-original', originalContent);

    const lineNumbersContainer = document.createElement('div');
    lineNumbersContainer.className = 'line-numbers-rows';

    const lines = originalContent.split('\n');
    const lineCount = lines[lines.length - 1].trim() === '' ? lines.length - 1 : lines.length;

    for (let i = 1; i <= lineCount; i++) {
        const lineNumber = document.createElement('span');
        lineNumber.textContent = i;
        lineNumbersContainer.appendChild(lineNumber);
    }

    const pre = codeBlock.parentNode;
    pre.insertBefore(lineNumbersContainer, codeBlock);
}

// processAugmentCodeSnippets — unchanged from original
function processAugmentCodeSnippets() {
    document.querySelectorAll('augment_code_snippet').forEach(function (snippet) {
        const filePath = snippet.getAttribute('path') || 'example.php';

        const wrapper = document.createElement('div');
        wrapper.className = 'augment-code-snippet';

        const header = document.createElement('div');
        header.className = 'augment-code-snippet-header';

        const filePathDisplay = document.createElement('span');
        filePathDisplay.className = 'file-path';
        filePathDisplay.textContent = filePath;
        header.appendChild(filePathDisplay);

        const viewButton = document.createElement('button');
        viewButton.className = 'view-full-file';
        viewButton.textContent = 'Zobrazit celý soubor';
        viewButton.addEventListener('click', function () {
            console.log('Zobrazení celého souboru: ' + filePath);
        });
        header.appendChild(viewButton);

        wrapper.appendChild(header);

        const pre = snippet.querySelector('pre');
        if (pre) {
            const clonedPre = pre.cloneNode(true);
            wrapper.appendChild(clonedPre);
            const codeBlock = clonedPre.querySelector('code');
            if (codeBlock) {
                addLineNumbers(codeBlock);
                addCodeBlockHeader(codeBlock);
            }
        }

        snippet.parentNode.replaceChild(wrapper, snippet);
    });
}

function processXmlTags() {
    document.querySelectorAll('pre code').forEach(function (codeBlock) {
        let content = codeBlock.innerHTML;
        content = content.replace(/&lt;(\/?[a-zA-Z0-9_:-]+)&gt;/g, '<span class="xml-tag">&lt;$1&gt;</span>');
        codeBlock.innerHTML = content;
    });
}
