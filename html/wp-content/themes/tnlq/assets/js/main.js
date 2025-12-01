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

    // модальное окно для ввода почты
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


    // модалка на ответ платежа
    {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentResult = urlParams.get('payment_result');
        const orderId = urlParams.get('order_id');
        const customerEmail = urlParams.get('customer_email');

        if (paymentResult) {
            switch (paymentResult) {
                case 'success':
                case 'completed':
                    showPaymentModal({
                        type: 'success',
                        title: 'Payment Successful!',
                        color: 'var(--success-color)',
                        message: `Your VPN configuration has been sent to <strong>${decodeURIComponent(customerEmail || '')}</strong>`,
                        orderId: orderId,
                        showOrderId: true
                    });
                    break;
                case 'cancelled':
                    showPaymentModal({
                        type: 'cancelled',
                        title: 'Payment Cancelled',
                        color: 'var(--danger-color)',
                        message: 'The payment was cancelled. You can try again anytime.',
                        showOrderId: false
                    });
                    break;
                case 'processing':
                case 'pending':
                    showPaymentModal({
                        type: 'pending',
                        title: 'Payment Processing',
                        color: 'var(--warning-color)',
                        message: `Your payment is being processed. We\'ll notify you to <strong>${decodeURIComponent(customerEmail || '')}</strong>`,
                        showOrderId: false
                    });
                    break;
            }

            // Убираем параметры из URL без перезагрузки
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }

        // https://tuneliqa.com//?payment_status=success&order_id=277&key=wc_order_suYLtlZWqW3nr
        function showPaymentModal(options) {
            const { type, title, color, message, orderId, showOrderId } = options;
            const orderIdHtml = showOrderId ? `<p>Order: #${orderId}</p>` : '';

            const modalHtml = `
            <dialog id="${type}PaymentDialog">
                <div class="modal-wrapper">
                    <form method="dialog" class="modal-form">
                        <div class="modal-heading" >
                            <h2 class="arrow-sign" style="color:${color};">${title}</h2>
                        </div>
                        <div class="modal-heading" >  
                            <p>${message}</p>
                            ${orderIdHtml}    
                        </div>             
                        <button type="submit" class="btn arrow-sign hover-active-2" style="background:${color}; color:#fff;">i understood</button>
                    </form>
                </div>
            </dialog>
            `;

            // Удаляем предыдущее модальное окно, если оно существует
            const existingModal = document.getElementById(`${type}PaymentDialog`);
            if (existingModal) {
                existingModal.remove();
            }

            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.getElementById(`${type}PaymentDialog`).showModal();
        }
    }
});
