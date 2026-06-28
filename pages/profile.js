(function () {
    var root = document.querySelector('[data-profile-tabs]');
    if (!root) return;

    var buttons = root.querySelectorAll('[data-tab-target]');
    var panels = document.querySelectorAll('[data-tab-panel]');

    function activate(name) {
        buttons.forEach(function (button) {
            var active = button.getAttribute('data-tab-target') === name;
            button.classList.toggle('border-[#00409c]', active);
            button.classList.toggle('text-[#00409c]', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('text-[#5d6673]', !active);
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== name);
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            activate(button.getAttribute('data-tab-target') || 'data');
        });
    });
})();
