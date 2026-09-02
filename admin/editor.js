/* =====================================================================
   Editoren
   Holder sidens tilstand i browseren og sender den samlet, naar brugeren
   trykker Gem. Ingen sideindlaesning undervejs.

   Al validering sker paa serveren. Det her lag handler om brugerflade,
   ikke om sikkerhed — det kan aendres af enhver med en browserkonsol.
   ===================================================================== */

(function () {
    'use strict';

    const body       = document.body;
    const pageId     = body.dataset.pageId;
    const canvas     = document.getElementById('canvas');
    const saveButton = document.getElementById('save-btn');
    const statusText = document.getElementById('save-status');

    let isDirty = false;

    /* --- Aendret-tilstand ------------------------------------------- */

    function markDirty() {
        if (isDirty) {
            return;
        }
        isDirty = true;
        saveButton.disabled = false;
        saveButton.classList.add('is-dirty');
        statusText.textContent = 'Ikke gemt';
    }

    function markClean(message) {
        isDirty = false;
        saveButton.disabled = true;
        saveButton.classList.remove('is-dirty');
        statusText.textContent = message || '';
    }

    // Fanger baade tastning i tekstfelter og valg i dropdowns og
    // farvevaelgere, uanset hvornaar elementet kom ind i DOM'en.
    document.addEventListener('input', function (event) {
        if (event.target.closest('.ed-panel, .ed-settings')) {
            markDirty();
        }
    });

    // Sidste vaern mod at lukke fanen med ugemt arbejde.
    window.addEventListener('beforeunload', function (event) {
        if (isDirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    /* --- Indsamling af tilstand ------------------------------------- */

    function collectFields(block) {
        const data = { settings: {}, styles: {} };

        // Almindelige felter. Repeater-raekker bruger data-rfield og
        // fanges derfor ikke her.
        block.querySelectorAll('[data-field]').forEach(function (input) {
            const scope = input.dataset.scope;
            if (data[scope]) {
                data[scope][input.dataset.field] = input.value;
            }
        });

        // Repeater-felter bliver til en liste af objekter. Raekkefoelgen
        // i DOM'en er raekkefoelgen i listen.
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

    function collectState() {
        const page = {};

        document.querySelectorAll('[data-page-field]').forEach(function (input) {
            page[input.dataset.pageField] = input.value;
        });

        // Raekkefoelgen i DOM'en ER raekkefoelgen. Serveren udleder
        // sort_order af listens indeks, saa der findes ikke to versioner
        // af sandheden.
        const blocks = Array.from(canvas.querySelectorAll('.ed-block')).map(
            function (block) {
                const fields = collectFields(block);

                return {
                    id: block.dataset.blockId || null,
                    type: block.dataset.blockType,
                    settings: fields.settings,
                    styles: fields.styles
                };
            }
        );

        return { page: page, blocks: blocks };
    }

    /* --- Handlinger paa blokke -------------------------------------- */

    canvas.addEventListener('click', function (event) {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const block = button.closest('.ed-block');

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

                markDirty();
                break;
            }

            case 'remove-row':
                button.closest('.ed-row').remove();
                markDirty();
                break;

            case 'delete':
                if (confirm('Slet denne sektion?')) {
                    // Blokken fjernes kun i browseren. Den forsvinder
                    // foerst i databasen, naar der gemmes — og indtil da
                    // kan brugeren fortryde ved at forlade siden.
                    block.remove();
                    markDirty();
                }
                break;

            case 'up': {
                const previous = block.previousElementSibling;
                if (previous) {
                    canvas.insertBefore(block, previous);
                    markDirty();
                }
                break;
            }

            case 'down': {
                const next = block.nextElementSibling;
                if (next) {
                    canvas.insertBefore(next, block);
                    markDirty();
                }
                break;
            }
        }
    });

    /* --- Tilfoej blok ----------------------------------------------- */

    const addToggle = document.getElementById('add-toggle');
    const addMenu   = document.getElementById('add-menu');

    addToggle.addEventListener('click', function () {
        const open = addMenu.hasAttribute('hidden');
        addMenu.toggleAttribute('hidden', !open);
        addToggle.setAttribute('aria-expanded', String(open));
    });

    addMenu.addEventListener('click', function (event) {
        const choice = event.target.closest('[data-add-type]');
        if (!choice) {
            return;
        }

        const template = document.querySelector(
            '[data-template-for="' + choice.dataset.addType + '"]'
        );

        if (!template) {
            return;
        }

        // Skabelonen indeholder allerede forhaandsvisning og felter med
        // standardvaerdier, tegnet af PHP ud fra blokkens skema.
        canvas.appendChild(template.content.cloneNode(true));

        addMenu.setAttribute('hidden', '');
        addToggle.setAttribute('aria-expanded', 'false');
        markDirty();

        canvas.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    /* --- Gem --------------------------------------------------------- */

    saveButton.addEventListener('click', async function () {
        saveButton.disabled = true;
        statusText.textContent = 'Gemmer …';

        try {
            const response = await fetch('save-page.php?page_id=' + pageId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(collectState())
            });

            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ukendt fejl');
            }

            markClean('Gemt');

            // Nye blokke havde tomt id. Serveren sender de tildelte id'er
            // tilbage, saa naeste gemning opdaterer dem i stedet for at
            // oprette dem forfra.
            const blocks = canvas.querySelectorAll('.ed-block');
            (result.ids || []).forEach(function (id, index) {
                if (blocks[index]) {
                    blocks[index].dataset.blockId = id;
                }
            });

        } catch (error) {
            statusText.textContent = 'Kunne ikke gemme: ' + error.message;
            saveButton.disabled = false;
        }
    });

    /* --- Forhaandsvis ------------------------------------------------ */

    document.getElementById('preview-btn').addEventListener('click', function () {
        // Tilstanden sendes med som en almindelig POST i et nyt vindue,
        // saa forhaandsvisningen viser det ugemte arbejde. Serveren
        // renderer og gemmer intet.
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'preview.php?page_id=' + pageId;
        form.target = '_blank';

        const field = document.createElement('input');
        field.type  = 'hidden';
        field.name  = 'state';
        field.value = JSON.stringify(collectState());

        form.appendChild(field);
        document.body.appendChild(form);
        form.submit();
        form.remove();
    });
}());