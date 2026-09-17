/* =====================================================================
   Traek-og-slip i sidelisten.

   Raekkefoelgen gemmes med det samme. Der er ingen gem-knap her, fordi
   handlingen er enkelt afgraenset — man traekker én raekke ét sted hen.
   ===================================================================== */

(function () {
    'use strict';

    const list = document.getElementById('page-list');

    if (!list) {
        return;
    }

    const status = document.getElementById('list-status');
    let dragged = null;

    /* --- Skift status ------------------------------------------------ */

    list.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-action="toggle-status"]');

        if (!button) {
            return;
        }

        const goingLive = button.dataset.status !== 'published';

        const message = goingLive
            ? 'Vil du udgive \u00bb' + button.dataset.title + '\u00ab?'
            : 'Vil du saette \u00bb' + button.dataset.title + '\u00ab tilbage til kladde?';

        // Paamindelsen er med, fordi status og udgivelse er to ting.
        // En side bliver ikke synlig paa websitet, foer sitet bygges.
        if (!confirm(message + '\n\nAendringen slaar foerst igennem paa websitet, naar du bygger det under Udgiv.')) {
            return;
        }

        const row = button.closest('.page-row');

        button.disabled = true;
        status.textContent = 'Skifter status \u2026';

        try {
            const response = await fetch('toggle-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: row.dataset.pageId })
            });

            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ukendt fejl');
            }

            button.dataset.status = result.status;
            button.textContent = result.label;
            button.classList.toggle('badge--published', result.status === 'published');
            button.classList.toggle('badge--draft', result.status === 'draft');

            status.textContent = 'Status aendret';
            setTimeout(function () {
                status.textContent = '';
            }, 2000);

        } catch (error) {
            status.textContent = 'Kunne ikke skifte status: ' + error.message;
        } finally {
            button.disabled = false;
        }
    });

    /* --- Start og slut ---------------------------------------------- */

    // Kun grebet starter et traek. Var hele raekken traekbar, ville man
    // ikke kunne markere titlen med musen.
    list.addEventListener('mousedown', function (event) {
        const handle = event.target.closest('.page-row__handle');

        // Laaste greb hoerer til undersider. De sorteres sammen med deres
        // foraelder og kan ikke traekkes frit rundt i listen.
        if (handle && !handle.classList.contains('is-locked')) {
            handle.closest('.page-row').draggable = true;
        }
    });

    list.addEventListener('dragstart', function (event) {
        dragged = event.target.closest('.page-row');

        if (!dragged) {
            return;
        }

        dragged.classList.add('is-dragging');

        // Firefox starter ikke et traek, medmindre der er sat data.
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', dragged.dataset.pageId);
    });

    list.addEventListener('dragend', function () {
        if (dragged) {
            dragged.classList.remove('is-dragging');
            dragged.draggable = false;
            dragged = null;
        }

        list.querySelectorAll('.is-over').forEach(function (row) {
            row.classList.remove('is-over');
        });
    });

    /* --- Undervejs --------------------------------------------------- */

    list.addEventListener('dragover', function (event) {
        // Uden preventDefault afviser browseren droppet som standard.
        event.preventDefault();

        const target = event.target.closest('.page-row');

        if (!target || target === dragged || !dragged) {
            return;
        }

        list.querySelectorAll('.is-over').forEach(function (row) {
            row.classList.remove('is-over');
        });
        target.classList.add('is-over');

        // Afgoer ud fra musens position, om raekken skal ind foer eller
        // efter den, man svaever over. Uden det ville man aldrig kunne
        // placere noget nederst i listen.
        const box = target.getBoundingClientRect();
        const below = event.clientY > box.top + box.height / 2;

        list.insertBefore(dragged, below ? target.nextSibling : target);
    });

    /* --- Slip og gem -------------------------------------------------- */

    list.addEventListener('drop', function (event) {
        event.preventDefault();
        saveOrder();
    });

    async function saveOrder() {
        const ids = Array.from(list.querySelectorAll('.page-row')).map(
            function (row) {
                return row.dataset.pageId;
            }
        );

        renumber();
        status.textContent = 'Gemmer raekkefoelge …';

        try {
            const response = await fetch('reorder-pages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            });

            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ukendt fejl');
            }

            status.textContent = 'Raekkefoelge gemt';

            // Kvitteringen forsvinder af sig selv. Den er en bekraeftelse,
            // ikke information brugeren skal handle paa.
            setTimeout(function () {
                status.textContent = '';
            }, 2000);

        } catch (error) {
            status.textContent = 'Kunne ikke gemme: ' + error.message;
        }
    }

    /* Numrene i hoejre side foelger den nye raekkefoelge med det samme,
       saa listen ikke modsiger sig selv, mens der gemmes. */
    function renumber() {
        list.querySelectorAll('.page-row__order').forEach(function (cell, index) {
            cell.textContent = String(index + 1);
        });
    }
}());