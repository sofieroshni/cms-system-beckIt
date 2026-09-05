<?php
require_once '../include/database.php';
require_once '../core/BlockRegistry.php';

$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

$stmt = $connection->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->bind_param('i', $pageId);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    die('Siden blev ikke fundet.');
}

$stmt = $connection->prepare("SELECT * FROM page_blocks WHERE page_id = ? ORDER BY sort_order ASC");
$stmt->bind_param('i', $pageId);
$stmt->execute();
$blocks = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Rediger: <?= htmlspecialchars($page['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<main class="main-editor">
    
 <!-- ====== Sideindstillinger (egen form, uafhængig af blokkene) ====== -->
    <form method="POST" action="save-page.php" class="page-settings">
        <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

        <label>Titel:
            <input type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>
        </label>

        <label>Slug:
            <input type="text" name="slug" value="<?= htmlspecialchars($page['slug']) ?>" required>
        </label>

        <label>Status:
            <select name="status">
                <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Kladde</option>
                <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Udgivet</option>
            </select>
        </label>

        <button type="submit">Gem sideindstillinger</button>
    </form>
    <hr>

    <h1>Redigerer: <?= htmlspecialchars($page['title']) ?></h1>

    <!-- ====== Alle blokke i ÉN form — dette er "gem alt" ====== -->
    <form method="POST" action="save-all-blocks.php" id="all-blocks-form">
        <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

        <div class="editor-sections">
            <?php while ($block = mysqli_fetch_assoc($blocks)): ?>
                <?php
                    $className = BlockRegistry::get($block['block_type']);
                    $data      = json_decode($block['settings'], true) ?? [];
                    $schema    = $className ? $className::getSchema() : [];
                    $blockId   = (int)$block['id'];
                ?>
                <div class="editor-section" data-block-id="<?= $blockId ?>">
<a href="delete-block.php?block_id=<?= $blockId ?>&page_id=<?= (int)$page['id'] ?>"
                       class="delete-button"
                     >
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>
                    
                    <span class="section-label"><?= htmlspecialchars($block['block_type']) ?></span>

                    <div class="section-preview" onClick="editFunction">
                        <?= $className ? $className::render($data) : '(ukendt blok-type)' ?>
                    </div>
<div class="input-felter" id="inputfelter">
                    <?php foreach ($schema as $fieldName => $fieldConfig): ?>
                  <div class="label-name">     <label class="label">
                            <?= htmlspecialchars($fieldConfig['label']) ?>:
                            <?php if ($fieldConfig['type'] === 'richtext'): ?>
                                <textarea name="blocks[<?= $blockId ?>][<?= htmlspecialchars($fieldName) ?>]"><?= htmlspecialchars($data[$fieldName] ?? '') ?></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="blocks[<?= $blockId ?>][<?= htmlspecialchars($fieldName) ?>]"
                                       value="<?= htmlspecialchars($data[$fieldName] ?? '') ?>">
                            <?php endif; ?>
                        </label></div><br>
                    <?php endforeach; ?></div>
                </div>
                <hr>
            <?php endwhile; ?>
        </div> 
    </form>

    <!-- Tilføj ny blok (egen form, ligger  uden for while-loopet) -->
    <div class="add-block">
       <form method="POST" action="add-block.php">
        <input type="hidden" name="page_id" value="<?=  (int)$page['id']?>">
                                <div class="buttons">
                                   <?php foreach (BlockRegistry::all() as $type => $class): ?>
                                    <!-- gemmer alle key som $type og key som $class -->
                                    <button class="button orange" type="submit"
                                     name="block_type" value="<?= (htmlspecialchars($type))?>" 
                                     >
                                     <?=  htmlspecialchars(ucfirst($type)) ?>
                                    </button> 
                                    <?php  endforeach; ?>
                                </div>
    </input>
    </form>
    </div>

</main>
   

    <footer>
        <div class="footer-buttons">
            <a class="btn" href="preview.php?page_id=<?= (int)$page['id'] ?>">
                <button type="button" class="btn eye"><i class="fa-solid fa-eye"></i> Show</button>
            </a>
            <button type="button" class="btn cta">Publish</button>

            <!-- Denne knap submitter all-blocks-form selvom den ligger uden for den -->
            <button type="submit" form="all-blocks-form" class="btn">Gem</button>
        </div>
    </footer>
</body>
</html>
<style>
    main.main-editor{
        background-color: red;
        display:flex;
        flex-direction:column;
        justify-content:center;
        text-align:center;
        width:100%;
        align-items:center;
        margin-bottom: 110px;

    }
    body {
        font-family: 'Jost', sans-serif;
        justify-content: center;
        align-items: center;
    }
    a {
        text-decoration: none;
    }
    button {
        background-color: blue;
        color: white;
        border-radius: 5px;
        padding: 10px;
        margin: 10px;
        border: none;
    }
    button.cta {
        background-color: green;
        color: white;
        border-radius: 5px;
        padding: 10px;
        margin: 10px;
        border: none;
    }
    button.eye {
        display: flex;
        align-items: center;
    }
    button.eye i {
        margin-right: 10px;
    }
    .delete-button {
   color:red;
    padding-right:100%;
    z-index:1;
    top:0;
    position:absolute;

    }
    .fa-circle-xmark {
        background-color: transparent;
    }
    footer {
        display: flex;
        justify-content: end;
        margin-top: 20px;
        height: 100px;
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: red;
    }
    .footer-buttons {
        display: flex;
        justify-content: end;
        align-items: center;
        width: 50%;
        background-color: purple;
    }
    .editor-sections{
        background-color: blue;
        display:flex;
        justify-content:center;
        align-items:center;
        flex-direction:column;
        width:100%;
    }
    /* //stribede bokse */
    .editor-section{
        border:#C7C6C6 3px  dashed;
        border-radius: 5px;
        display:flex;
        justify-content:center;
        align-items:center;
        flex-direction:column;
        width:80%;
        z-index:0!important;
        position:relative;
        margin-top: 100px;
        
    }
    
    /* //her ligge ALLE inputfelterne den skal skjules og vise */
    .input-felter{
        background-color:black;
        display:flex;
        justify-content:space-around; 
        align-items:center;
     
        width:100%; 
        padding-top:20px;
        padding-bottom:20px;


        
    }
    .input-felter.show{
        display:flex
    }
  input[type="text"]{
        height:10px;
        border-radius:5px;
        border:none;
        height:30px;
        background-color:#FBFBFB;
        color: #4E4646;
        font-size:12px;;
        font-weight: 500;
        width:auto;
        overflow:visible;
        margin:0px;
        padding:0px;
        height:50px;
        

    }
    
  
    .label-name{
        background-color:#5271AC;
        color:white;
        font-size: 30px;
        font-weight:900;
        padding:0px;
        border-radius:5px;
        

    }
    .label{
       font-family: 'Jost', sans-serif;
       font-size: 16px;
       color: var(--blue);
    }
    .section-label{
        color:#C7C6C6;
        position:absolute;
        background-color:white;
        z-index:20!important;
        top:0;
        padding:15px;
        

    }
    .tilføj{
        background:none;
        color:white;
        border:none;
        
    }
    .add-block{
        background-color:pink;

    }
    .editor-section.selected {
    border-color: orange;
}
</style>
<script>

    const editSection = document.querySelectorAll('.editor-section');

    editSection.forEach(function(section) {
        section.addEventListener('click', function() {
            section.classList.toggle('selected');
        });
    });

</script>