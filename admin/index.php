<?php
require_once '../include/database.php';
require_once '../blocks/Navbar.php';


$result = mysqli_query($connection, "SELECT * FROM pages ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Dine sider</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
   
<section class=admin-section>
        <h1 class="admin-h1">Dine sider</h1>

    <ul class="bjælker" >
        <?php while ($page = mysqli_fetch_assoc($result)): ?>
            <li class="bjælke">
                <div class="move-dots"> <p >:::</p></div>
              <h3 class="page-title">  <?= htmlspecialchars($page['title']) ?></h3>
                — status: <?= htmlspecialchars($page['status']) ?>
                — <a href="editor.php?page_id=<?= (int)$page['id'] ?>">Rediger</a>

                <form method="POST" action="delete-page.php" style="display:inline;"
                      onsubmit="return confirm('Slet siden og alle dens sektioner?');">
                    <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">
                    <button type="submit">✕</button>
                </form>
            <h3 class="page-title">  <?= htmlspecialchars($page['id']) ?></h3> <!*spørg khalid Igen om dette her pga.der måske mangler auto-incremcement*!>

                
            </li>
        <?php endwhile; ?>
    </ul>
    <a href="create-page.php" class="addpage">Tifløj side +</button>   </a>  

</section>
    
</body>
</html>
<style>
.addpage{
    color:white;
    width:100%;
    display:flex;
    justify-content:center;
     border: 1px dashed #F0AD72;
   font-family: 'Jost', sans-serif; /*spørg khalid om det inter eller jost!**/
     color: #F0AD72;
     height:42px;
display:flex;
font-weight: 700;
align-items:center;
text-align:center;
flex-direction:column;

}
</style>