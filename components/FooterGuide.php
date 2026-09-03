
<footer>

    <i class="fa-solid fa-circle-info"
       onclick="hej()">
    </i>

    <div class="info"></div>

</footer>


<script>

function hej() {

    const info = document.querySelector('.info');

    info.classList.toggle('show');

    info.innerHTML = `
        <p>HEJSA</p>
    `;
}

</script>

<style>
    footer{
        position:absolute;
        bottom:0;
        background-color: #f700ff;
        width:100%;
        height: 150px;
        display:flex;
        justify-content:end;
        align-items:center;
       
    }
.info {
    display: none;
    color:var(--blue);

}
.fa-circle-info{
        color:var(--blue);
        font-size: 30px;
         padding-left:50px;
        padding-right:50px;

}
/* 
.info.show {
    width: 200px;
    height: 100px;
    background-color: black;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
        color:var(--blue);
        position:absolute;
        bottom: 100px;;
        right:40px;
        border-radius: 63% 37% 42% 58% / 55% 45% 55% 45%;

}
.info.show::after{
    width:30px;
    height:30px;
    position:absolute;
    rotate: 45deg;

    border-radius: 60% 40% 55% 45%;
} */

 /* chat-gpt løsning */

 .info.show {
    width: 190px;
    height: 180px;
    background-color: white;

    /* Giver den organiske/bløde form */
    border-radius: 63% 37% 42% 58% / 55% 45% 55% 45%;

    /* Placering af teksten */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    text-align: center;
    gap: 3px;

     position: absolute;
    bottom: 110px;
    right: 40px;
    margin-bottom: 10px;
    animation:fadeIn 0.2s ease-in-out;

}

/* Den lille "hale" */
.info.show::after {
    content: "";


    width: 40px;
    height: 40px;

    background-color: white;

    /* Gør også halen lidt organisk */
    border-radius: 60% 40% 55% 45%;

    /* Placering */
    right: 0;

    /* Drej den lidt ligesom på Figma */
    transform: rotate(35deg);
    position: absolute;
    bottom: -20px;
    animation-duration:0ms;
    animation:fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 10%;
    transform: translateY(10px); }
    to { opacity: 100%; 
    transform: translateY(0); 
}
}
</style>
