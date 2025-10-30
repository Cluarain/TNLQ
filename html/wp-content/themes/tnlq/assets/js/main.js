document.addEventListener("DOMContentLoaded", function () {
    {
        // меню для мобилы
        var header = document.getElementById("header");
        var mobile_burger = document.getElementById("mobile_burger");
        var d = document.body.classList;

        function toggleMenu() {
            if (header) {
                if (header.classList.contains("active")) {
                    // Закрываем меню
                    header.classList.remove("active");
                    d.remove("_lock");
                    document.removeEventListener("click", handleDocumentClick);
                } else {
                    // Открываем меню
                    header.classList.add("active");
                    d.add("_lock");
                    document.addEventListener("click", handleDocumentClick);
                }
            } else {
                console.log("[data-burgermenu] 404");
            }
        }

        function closeMenu() {
            header.classList.remove("active");
            d.remove("_lock");
            document.removeEventListener("click", handleDocumentClick);
        }

        function handleDocumentClick(event) {
            // Проверяем, что клик был вне меню и не на бургер
            var isClickInsideMenu = header.contains(event.target);
            var isClickOnBurger = event.target === mobile_burger || mobile_burger.contains(event.target);

            // Проверяем, является ли клик на ссылке внутри меню
            var isClickOnLink = event.target.tagName === "A" && header.contains(event.target);

            if (!isClickInsideMenu && !isClickOnBurger) {
                // Закрываем меню если клик вне и не на бургер
                closeMenu();
            }

            if (isClickOnLink) {
                // Закрываем меню при клике на ссылку
                closeMenu();
            }
        }

        if (mobile_burger) {
            mobile_burger.addEventListener("click", toggleMenu);
        }
    }

    // Находим любую ссылку с href="#..."
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href^="#"]');

        if (!link) return; // Если клик не по такой ссылке, выходим

        var targetId = link.getAttribute('href').substring(1); // Получаем ID без "#"

        if (!targetId) return; // Если пустой ID (например, href="#"), выходим

        e.preventDefault(); // Отменяем стандартное поведение

        var targetElement = document.getElementById(targetId); // Ищем элемент с таким ID

        if (targetElement) {
            // Элемент найден — плавно скроллим к нему
            targetElement.scrollIntoView({ behavior: 'smooth' });
        } else {
            // Элемент не найден — редиректим на главную с якорем
            window.location.href = '/' + '#' + targetId;
        }
    });
});
