<?php
require_once '../include/database.php';
require_once '../components/Navbar.php';
require_once '../components/FooterGuide.php';

$result = mysqli_query($connection, "SELECT * FROM pages ORDER BY sort_order ASC");
?>

<!DOCTYPE html>
<html lang="da">

<head>
    <meta charset="UTF-8">
    <title>Dine sider</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<section class="admin-section">

    <h1 class="admin-h1">Dine sider</h1>

    <ul class="bjælker">

        <?php while ($page = mysqli_fetch_assoc($result)): ?>

            <li class="bjælke">

                <div class="left-side">

                    <div class="move-dots">
                        <p>:::</p>
                    </div>

                    <h3 class="page-title">
                        <?= htmlspecialchars($page['title']) ?>
                    </h3>

                </div>

                <a href="preview.php?page_id=<?= (int)$page['id'] ?>" style="color:black;">
                    Preview
                </a>

                <div class="icons">

                    <?php if ($page['status'] === 'published'): ?>

                        <p class="status published">Udgivet</p>

                    <?php else: ?>

                        <p class="status unpublished">Kladde</p>

                    <?php endif; ?>


                    <a href="editor.php?page_id=<?= (int)$page['id'] ?>">
                        <button class="rediger" type="button">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </a>


                    <form method="POST"
                          action="delete-page.php"
                          style="display:inline;"
                          onsubmit="return confirm('Slet siden og alle dens sektioner?');">

                        <input type="hidden"
                               name="page_id"
                               value="<?= (int)$page['id'] ?>">

                        <button type="submit" class="delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>

                    </form>

                    <h3 class="page-title">
                        <?= htmlspecialchars($page['id']) ?>
                    </h3>

                </div>

            </li>

        <?php endwhile; ?>

    </ul>


    <a href="create-page.php" class="addpage">
        Opret side +
    </a>

</section>



<style>

.addpage {
    color: white;
    width: 100%;
    display: flex;
    justify-content: center;
    border: 1px dashed #F0AD72;
    font-family: 'Jost', sans-serif;
    color: #F0AD72;
    height: 42px;
    font-weight: 700;
    align-items: center;
    text-align: center;
    flex-direction: column;
}


</style>

</body>
</html>