<?php 
require_once __DIR__ . '/BlockInterface.php';

class TabelBlock implements BlockInterface {

    /**
     * Definerer felterne i editoren.
     * Ved at bruge et 'repeater' felt ved editoren, hvor der er plads til flere rækker.
     */
    public static function getSchema(): array {
        return [
            'overskrift' => [
                'type' => 'text', 
                'label' => 'Overskrift (valgfrit)'
            ],
            'rows_json' => [
                'type' => 'text', 
                'label' => 'Tabel data (JSON / Interaktiv)'
            ]
        ];
    }

    /**
     * Render-funktionen håndterer både visning i editor.php og preview.php
     */
    public static function render(array $data): string {
        $overskrift = htmlspecialchars($data['overskrift'] ?? '', ENT_QUOTES, 'UTF-8');
        
        // Dekod eksisterende rækker gemt fra formularen
        $rawRows = $data['rows_json'] ?? '[]';
        $rowsData = is_string($rawRows) ? json_decode($rawRows, true) : $rawRows;
        if (!is_array($rowsData)) {
            $rowsData = [];
        }

        // Generer HTML-tabelrækker ud fra gemte data
        $tableRowsHtml = '';
        foreach ($rowsData as $row) {
            $underklub = htmlspecialchars($row['underklub'] ?? '', ENT_QUOTES, 'UTF-8');
            $spilledag = htmlspecialchars($row['spilledag'] ?? '', ENT_QUOTES, 'UTF-8');
            $tidspunkt = htmlspecialchars($row['tidspunkt'] ?? '', ENT_QUOTES, 'UTF-8');

            $tableRowsHtml .= "
                <tr>
                    <td>{$underklub}</td>
                    <td>{$spilledag}</td>
                    <td>{$tidspunkt}</td>
                </tr>";
        }

        // Tilfældigt ID for at understøtte flere tabel-blokke på samme side uden JavaScript-konflikter
        $uniqId = uniqid('tabel_');

        return "
        <section class=\"tabel-block-container\" id=\"{$uniqId}\">
            " . ($overskrift ? "<h2>{$overskrift}</h2>" : "") . "
            
            <table>
                <thead>
                    <tr>
                        <th>Underklub</th>
                        <th>Spilledag</th>
                        <th>Tidspunkt</th>
                        <th class=\"builder-only-col\">Handling</th>
                    </tr>
                </thead>
                <tbody class=\"tabel-body\">
                    {$tableRowsHtml}
                </tbody>
            </table>

            <!-- Interaktiv tilføjelses-UI (skjules automatisk i preview) -->
            <div class='tabel-builder-controls'>
                <h4>Tilføj række til tabellen</h4>
                <div class='builder-inputs'>
                    <input type='text' class='input-underklub' placeholder='Underklu'>
                    <input type='text' class='input-spilledag' placeholder='Spilledag'>
                    <input type='text' class='input-tidspunkt' placeholder='Tidspunkt'>
                    <button type='button' class='btn-add-row'>+ Tilføj</button>
                </div>
            </div>

            <!-- CSS til struktur og skjul af builder-elementer på preview.php -->
            <style>
                #{$uniqId} table {
                    width: 100%;
                    border-collapse: collapse;
                }
                #{$uniqId} th, #{$uniqId} td {
                    border: 1px solid #ccc;
                    padding: 8px;
                    text-align: left;
                }
                #{$uniqId} .tabel-builder-controls {
                    margin-top: 15px;
                    padding: 10px;
                    background: #f4f4f4;
                    border: 1px dashed #aaa;
                }
                #{$uniqId} .builder-inputs {
                    display: flex;
                    gap: 10px;
                }
                /* Hvis vi IKKE er i editoren (dvs. på preview.php), skjules kontroller og slet-knapper */
                body:not(:has(.main-editor)) #{$uniqId} .tabel-builder-controls,
                body:not(:has(.main-editor)) #{$uniqId} .builder-only-col,
                body:not(:has(.main-editor)) #{$uniqId} .btn-delete-row {
                    display: none !important;
                }
            </style>

            <!-- JS der opdaterer det skjulte inputfelt i formularen når der tilføjes/slettes rækker -->
            <script>
            (function() {
                const container = document.getElementById('{$uniqId}');
                if (!container) return;

                const addBtn = container.querySelector('.btn-add-row');
                const tbody = container.querySelector('.tabel-body');

                // Find det inputfelt som editor.php skaber ud fra getSchema()
                function getHiddenJsonInput() {
                    return container.closest('.editor-section')?.querySelector('input[name*=\"[rows_json]\"]');
                }

                // Opdater JSON i det skjulte inputfelt som gemmes i databasen
                function syncDataToInput() {
                    const rows = [];
                    tbody.querySelectorAll('tr').forEach(tr => {
                        const tds = tr.querySelectorAll('td');
                        if (tds.length >= 3) {
                            rows.push({
                                underklub: tds[0].textContent.trim(),
                                spilledag: tds[1].textContent.trim(),
                                tidspunkt: tds[2].textContent.trim()
                            });
                        }
                    });

                    const jsonInput = getHiddenJsonInput();
                    if (jsonInput) {
                        jsonInput.value = JSON.stringify(rows);
                    }
                }

                // Tilføj ny række
                if (addBtn) {
                    addBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const u = container.querySelector('.input-underklub').value.trim();
                        const s = container.querySelector('.input-spilledag').value.trim();
                        const t = container.querySelector('.input-tidspunkt').value.trim();

                        if (!u || !s || !t) {
                            alert('Udfyld venligst alle 3 felter.');
                            return;
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>\${u}</td>
                            <td>\${s}</td>
                            <td>\${t}</td>
                            <td class='builder-only-col'><button type='button' class='btn-delete-row'>✕</button></td>
                        `;
                        tbody.appendChild(tr);

                        // Nulstil felter
                        container.querySelector('.input-underklub').value = '';
                        container.querySelector('.input-spilledag').value = '';
                        container.querySelector('.input-tidspunkt').value = '';

                        syncDataToInput();
                    });
                }

                // Slet række
                tbody.addEventListener('click', function(e) {
                    if (e.target.classList.contains('btn-delete-row')) {
                        e.target.closest('tr').remove();
                        syncDataToInput();
                    }
                });

                // Gem første gang hvis tom
                syncDataToInput();
            })();
            </script>
        </section>
        ";
    }
}