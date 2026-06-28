(function () {
    var printButton = document.querySelector('[data-pdf-print]');
    var backButton = document.querySelector('[data-pdf-back]');

    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }

    if (backButton) {
        backButton.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.href = printButton ? '../handlers/features/dashboard.php' : './';
        });
    }
})();
