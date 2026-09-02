<? require_once '../include/database.php';
 require_once '../core/BlockRegistry.php'; ?>
<footer>
    <a class="cta-btn" href="editor.php?page_id=<?= (int)$page['id'] ?>">Rediger</a>
        <a class="cta-btn" href="preview.php?page_id=<?= (int)$page['id'] ?>">Preview</a>


</footer>
<style>
    footer{
        
        display: flex;
        justify-content: center;
       
        margin-top: 20px;
        height:250px;
        background-color:lightblue;
    }
button.cta-btn {
    background-color: #4CAF50; /* Grøn baggrund */
    color: white; /* Hvid tekst */
    padding: 10px 20px; /* Polstring */
    border: none; /* Ingen kant */
    border-radius: 5px; /* Runde hjørner */
    cursor: pointer; /* Pointer cursor ved hover */
    text-decoration: none; /* Fjern understregning fra linket */
}

</style>