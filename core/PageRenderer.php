<?php
require_once __DIR__ . '/BlockRegistry.php';

class PageRenderer {
    // Henter alle blokke for en side og bygger den samlede HTML
    public static function renderPage(mysqli $connection, int $pageId): string {
        $stmt = $connection->prepare(
            "SELECT block_type, settings FROM page_blocks WHERE page_id = ? ORDER BY sort_order ASC"
        );
        $stmt->bind_param('i', $pageId);
        $stmt->execute();
        $result = $stmt->get_result();

        $html = '';
        while ($row = $result->fetch_assoc()) {
            $className = BlockRegistry::get($row['block_type']);
            if (!$className) {
                continue; // ukendt bloktype — spring over i stedet for at crashe
            }
            $data = json_decode($row['settings'], true) ?? [];
            $html .= $className::render($data);
        }
        return $html;
    }
}