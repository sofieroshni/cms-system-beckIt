<nav class="nav">
    <div >
        <h1 class="adminpanelh1">Adminpanel</h1> </div>
    <ul class="links">
        <a href="index.php" > <li class="active">Dine Sider</li></a>
        <a href="create-page.php"> <li>Opret side</li></a>
        <a href="Gallery.php"> <li>Galleri</li></a>
        <a href=""> <li>Settings</li></a>

    </ul>
</nav>
<style>
:root {
    --blue: #5271AC;
    --active: #F0AD72;
}
.nav{
    /* background-color:var(--blue); */
    width:100%;
    display:flex;
    flex-direction:column;
    margin:0;
    padding:0px; 
    /* background-color:purple; */
    box-sizing: border-box;
    position:fixed;
    top:0px;
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
z-index:0;
border-bottom-left-radius:6px; /**obs lige spørg khalid hvad designet er */
border-bottom-right-radius:6px;


}
.links{
    display:flex;
    justify-content:center;
    flex-direction:column;
/* background-color:pink; */
background-color:var(--blue);
padding:16px;
    width: 200px;
    height:181px;
}
.links li{
    /* background-color:brown; */
    background-color:var(--blue);

    display:flex;
    text-align:left;
    justify-content:start;
    align-items:start;
    /* padding-left: 18px; */
    width:80%;
    padding:8px;

}
.links li.active{
    background-color: var(--active);
    display:flex;
    text-align:left;
    justify-content:start;
    align-items:start;
    padding: 8px;
    border-radius:3px;
        /* padding-left: 18px; */


}
a{
    text-align:left;
    justify-content:start;
    align-items:left;
    text-decoration: none;
}

.adminpanelh1{
    font-family: 'Jost', sans-serif;
    font-weight: 900;
    font-size: 32px;
    color: white;
}

</style>
