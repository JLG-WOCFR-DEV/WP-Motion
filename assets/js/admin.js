(function () {
    'use strict';

    var table = document.getElementById('wpgsap-routes');
    var addBtn = document.getElementById('wpgsap-add-route');
    var i18n = (window.WPGSAP_ADMIN && window.WPGSAP_ADMIN.i18n) || {};
    var templates = (window.WPGSAP_ADMIN && window.WPGSAP_ADMIN.templates) || ['*'];
    var presets = (window.WPGSAP_ADMIN && window.WPGSAP_ADMIN.presets) || ['fade', 'slide', 'wipe', 'none'];

    function optionList(values, selected) {
        return values.map(function (value) {
            return '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + value + '</option>';
        }).join('');
    }

    function nextIndex() {
        var rows = table ? table.querySelectorAll('tbody .wpgsap-route') : [];
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
        table.querySelectorAll('.wpgsap-remove-route').forEach(bindRemove);
    }

    if (addBtn && table) {
        addBtn.addEventListener('click', function () {
            var index = nextIndex();
            var tr = document.createElement('tr');
            tr.className = 'wpgsap-route';
            tr.innerHTML =
                '<td><select name="wp_gsap_settings[routes][' + index + '][from]">' + optionList(templates, '*') + '</select></td>' +
                '<td><select name="wp_gsap_settings[routes][' + index + '][to]">' + optionList(templates, '*') + '</select></td>' +
                '<td><select name="wp_gsap_settings[routes][' + index + '][preset]">' + optionList(presets, 'fade') + '</select></td>' +
                '<td><label><input type="checkbox" name="wp_gsap_settings[routes][' + index + '][shared]" value="1"> ' + (i18n.shared || '') + '</label></td>' +
                '<td><button type="button" class="button-link-delete wpgsap-remove-route">' + (i18n.remove || 'Supprimer') + '</button></td>';
            table.querySelector('tbody').appendChild(tr);
            bindRemove(tr.querySelector('.wpgsap-remove-route'));
        });
    }

    var openFrom = document.getElementById('wpgsap-open-from');
    if (openFrom) {
        openFrom.addEventListener('click', function () {
            var from = document.getElementById('wpgsap-from');
            if (from && from.value) {
                window.open(from.value, '_blank', 'noopener');
            }
        });
    }
})();
