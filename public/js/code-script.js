// Code Script for DDD Symfony Guide

document.addEventListener('DOMContentLoaded', function() {
    // Process all code blocks
    document.querySelectorAll('pre code').forEach(function(codeBlock) {
        // Add line numbers
        addLineNumbers(codeBlock);

        // Add copy button
        addCopyButton(codeBlock);
    });

    // Process Augment code snippets
    processAugmentCodeSnippets();
});

// Function to add line numbers to code blocks
function addLineNumbers(codeBlock) {
    // Preserve original content for copy functionality
    const originalContent = codeBlock.textContent;
    codeBlock.setAttribute('data-original', originalContent);

    // Create line numbers container
    const lineNumbersContainer = document.createElement('div');
    lineNumbersContainer.className = 'line-numbers-rows';

    // Count lines (excluding empty last line)
    const lines = originalContent.split('\n');
    const lineCount = lines[lines.length - 1].trim() === '' ? lines.length - 1 : lines.length;

    // Add line numbers
    for (let i = 1; i <= lineCount; i++) {
        const lineNumber = document.createElement('span');
        lineNumber.textContent = i;
        lineNumbersContainer.appendChild(lineNumber);
    }

    // Insert line numbers before code
    const pre = codeBlock.parentNode;
    pre.insertBefore(lineNumbersContainer, codeBlock);
}

// Function to add copy button to code blocks
function addCopyButton(codeBlock) {
    const pre = codeBlock.parentNode;

    // Skip if already has a copy button
    if (pre.querySelector('.copy-button')) {
        return;
    }

    const copyButton = document.createElement('button');
    copyButton.className = 'copy-button';
    copyButton.textContent = 'Kopírovat';

    copyButton.addEventListener('click', () => {
        // Get original text content without line numbers
        const code = codeBlock.getAttribute('data-original') || codeBlock.textContent;

        // Copy to clipboard
        navigator.clipboard.writeText(code).then(() => {
            copyButton.textContent = 'Zkopírováno!';
            setTimeout(() => {
                copyButton.textContent = 'Kopírovat';
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
            copyButton.textContent = 'Chyba!';
            setTimeout(() => {
                copyButton.textContent = 'Kopírovat';
            }, 2000);
        });
    });

    pre.appendChild(copyButton);
}

// Function to process Augment code snippets
function processAugmentCodeSnippets() {
    document.querySelectorAll('augment_code_snippet').forEach(function(snippet) {
        // Get attributes
        const filePath = snippet.getAttribute('path') || 'example.php';
        const mode = snippet.getAttribute('mode') || 'EXCERPT';

        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'augment-code-snippet';

        // Create header
        const header = document.createElement('div');
        header.className = 'augment-code-snippet-header';

        // Create file path display
        const filePathDisplay = document.createElement('span');
        filePathDisplay.className = 'file-path';
        filePathDisplay.textContent = filePath;
        header.appendChild(filePathDisplay);

        // Create view full file button
        const viewButton = document.createElement('button');
        viewButton.className = 'view-full-file';
        viewButton.textContent = 'Zobrazit celý soubor';
        viewButton.addEventListener('click', () => {
            // This would typically open the full file in a modal or navigate to it
            alert(`Zobrazení celého souboru: ${filePath}`);
        });
        header.appendChild(viewButton);

        // Add header to wrapper
        wrapper.appendChild(header);

        // Move the pre element inside the wrapper
        const pre = snippet.querySelector('pre');
        if (pre) {
            const clonedPre = pre.cloneNode(true);
            wrapper.appendChild(clonedPre);

            // Process the code block inside
            const codeBlock = clonedPre.querySelector('code');
            if (codeBlock) {
                addLineNumbers(codeBlock);
                addCopyButton(codeBlock);
            }
        }

        // Replace the original snippet with the new wrapper
        snippet.parentNode.replaceChild(wrapper, snippet);
    });
}

// Function to handle XML tags in code
function processXmlTags() {
    document.querySelectorAll('pre code').forEach(function(codeBlock) {
        // Replace < with &lt; in XML tags
        let content = codeBlock.innerHTML;
        content = content.replace(/&lt;(\/?[a-zA-Z0-9_:-]+)&gt;/g, '<span class="xml-tag">&lt;$1&gt;</span>');
        codeBlock.innerHTML = content;
    });
}
