<nav class="nav">
    <div >
        <h1 class="adminpanelh1">Adminpanel</h1> </div>
    <ul class="links">
        <a href="admin/index.php"> <li>Dine Sider</li></a>
        <a href="admin/create-page.php"> <li>Opret side</li></a>
        <a href="blocks/Gallery.php"> <li>Galleri</li></a>
        <a href=""> <li>Settings</li></a>

    </ul>
</nav>
<style>
:root {
    --blue: #5271AC;
}
.nav{
    /* background-color:var(--blue); */
    width:100%;
    display:flex;
    flex-direction:column;
position: fixed;
}
.nav>div{
    width: 478px;
    height:124px;
    background-color:var(--blue);
    display:flex;
    flex-direction:column;
    justify-content:center;
    text-align:center;
box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 10px;


}
.links{
    display:flex;
    background-color:red;
    justify-content:center;
    flex-direction:column;
    align-items:center;

    width: 201px;
    height:181px;
}
.links li{
    background-color:blue;
    display:flex;
    text-align:left;
    justify-content:start;
    align-items:start;

}
a{
    text-align:left;
    justify-content:start;
    align-items:left;
}

.adminpanelh1{
    font-family: 'Jost', sans-serif;
    font-weight: 700;
    font-size: 32px;
    color: white;
}

</style>
