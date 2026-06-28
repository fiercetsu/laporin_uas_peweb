(function () {
  "use strict";

  var form = document.getElementById("loginForm");
  if (!form) {
    return;
  }

  var password = document.getElementById("password");
  var toggle = document.getElementById("togglePassword");
  if (password && toggle) {
    toggle.addEventListener("click", function () {
      var show = password.type === "password";
      password.type = show ? "text" : "password";
      toggle.setAttribute(
        "aria-label",
        show ? "Sembunyikan password" : "Tampilkan password",
      );
      toggle.innerHTML = show
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';
    });
  }

  form.addEventListener("submit", function (event) {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }

    form.classList.add("was-validated");
  });
})();
