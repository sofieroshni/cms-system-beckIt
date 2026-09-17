/* =====================================================================
   Delte blokke.

   Bevidst mindre end editor.js: her gemmes én blok ad gangen, saa der er
   ingen samlet tilstand at holde styr paa og intet at gemme paa tvaers.
   ===================================================================== */

(function () {
    'use strict';

    const list = document.getElementById('shared-list');

    if (!list) {
        return;
    }

    /* Samme opsamling som i sideeditoren: almindelige felter via
       data-field, repeater-raekker via data-rfield. */
    function collectFields(block) {
        const data = { settings: {}, styles: {} };

        block.querySelectorAll('[data-field]').forEach(function (input) {
            const scope = input.dataset.scope;
            if (data[scope]) {
                data[scope][input.dataset.field] = input.value;
            }
        });

        block.querySelectorAll('[data-repeater]').forEach(function (repeater) {
            data.settings[repeater.dataset.repeater] = Array.from(
                repeater.querySelectorAll('.ed-row')
            ).map(function (row) {
                const values = {};
                row.querySelectorAll('[data-rfield]').forEach(function (input) {
                    values[input.dataset.rfield] = input.value;
                });
                return values;
            });
        });

        return data;
    }

    list.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-action]');

        if (!button) {
            return;
        }

        const block = button.closest('.shared-block');

        switch (button.dataset.action) {
            case 'edit': {
                const panel = block.querySelector('.ed-panel');
                const open = panel.hasAttribute('hidden');
                panel.toggleAttribute('hidden', !open);
                button.setAttribute('aria-expanded', String(open));
                break;
            }

            case 'add-row': {
                const repeater = button.closest('[data-repeater]');
                const template = repeater.querySelector('[data-row-template]');
                repeater
                    .querySelector('.ed-repeater__rows')
                    .appendChild(template.content.cloneNode(true));
                break;
            }

            case 'remove-row':
                button.closest('.ed-row').remove();
                break;

            case 'save-shared':
                await save(block, button);
                break;
        }
    });

    async function save(block, button) {
        const status = block.querySelector('[data-shared-status]');
        const fields = collectFields(block);

        button.disabled = true;
        status.textContent = 'Gemmer …';

        try {
            const response = await fetch('save-shared.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: block.dataset.sharedId,
                    name: block.querySelector('[data-shared-name]').value,
                    settings: fields.settings,
                    styles: fields.styles
                })
            });

            const raw = await response.text();
            let result;

            try {
                result = JSON.parse(raw);
            } catch (parseError) {
                console.error('Serveren svarede ikke med JSON:', raw);
                throw new Error('Serveren svarede uventet. Se konsollen (F12).');
            }

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ukendt fejl');
            }

            // Forhaandsvisningen i denne skaerm opdateres ikke uden en
            // genindlaesning, saa brugeren faar at vide, hvorfor billedet
            // ovenover stadig ser ud som foer.
            status.textContent = 'Gemt — genindlaes for at se aendringen';

        } catch (error) {
            status.textContent = 'Kunne ikke gemme: ' + error.message;
        } finally {
            button.disabled = false;
        }
    }

    /* Billedupload, samme flow som i sideeditoren. */
    list.addEventListener('change', async function (event) {
        const fileInput = event.target.closest('.ed-image__file');

        if (!fileInput || !fileInput.files.length) {
            return;
        }

        const wrapper = fileInput.closest('.ed-image');
        const data = new FormData();
        data.append('image', fileInput.files[0]);

        try {
            const response = await fetch('upload-image.php', {
                method: 'POST',
                body: data
            });

            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ukendt fejl');
            }

            wrapper.querySelector('.ed-image__path').value = result.path;

            const preview = wrapper.querySelector('.ed-image__preview');
            preview.innerHTML = '';

            const image = document.createElement('img');
            image.src = document.body.dataset.basePath + '/' + result.path;
            image.alt = '';
            preview.appendChild(image);

        } catch (error) {
            console.error('Upload fejlede:', error);
            alert('Billedet kunne ikke uploades: ' + error.message);
        } finally {
            fileInput.value = '';
        }
    });
}());