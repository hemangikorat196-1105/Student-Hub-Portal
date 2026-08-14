function openMenu(){

    document.getElementById("sidebar").classList.add("active");

    document.getElementById("overlay").classList.add("active");

}

function closeMenu(){

    document.getElementById("sidebar").classList.remove("active");

    document.getElementById("overlay").classList.remove("active");

}

// Close sidebar after clicking any menu item

document.querySelectorAll(".sidebar a").forEach(link=>{

    link.addEventListener("click",closeMenu);

});