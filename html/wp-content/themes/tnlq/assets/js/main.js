document.addEventListener("DOMContentLoaded", function () {
    var header = document.getElementById("header");
    var mobile_burger = document.getElementById("mobile_burger");
    mobile_burger.addEventListener("click", function (e) {
        header.classList.toggle("active");
    });
});
