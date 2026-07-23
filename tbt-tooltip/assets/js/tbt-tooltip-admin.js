/**
 * TBT Tooltip — admin generator logic.
 *
 * Paste text → mouse-select fragments in the preview → each selection gets
 * a row with a tooltip field → "Generate HTML" serializes the preview into
 * the final markup for a Code block, and renders a live preview using the
 * real frontend CSS/JS.
 *
 * Everything is client-side and vanilla JS; nothing is persisted.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var input = document.getElementById('tbt-tt-input');
        var loadBtn = document.getElementById('tbt-tt-load');
        var preview = document.getElementById('tbt-tt-preview');
        var list = document.getElementById('tbt-tt-list');
        var listEmpty = document.getElementById('tbt-tt-list-empty');
        var generateBtn = document.getElementById('tbt-tt-generate');
        var output = document.getElementById('tbt-tt-output');
        var copyBtn = document.getElementById('tbt-tt-copy');
        var copyStatus = document.getElementById('tbt-tt-copy-status');
        var live = document.getElementById('tbt-tt-live');

        var titleInput   = document.getElementById('tbt-tt-title');
        var saveBtn      = document.getElementById('tbt-tt-save');
        var saveStatus   = document.getElementById('tbt-tt-save-status');
        var saveResult   = document.getElementById('tbt-tt-save-result');
        var shortcodeEl  = document.getElementById('tbt-tt-shortcode');
        var shortcodeCpy = document.getElementById('tbt-tt-shortcode-copy');
        var editLink     = document.getElementById('tbt-tt-edit-link');

        if (!input || !preview || !list) {
            return;
        }

        var idCounter = 0;

        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function updateEmptyHint() {
            listEmpty.style.display = list.children.length ? 'none' : '';
        }

        function resetOutput() {
            output.value = '';
            live.innerHTML = '';
            copyBtn.disabled = true;
            copyStatus.textContent = '';
            if (saveBtn) {
                saveBtn.disabled = true;
                titleInput.disabled = true;
                saveStatus.textContent = '';
                saveResult.style.display = 'none';
            }
        }

        /* ---------------------------------------------------------------
         * Step 1 — load pasted text into the preview, one <p> per line.
         * ------------------------------------------------------------- */
        loadBtn.addEventListener('click', function () {
            var lines = input.value.split(/\r?\n/)
                .map(function (line) { return line.trim(); })
                .filter(function (line) { return line !== ''; });

            if (!lines.length) {
                window.alert('Paste some text in Step 1 first.');
                return;
            }
            if (list.children.length &&
                !window.confirm('Loading new text removes the current selections and tooltips. Continue?')) {
                return;
            }

            preview.innerHTML = '';
            lines.forEach(function (line) {
                var p = document.createElement('p');
                p.textContent = line;
                preview.appendChild(p);
            });

            list.innerHTML = '';
            idCounter = 0;
            resetOutput();
            updateEmptyHint();
        });

        /* ---------------------------------------------------------------
         * Step 2 — turn a mouse selection inside the preview into a mark.
         * ------------------------------------------------------------- */

        // Move the range edges inward past any leading/trailing whitespace,
        // so a sloppy selection doesn't produce triggers with stray spaces.
        function trimRange(range) {
            var node = range.startContainer;
            var offset;
            if (node.nodeType === Node.TEXT_NODE) {
                offset = range.startOffset;
                while (offset < node.textContent.length && /\s/.test(node.textContent.charAt(offset))) {
                    offset++;
                }
                range.setStart(node, offset);
            }
            node = range.endContainer;
            if (node.nodeType === Node.TEXT_NODE) {
                offset = range.endOffset;
                while (offset > 0 && /\s/.test(node.textContent.charAt(offset - 1))) {
                    offset--;
                }
                range.setEnd(node, offset);
            }
        }

        function insideMark(node) {
            var el = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
            return el ? el.closest('.tbt-tt-mark') : null;
        }

        preview.addEventListener('mouseup', function () {
            var selection = window.getSelection();
            if (!selection.rangeCount || selection.isCollapsed) {
                return;
            }
            var range = selection.getRangeAt(0);
            if (!preview.contains(range.startContainer) || !preview.contains(range.endContainer)) {
                return;
            }
            if (preview.querySelector('.tbt-tt-placeholder')) {
                return;
            }

            trimRange(range);
            if (range.collapsed || range.toString().trim() === '') {
                return;
            }

            var contents = range.cloneContents();
            if (contents.querySelector('p')) {
                window.alert('Please select within a single paragraph.');
                selection.removeAllRanges();
                return;
            }
            if (contents.querySelector('.tbt-tt-mark') ||
                insideMark(range.startContainer) || insideMark(range.endContainer)) {
                window.alert('That selection overlaps a fragment that is already highlighted. Remove the existing one first.');
                selection.removeAllRanges();
                return;
            }

            var mark = document.createElement('span');
            mark.className = 'tbt-tt-mark';
            idCounter++;
            mark.dataset.ttId = String(idCounter);

            try {
                range.surroundContents(mark);
            } catch (err) {
                window.alert('That selection cannot be highlighted. Try selecting the text again.');
                selection.removeAllRanges();
                return;
            }

            selection.removeAllRanges();
            addRow(mark.dataset.ttId, mark.textContent);
            resetOutput();
        });

        /* ---------------------------------------------------------------
         * Step 3 — the phrase/tooltip list.
         * ------------------------------------------------------------- */
        function addRow(id, phrase) {
            var row = document.createElement('div');
            row.className = 'tbt-tt-row';
            row.dataset.ttId = id;

            var phraseEl = document.createElement('span');
            phraseEl.className = 'tbt-tt-row-phrase';
            phraseEl.textContent = phrase;

            var comment = document.createElement('textarea');
            comment.className = 'tbt-tt-row-comment';
            comment.rows = 2;
            comment.placeholder = 'Tooltip text…';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'button tbt-tt-row-remove';
            removeBtn.textContent = 'Remove';

            row.appendChild(phraseEl);
            row.appendChild(comment);
            row.appendChild(removeBtn);
            list.appendChild(row);

            updateEmptyHint();
            comment.focus();
        }

        list.addEventListener('click', function (event) {
            var removeBtn = event.target.closest('.tbt-tt-row-remove');
            if (!removeBtn) {
                return;
            }
            var row = removeBtn.closest('.tbt-tt-row');
            var mark = preview.querySelector('.tbt-tt-mark[data-tt-id="' + row.dataset.ttId + '"]');
            if (mark) {
                // Unwrap: put the plain text back where the mark was.
                var parent = mark.parentNode;
                while (mark.firstChild) {
                    parent.insertBefore(mark.firstChild, mark);
                }
                parent.removeChild(mark);
                parent.normalize();
            }
            row.remove();
            updateEmptyHint();
            resetOutput();
        });

        list.addEventListener('input', function (event) {
            if (event.target.classList.contains('tbt-tt-row-comment')) {
                event.target.classList.remove('tbt-tt-missing');
            }
        });

        /* ---------------------------------------------------------------
         * Step 4 — serialize the preview into final markup.
         * ------------------------------------------------------------- */
        generateBtn.addEventListener('click', function () {
            var paragraphs = preview.querySelectorAll('p:not(.tbt-tt-placeholder)');
            if (!paragraphs.length) {
                window.alert('Load some text in Step 1 first.');
                return;
            }

            var comments = {};
            list.querySelectorAll('.tbt-tt-row').forEach(function (row) {
                comments[row.dataset.ttId] = row.querySelector('.tbt-tt-row-comment');
            });

            var missing = [];
            var html = [];

            paragraphs.forEach(function (p) {
                var parts = '';
                p.childNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('tbt-tt-mark')) {
                        var field = comments[node.dataset.ttId];
                        var comment = field ? field.value.trim() : '';
                        if (!comment) {
                            missing.push(node.textContent);
                            if (field) {
                                field.classList.add('tbt-tt-missing');
                            }
                        }
                        parts += '<span class="tbt-tooltip-trigger">'
                            + '<span class="tbt-tooltip-trigger-text">' + escapeHtml(node.textContent) + '</span>'
                            + '<span class="tbt-tooltip-bubble">' + escapeHtml(comment) + '</span>'
                            + '</span>';
                    } else {
                        parts += escapeHtml(node.textContent);
                    }
                });
                html.push('<p class="tbt-tooltip-paragraph">' + parts + '</p>');
            });

            if (missing.length) {
                window.alert('Add tooltip text for: ' + missing.join(', '));
                return;
            }

            output.value = html.join('\n');
            live.innerHTML = output.value;
            copyBtn.disabled = false;
            copyStatus.textContent = '';
            titleInput.disabled = false;
            saveBtn.disabled = false;
        });

        copyBtn.addEventListener('click', function () {
            function done() {
                copyStatus.textContent = 'Copied!';
                window.setTimeout(function () { copyStatus.textContent = ''; }, 2500);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(output.value).then(done, function () {
                    fallbackCopy();
                    done();
                });
            } else {
                fallbackCopy();
                done();
            }
            function fallbackCopy() {
                output.focus();
                output.select();
                document.execCommand('copy');
            }
        });

        /* ---------------------------------------------------------------
         * Step 4 (continued) — save the generated markup as a tbt_tooltip
         * post and hand back its shortcode.
         * ------------------------------------------------------------- */
        saveBtn.addEventListener('click', function () {
            var title = titleInput.value.trim();
            if (!title) {
                window.alert('Please enter a name for the tooltip.');
                titleInput.focus();
                return;
            }
            if (!output.value.trim()) {
                window.alert('Generate the HTML first.');
                return;
            }

            saveBtn.disabled = true;
            saveStatus.textContent = 'Saving…';

            var body = new URLSearchParams();
            body.append('action', 'tbt_tooltip_save');
            body.append('nonce', tbtTooltipSave.nonce);
            body.append('title', title);
            body.append('content', output.value);

            fetch(tbtTooltipSave.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success) {
                    shortcodeEl.value = res.data.shortcode;
                    if (res.data.editUrl) {
                        editLink.href = res.data.editUrl;
                        editLink.style.display = '';
                    } else {
                        editLink.style.display = 'none';
                    }
                    saveResult.style.display = '';
                    saveStatus.textContent = 'Saved!';
                    window.setTimeout(function () { saveStatus.textContent = ''; }, 2500);
                } else {
                    var msg = (res && res.data && res.data.message) ? res.data.message : 'Save failed.';
                    saveStatus.textContent = '';
                    window.alert(msg);
                    saveBtn.disabled = false;
                }
            })
            .catch(function () {
                saveStatus.textContent = '';
                window.alert('Save failed (network error).');
                saveBtn.disabled = false;
            });
        });

        shortcodeCpy.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcodeEl.value);
            } else {
                shortcodeEl.focus();
                shortcodeEl.select();
                document.execCommand('copy');
            }
            saveStatus.textContent = 'Shortcode copied!';
            window.setTimeout(function () { saveStatus.textContent = ''; }, 2500);
        });
    });
})();
