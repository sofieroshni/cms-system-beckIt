<?php
require_once '../include/database.php';

$result = mysqli_query($connection, "SELECT * FROM pages ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Dine sider</title>
</head>
<body>

    <h1>Dine sider</h1>

    <ul>
        <?php while ($page = mysqli_fetch_assoc($result)): ?>
            <li>
                <?= htmlspecialchars($page['title']) ?>
                — status: <?= htmlspecialchars($page['status']) ?>
                — <a href="editor.php?page_id=<?= (int)$page['id'] ?>">Rediger</a>

                <form method="POST" action="delete-page.php" style="display:inline;"
                      onsubmit="return confirm('Slet siden og alle dens sektioner?');">
                    <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">
                    <button type="submit">✕</button>
                </form>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>