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

    // Typewriter
    {
        // Функция для проверки загрузки Typewriter
        function waitForTypewriter(callback) {
            if (typeof Typewriter !== 'undefined') {
                callback();
            } else {
                setTimeout(function () {
                    waitForTypewriter(callback);
                }, 100);
            }
        }

        waitForTypewriter(function () {
            // Получаем все элементы с текстом
            var titleElements = document.querySelectorAll('.resist-mass__title');
            var texts = Array.from(titleElements).map(function (el) {
                return el.textContent.trim();
            });

            var currentIndex = 0;
            var typedNode = document.querySelector('.resist-mass__title.active');

            function startNextAnimation() {
                // Обновляем активный элемент
                titleElements.forEach(function (el) {
                    el.classList.remove('active');
                });
                typedNode = titleElements[currentIndex];
                typedNode.classList.add('active');

                // Очищаем текст для анимации
                typedNode.textContent = "";

                var typewriter = new Typewriter(typedNode, {
                    loop: false,
                    delay: 75,
                    deleteSpeed: 50
                });

                typewriter
                    .typeString(texts[currentIndex])
                    .pauseFor(2000)
                    .deleteAll()
                    .callFunction(function () {
                        // Переходим к следующему тексту
                        currentIndex = (currentIndex + 1) % texts.length;

                        // Запускаем следующую анимацию после небольшой паузы
                        setTimeout(startNextAnimation, 500);
                    })
                    .start();
            }

            if (titleElements.length > 0) {
                // Запускаем первую анимацию
                startNextAnimation();
            }

        });
    }

    // модальное окно
    {



        //     // Обработка отправки формы
        //     form.addEventListener('submit', function (e) {
        //         e.preventDefault();
        //         const formData = new FormData(this);

        //         fetch('/wp-admin/admin-ajax.php', {
        //             method: 'POST',
        //             body: formData
        //         })
        //             .then(response => response.json())
        //             .then(data => {
        //                 if (data.success) {
        //                     const originalLink = document.querySelector('a[data-period="' + tariffInput.value + '"]');
        //                     window.location.href = originalLink.href;
        //                 } else {
        //                     alert('Ошибка: ' + data.data);
        //                 }
        //             })
        //             .catch(error => {
        //                 console.error('Error:', error);
        //             });
        //     });


        var dialog = document.getElementById('paymentDialog');
        var form = document.getElementById('paymentForm');
        
        // закрывать по крестику и по клику вне формы
        dialog.addEventListener('click', () => dialog.close());
        document.getElementById('modal-wrapper').addEventListener('click', (event) => event.stopPropagation());

        // Обработчик клика на кнопки заказа
        document.querySelectorAll('.buy-now-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const productId = this.dataset.productId;
                const period = this.dataset.period;

                // Заполняем скрытые поля
                document.getElementById('productId').value = productId;
                document.getElementById('tariffPeriod').value = period;

                // Сбрасываем и показываем форму
                form.reset();
                dialog.showModal();
            });
        });

        // Показываем индикатор загрузки при отправке формы
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
        });
    }
});
