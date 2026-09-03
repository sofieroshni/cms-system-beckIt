<? require_once '../include/database.php';
 require_once '../core/BlockRegistry.php'; 
  include 'style.css';
 ?>

<footer>
   <div>
    <a class="btn" href="editor.php?page_id=<?= (int)$page['id'] ?>">
        <button class="btn">Preview</button>
    </a>
    <button class="btn">Publish</button>
    <button class="btn">Gem</button>
    <button class="btn">Tilføj blok</button>
</div>
    
    <a class="btn " href="editor.php?page_id=<?= (int)$page['id'] ?>">
         <button class="btn">

    </button
    ></a>
        <a class=" btn cta" href="preview.php?page_id=<?= (int)$page['id'] ?>">  
            <button class="btn"></button></a>
        <a>Publiser</a>


</footer>
<style>
    footer{
        display: flex;
        justify-content: center;
        margin-top: 20px;
        height:100px;
        background-color:lightblue;
        bottom:0;
       position:fixed;
       width:100%;
       background-color:red;
    }
    

</style>