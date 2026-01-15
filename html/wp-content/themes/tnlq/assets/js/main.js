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
        var dialog = document.getElementById('paymentDialog');
        var form = document.getElementById('paymentForm');
        var modalWrapper = document.getElementById('modal-wrapper');

        if (dialog && form) {
            // закрывать по крестику и по клику вне формы
            dialog.addEventListener('click', () => dialog.close());
            if (modalWrapper) {
                modalWrapper.addEventListener('click', (event) => event.stopPropagation());
            }

            // Обработчик клика на кнопки заказа
            document.querySelectorAll('.buy-now-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Заполняем скрытые поля
                    var productIdInput = document.getElementById('productId');
                    var tariffInput = document.getElementById('tariffPeriod');

                    if (productIdInput) {
                        productIdInput.value = this.dataset.productId || '';
                    }
                    if (tariffInput) {
                        tariffInput.value = this.dataset.period || '';
                    }

                    // Сбрасываем и показываем форму
                    form.reset();
                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.style.display = 'block';
                    }
                });
            });

            // Показываем индикатор загрузки при отправке формы
            form.addEventListener('submit', function () {
                var submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;
                }
            });
        }
    }


    // модалка на ответ платежа
    {
        var urlParams = new URLSearchParams(window.location.search);
        var paymentResult = urlParams.get('payment_result');
        var orderId = urlParams.get('order_id');
        var customerEmail = urlParams.get('customer_email');
        // var product_id = urlParams.get('product_id');
        // var promo = urlParams.get('promo');
        var message = urlParams.get('message');

        if (paymentResult) {
            switch (paymentResult) {
                case 'success':
                case 'completed':
                    if (window.gtag) {
                        gtag('event', 'conversion', {
                            'send_to': 'AW-XXXXXXXXX/XXXXXXXXX',
                            // 'value': 1.0, // Значение конверсии (если применимо)
                            // 'currency': 'USD', // Валюта (если применимо)
                            'transaction_id': orderId || ''
                        });
                    }

                    showPaymentModal({
                        type: 'success',
                        title: 'payment confirmed',
                        color: 'var(--success-color)',
                        message: `Your VPN config and instructions are now in your inbox <strong>${decodeURIComponent(customerEmail || '')}</strong>`,
                        orderId: orderId,
                    });
                    break;
                case 'cancelled':
                    showPaymentModal({
                        type: 'cancelled',
                        title: 'payment cancelled',
                        color: 'var(--danger-color)',
                        message: 'No charges were made. You can try again at any time.',
                    });
                    break;
                case 'processing':
                case 'pending':
                    showPaymentModal({
                        type: 'pending',
                        title: 'payment pending',
                        color: 'var(--warning-color)',
                        message: `Waiting for blockchain confirmations… We’ll notify you once the transaction is complete.`,
                    });
                    break;

                case 'error':
                    showPaymentModal({
                        type: 'error',
                        title: 'error',
                        color: 'var(--danger-color)',
                        message: `Error: ${message}`,
                    });
                    break;
            }

            // Убираем параметры из URL без перезагрузки
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // https://tuneliqa.com/?payment_status=success&order_id=277&key=wc_order_suYLtlZWqW3nr
        function showPaymentModal(options) {
            var { type, title, color, message, orderId } = options;
            var orderIdHtml = orderId ? `<p>Order: #${orderId}</p>` : '';

            var modalHtml = `
            <dialog id="${type}PaymentDialog">
                <div class="modal-wrapper">
                    <form method="dialog" class="modal-form">
                        <div class="modal-heading">
                            <h2 class="arrow-sign" style="color:${color};">${title}</h2>
                        </div>
                        <div class="modal-heading">  
                            <p>${message}</p>
                            ${orderIdHtml}    
                        </div>             
                        <button type="submit" class="btn arrow-sign hover-active-2" style="background:${color}; color:#fff;">i understood</button>
                    </form>
                </div>
            </dialog>
            `;

            // Удаляем предыдущее модальное окно, если оно существует
            var existingModal = document.getElementById(`${type}PaymentDialog`);
            if (existingModal) {
                existingModal.remove();
            }

            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.getElementById(`${type}PaymentDialog`).showModal();
        }
    }
});
