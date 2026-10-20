<script>
document.addEventListener('DOMContentLoaded', function () {
    function visibleRows(group) {
        return Array.prototype.filter.call(group.querySelectorAll('[data-repeater-row]'), function (row) {
            return !row.hasAttribute('data-repeater-template');
        });
    }

    function setRowIndex(row, index, group) {
        var prefix = group.getAttribute('data-input-prefix') || '';
        var idPrefix = group.getAttribute('data-id-prefix') || '';
        row.setAttribute('data-repeater-index', String(index));

        row.querySelectorAll('[data-subkey]').forEach(function (el) {
            var sub = el.getAttribute('data-subkey') || '';
            el.name = prefix + '[' + index + '][' + sub + ']';
            if (el.hasAttribute('data-repeater-id')) {
                el.id = idPrefix + '-' + index + '-' + sub;
            }
        });

        row.querySelectorAll('[data-subkey-label]').forEach(function (label) {
            var sub = label.getAttribute('data-subkey-label') || '';
            var control = null;
            row.querySelectorAll('[data-repeater-id]').forEach(function (el) {
                if (el.getAttribute('data-subkey') === sub) {
                    control = el;
                }
            });
            if (control) {
                label.setAttribute('for', control.id);
            }
        });

        row.querySelectorAll('output[data-repeater-output]').forEach(function (out) {
            var range = out.parentElement ? out.parentElement.querySelector('[data-repeater-range]') : null;
            if (range) {
                out.value = range.value;
                out.textContent = range.value;
            }
        });
    }

    function reindex(group) {
        visibleRows(group).forEach(function (row, index) {
            setRowIndex(row, index, group);
        });
    }

    function bindRemove(row, group) {
        var btn = row.querySelector('.repeater-remove');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function () {
            row.remove();
            reindex(group);
        });
    }

    document.querySelectorAll('.repeater-group').forEach(function (group) {
        var template = group.querySelector('[data-repeater-template]');
        var add = group.parentElement ? group.parentElement.querySelector('[data-repeater-add]') : null;
        if (!template || !add) {
            return;
        }

        visibleRows(group).forEach(function (row, index) {
            setRowIndex(row, index, group);
            bindRemove(row, group);
        });

        add.addEventListener('click', function () {
            var rows = visibleRows(group);
            var clone = template.cloneNode(true);
            clone.removeAttribute('data-repeater-template');
            clone.removeAttribute('hidden');
            clone.querySelectorAll('[disabled]').forEach(function (el) {
                el.removeAttribute('disabled');
            });
            group.appendChild(clone);
            setRowIndex(clone, rows.length, group);
            bindRemove(clone, group);
        });
    });

    document.addEventListener('input', function (event) {
        var input = event.target;
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        if (input.hasAttribute('data-range-output')) {
            var output = document.getElementById(input.getAttribute('data-range-output') || '');
            if (output) {
                output.value = input.value;
                output.textContent = input.value;
            }
        }

        if (input.hasAttribute('data-repeater-range')) {
            var sibling = input.parentElement ? input.parentElement.querySelector('output') : null;
            if (sibling) {
                sibling.value = input.value;
                sibling.textContent = input.value;
            }
        }
    });

    document.addEventListener('change', function (event) {
        var select = event.target;
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }
        if (select.hasAttribute('data-font-preview')) {
            var preview = document.getElementById(select.getAttribute('data-font-preview') || '');
            if (preview) {
                preview.style.fontFamily = select.value;
            }
        }
    });
});
</script>
