(function () {
    'use strict';

    var table = document.getElementById('wpmotion-routes');
    var addBtn = document.getElementById('wpmotion-add-route');
    var i18n = (window.WPMOTION_ADMIN && window.WPMOTION_ADMIN.i18n) || {};
    var templates = (window.WPMOTION_ADMIN && window.WPMOTION_ADMIN.templates) || { '*': '*' };
    var presets = (window.WPMOTION_ADMIN && window.WPMOTION_ADMIN.presets) || { fade: 'fade', slide: 'slide', wipe: 'wipe', none: 'none' };

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function optionList(map, selected) {
        return Object.keys(map).map(function (value) {
            var label = map[value] || value;
            return '<option value="' + escapeHtml(value) + '"' + (value === selected ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
        }).join('');
    }

    function nextIndex() {
        var rows = table ? table.querySelectorAll('tbody .wpmotion-route') : [];
        var max = -1;
        rows.forEach(function (row) {
            var select = row.querySelector('select');
            if (!select || !select.name) {
                return;
            }
            var match = select.name.match(/\[routes\]\[(\d+)\]/);
            if (match) {
                max = Math.max(max, parseInt(match[1], 10));
            }
        });
        return max + 1;
    }

    function bindRemove(button) {
        button.addEventListener('click', function () {
            var row = button.closest('tr');
            if (row) {
                row.remove();
            }
        });
    }

    if (table) {
        table.querySelectorAll('.wpmotion-remove-route').forEach(bindRemove);
    }

    if (addBtn && table) {
        addBtn.addEventListener('click', function () {
            var index = nextIndex();
            var tr = document.createElement('tr');
            tr.className = 'wpmotion-route';
            tr.innerHTML =
                '<td><select name="wp_motion_settings[routes][' + index + '][from]">' + optionList(templates, '*') + '</select></td>' +
                '<td><select name="wp_motion_settings[routes][' + index + '][to]">' + optionList(templates, '*') + '</select></td>' +
                '<td><select name="wp_motion_settings[routes][' + index + '][preset]">' + optionList(presets, 'fade') + '</select></td>' +
                '<td><label><input type="checkbox" name="wp_motion_settings[routes][' + index + '][shared]" value="1"> ' + (i18n.shared || '') + '</label></td>' +
                '<td><button type="button" class="button-link-delete wpmotion-remove-route">' + (i18n.remove || 'Supprimer') + '</button></td>';
            table.querySelector('tbody').appendChild(tr);
            bindRemove(tr.querySelector('.wpmotion-remove-route'));
        });
    }

    var resetRoutes = document.getElementById('wpmotion-reset-routes');
    if (resetRoutes) {
        resetRoutes.addEventListener('click', function (event) {
            var message = i18n.resetConfirm || '';
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    var openFrom = document.getElementById('wpmotion-open-from');
    if (openFrom) {
        openFrom.addEventListener('click', function () {
            var from = document.getElementById('wpmotion-from');
            if (from && from.value) {
                window.open(from.value, '_blank', 'noopener');
            }
        });
    }
})();
