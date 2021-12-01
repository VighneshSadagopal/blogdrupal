var nav = document.getElementById("nav");

window.onscroll = function() {
    if (window.pageYOffset > 100) {
        nav.style.background = "Black";
        nav.style.margin = "-17px 0px 20px 0px";
        nav.style.position = "0";
        nav.style.borderBottom = "2px solid white";
    } else {
        nav.style.background = "transparent"
        nav.style.margin = "0px 0px 20px 0px";
        nav.style.color = "#fff"
        nav.style.borderBottom = "none";
    }
}