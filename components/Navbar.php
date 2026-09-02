<?php
$templates = mysqli_query($connection, "SELECT * FROM page_templates ORDER BY id ASC");
?>
<nav class="nav">
    <div >
        <h1 class="adminpanelh1">Adminpanel</h1> </div>
    <ul class="links">
        <a href="index.php" class="link" > <li class="active">Dine Sider</li></a>
        <a href="#" onClick="toggleSlide()" class="link"> <li>Opret side</li></a>
        <a href="Gallery.php" class="link" ><li>Galleri</li></a>
        <a href="" class="link" ><li>Settings</li></a>

    </ul>
    <div class="slider" id="slider">
    <li>Skabeloner
            <ul>
                <?php while ($t = mysqli_fetch_assoc($templates)): ?>
                    <li><a href="create-page.php?template_id=<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></a></li>
                <?php endwhile; ?>
            </ul>
    </li>

    <li class="blank"><a href="create-page.php?template_id=0">Blank Side</a></li>
</div>
</nav>

<style>
:root {
    --blue: #5271AC;
    --active: #F0AD72;
}
.nav{
    width:100%;
    display:flex;
    flex-direction:column;
    margin:0;
    padding:0px;
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
border-bottom-left-radius:6px;
border-bottom-right-radius:6px;
}
.links{
    display:flex;
    justify-content:center;
    flex-direction:column;
background-color:var(--blue);
padding:16px;
    width: 200px;
    height:181px;
}
.links li{
    background-color:var(--blue);
    display:flex;
    text-align:left;
    justify-content:start;
    align-items:start;
    width:80%;
    padding:8px;
}
.links > a > li.active{
    background-color: var(--active);
    display:flex;
    text-align:left;
    justify-content:start;
    align-items:start;
    padding: 8px;
    border-radius:3px;
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
.nav > .slider{
 background-color:white;
 font-family: 'Jost', sans-serif;
list-style-type:none;
width:230px;
height: 330px;
position:absolute;
z-index:-1;
left:230px;
top:100px;
color:white;
padding:8px;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
transform: translateX(-100%);
transition: transform 0.3s ease;
opacity:0;
}

.nav > .slider.active{
    transform: translateX(0);
    opacity:1;
}
.nav > .slider >li{
    background-color: var(--blue);
    font-weight:700;
    width:200px;
    background-color:green;
     margin-top:8px;
     padding-top:8px;
    border-radius:3px;
}
.nav >.slider>li>ul>li{
    background-color:#354B73;
    font-weight:100;
    padding:8px;
    background-color:red;
}
.nav >.slider>li:last-child{
    font-weight:100;
    margin-top:25px;
}
</style>
<script>
function toggleSlide(){
    document.getElementById('slider').classList.toggle('active');
}
function changeColor(){
    document.querySelectorAll('links').forEach((el) => {
        el.classList.toggle('active');
    });
}
</script>