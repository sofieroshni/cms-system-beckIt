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

    /* --- Start og slut ---------------------------------------------- */

    // Kun grebet starter et traek. Var hele raekken traekbar, ville man
    // ikke kunne markere titlen med musen.
    list.addEventListener('mousedown', function (event) {
        const handle = event.target.closest('.page-row__handle');

        if (handle) {
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

            status.textContent = 'Rækkefølge gemt';

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