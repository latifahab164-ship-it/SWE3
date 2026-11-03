<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>مِراس - تسجيل الدخول</title>

  <link rel="stylesheet" href="Style.css">

  <style>

    /* ==========================================================
       MERAS AUTH PAGE
    ========================================================== */

    .auth-page {
      --au-teal-dark: #2E5560;
      --au-ink: var(--ink, #2E5560);
      --au-ink-dark: var(--ink-dark, #1F3F49);
      --au-teal: var(--teal, #7BB0BC);
      --au-teal-light: var(--teal-light, #8EB8BF);
      --au-gold: var(--gold, #EDD08A);
      --au-yellow: var(--yellow, #F9E5AE);
      --au-beige: var(--beige, #F8F0EA);
      --au-header-bg: var(--header-bg, #FFFCF8);

      --au-line: rgba(46, 85, 96, 0.18);
      --au-error: #A33A3A;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;

      font-family:
        "Thmanyah Sans",
        "Segoe UI",
        Tahoma,
        Arial,
        sans-serif;

      /* Off-white */
      background-color: #F8F0EA;

      color: var(--ink-dark, #1F3F49);
    }


    /* ==========================================================
       PAGE
    ========================================================== */

    .auth-page {
      width: 100%;
      max-width: 520px;

      margin: 0 auto;
      padding: 40px 20px 60px;
    }


    .auth-logo {
      display: block;

      width: 150px;
      max-width: 100%;
      height: auto;

      margin: 0 auto 20px;
    }


    .auth-page .page-title {
      margin: 0 0 6px;

      text-align: center;

      font-size: 30px;
      font-weight: 500;

      color: var(--au-ink-dark);
    }


    .auth-page .page-subtitle {
      margin: 0 0 26px;

      text-align: center;

      font-size: 15px;
      font-weight: 300;

      color: var(--au-teal-dark);
    }


    /* ==========================================================
       CARD
    ========================================================== */

    .auth-card {
      padding: 24px;

      background-color: #fff;

      border: 1px solid var(--au-line);
      border-top: 5px solid var(--au-teal-light);
      border-radius: 18px;

      box-shadow:
        0 6px 20px rgba(46, 85, 96, 0.10);
    }


    /* ==========================================================
       LOGIN / REGISTER TABS
    ========================================================== */

    .auth-tabs {
      display: flex;
      gap: 6px;

      margin-bottom: 22px;
      padding: 5px;

      background-color: var(--au-header-bg);

      border: 1px solid var(--au-line);
      border-radius: 999px;
    }


    .auth-tab {
      flex: 1;

      padding: 9px 12px;

      border: 0;
      border-radius: 999px;

      background-color: transparent;

      font-family: inherit;
      font-size: 15px;
      font-weight: 500;

      color: var(--au-teal-dark);

      cursor: pointer;

      transition:
        background-color .2s,
        color .2s;
    }


    .auth-tab:hover {
      background-color: var(--au-teal-light);
    }


    .auth-tab.active {
      background-color: var(--au-teal-dark);
      color: #fff;
    }


    /* ==========================================================
       PANELS
    ========================================================== */

    .auth-panel {
      display: none;
    }


    .auth-panel.active {
      display: block;

      animation: fadeIn .3s ease;
    }


    @keyframes fadeIn {

      from {
        opacity: 0;
        transform: translateY(6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }

    }


    .panel-title {
      margin: 0 0 6px;

      font-size: 21px;
      font-weight: 500;

      color: var(--au-ink-dark);
    }


    .panel-desc {
      margin: 0 0 18px;

      font-size: 14px;
      font-weight: 300;
      line-height: 1.9;

      color: var(--au-teal-dark);
    }


    /* ==========================================================
       USER TYPE
    ========================================================== */

    .user-type-title {
      display: block;

      margin-bottom: 8px;

      font-size: 14px;
      font-weight: 500;

      color: var(--au-teal-dark);
    }


    .user-type {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 7px;

      margin-bottom: 20px;
      padding: 5px;

      background-color: var(--au-header-bg);

      border: 1px solid var(--au-line);
      border-radius: 14px;
    }


    .user-type-btn {
      padding: 9px 10px;

      border: 0;
      border-radius: 10px;

      background-color: transparent;

      font-family: inherit;
      font-size: 14px;
      font-weight: 500;

      color: var(--au-teal-dark);

      cursor: pointer;

      transition:
        background-color .2s,
        color .2s;
    }


    .user-type-btn:hover {
      background-color: var(--au-teal-light);
    }


    .user-type-btn.active {
      background-color: var(--au-ink);
      color: #fff;
    }


    /* ==========================================================
       FIELDS
    ========================================================== */

    .auth-field {
      margin-bottom: 16px;
    }


    .auth-label {
      display: block;

      margin-bottom: 6px;

      font-size: 14px;
      font-weight: 500;

      color: var(--au-teal-dark);
    }


    .auth-input {
      display: block;

      width: 100%;

      padding: 11px 14px;

      background-color: var(--au-header-bg);

      border: 1px solid var(--au-line);
      border-right: 5px solid var(--au-teal-light);
      border-radius: 12px;

      font-family: inherit;
      font-size: 16px;
      font-weight: 400;

      color: var(--au-ink-dark);

      transition:
        border-color .2s,
        background-color .2s;
    }


    .auth-input:focus {
      outline: none;

      border-color: var(--au-teal-dark);
      background-color: #fff;
    }


    .auth-input.ltr {
      direction: ltr;
      text-align: right;
    }


    .auth-input.invalid {
      border-color: var(--au-error);
    }


    .field-error {
      display: none;

      margin: 6px 0 0;

      font-size: 13px;
      font-weight: 400;

      color: var(--au-error);
    }


    .field-error.show {
      display: block;
    }


    /* ==========================================================
       PASSWORD
    ========================================================== */

    .password-wrap {
      position: relative;
    }


    .password-wrap .auth-input {
      padding-left: 74px;
    }


    .toggle-password {
      position: absolute;

      top: 50%;
      left: 10px;

      transform: translateY(-50%);

      padding: 4px 10px;

      border: 0;
      border-radius: 999px;

      background-color: transparent;

      font-family: inherit;
      font-size: 13px;
      font-weight: 500;

      color: var(--au-teal-dark);

      cursor: pointer;
    }


    .toggle-password:hover {
      background-color: var(--au-teal-light);
    }


    /* ==========================================================
       BUTTON
    ========================================================== */

    .auth-btn {
      display: block;

      width: 100%;

      padding: 11px 22px;

      border: 2px solid var(--au-ink);
      border-radius: 999px;

      background-color: var(--au-ink);
      color: #fff;

      font-family: inherit;
      font-size: 16px;
      font-weight: 500;

      text-align: center;

      cursor: pointer;

      transition:
        background-color .2s,
        color .2s,
        border-color .2s;
    }


    .auth-btn:hover {
      background-color: var(--au-gold);
      border-color: var(--au-gold);

      color: var(--au-ink-dark);
    }


    /* ==========================================================
       LINKS
    ========================================================== */

    .link-btn {
      padding: 0;

      border: 0;
      background: none;

      font-family: inherit;
      font-size: 14px;
      font-weight: 500;

      color: var(--au-teal-dark);

      text-decoration: underline;

      cursor: pointer;
    }


    .link-btn:hover {
      color: var(--au-ink-dark);
    }


    .forgot-row {
      margin: -4px 0 18px;
    }


    .switch-row {
      margin: 18px 0 0;

      text-align: center;

      font-size: 14px;

      color: var(--au-teal-dark);
    }


    /* ==========================================================
       MESSAGES
    ========================================================== */

    .auth-message {
      display: none;

      margin: 0 0 18px;
      padding: 12px 16px;

      border-radius: 14px;

      font-size: 14px;
      font-weight: 500;
      line-height: 1.8;

      color: var(--au-ink-dark);
    }


    .auth-message.show {
      display: block;
    }


    .auth-message.success {
      background-color: var(--au-teal-light);
      border: 1px solid var(--au-teal);
    }


    .auth-message.error {
      background-color: var(--au-yellow);
      border: 1px solid var(--au-gold);
    }


    /* ==========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 600px) {

      .auth-page {
        padding: 26px 14px 44px;
      }

      .auth-page .page-title {
        font-size: 25px;
      }

      .auth-card {
        padding: 18px;
      }

    }

  </style>
</head>


<body>


<main class="auth-page">


  <!-- Logo -->

  <img
    src="logoMeras.png"
    alt="شعار مِراس"
    class="auth-logo"
  >


  <h1 class="page-title">
    مرحبًا بك في مِراس
  </h1>


  <p class="page-subtitle">
    منصة طلاب وأندية جامعة الملك سعود
  </p>


  <div class="auth-card">


    <!-- ======================================================
         MAIN TABS
    ======================================================= -->

    <div
      class="auth-tabs"
      id="auth-tabs"
      role="tablist"
    >

      <button
        type="button"
        class="auth-tab active"
        id="tab-login"
      >
        تسجيل الدخول
      </button>


      <button
        type="button"
        class="auth-tab"
        id="tab-register"
      >
        إنشاء حساب جديد
      </button>

    </div>


    <!-- ======================================================
         LOGIN
    ======================================================= -->

    <section
      class="auth-panel active"
      id="panel-login"
    >


      <h2 class="panel-title">
        تسجيل الدخول
      </h2>


      <p class="panel-desc">
        اختر نوع حسابك ثم أدخل بيانات تسجيل الدخول.
      </p>


      <p
        class="auth-message"
        id="login-message"
        role="alert"
      ></p>


      <!-- Account type -->

      <span class="user-type-title">
        نوع الحساب
      </span>


      <div class="user-type">

        <button
          type="button"
          class="user-type-btn active"
          data-user-type="student"
        >
          طالب
        </button>


        <button
          type="button"
          class="user-type-btn"
          data-user-type="club"
        >
          مسؤول نادي
        </button>

      </div>


      <form
        id="login-form"
        novalidate
      >


        <!-- University ID / Email -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="login-identifier"
            id="login-identifier-label"
          >
            الرقم الجامعي
          </label>


          <input
            class="auth-input ltr"
            type="text"
            id="login-identifier"
            inputmode="numeric"
            maxlength="10"
            autocomplete="username"
          >


          <p
            class="field-error"
            id="login-identifier-error"
          ></p>

        </div>


        <!-- Password -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="login-password"
          >
            كلمة المرور
          </label>


          <div class="password-wrap">

            <input
              class="auth-input ltr"
              type="password"
              id="login-password"
              autocomplete="current-password"
            >


            <button
              type="button"
              class="toggle-password"
              data-target="login-password"
            >
              إظهار
            </button>

          </div>


          <p
            class="field-error"
            id="login-password-error"
          ></p>

        </div>


        <!-- Forgot password -->

        <p class="forgot-row">

          <button
            type="button"
            class="link-btn"
            id="go-reset"
          >
            نسيت كلمة المرور؟
          </button>

        </p>


        <button
          type="submit"
          class="auth-btn"
        >
          تسجيل الدخول
        </button>

      </form>


      <p class="switch-row">

        طالب وليس لديك حساب؟

        <button
          type="button"
          class="link-btn"
          id="go-register"
        >
          إنشاء حساب جديد
        </button>

      </p>


    </section>


    <!-- ======================================================
         REGISTRATION
    ======================================================= -->

    <section
      class="auth-panel"
      id="panel-register"
    >


      <h2 class="panel-title">
        إنشاء حساب طالب جديد
      </h2>


      <p class="panel-desc">
        أدخل بياناتك لإنشاء حساب جديد في مِراس.
      </p>


      <p
        class="auth-message"
        id="register-message"
        role="alert"
      ></p>


      <form
        id="register-form"
        novalidate
      >


        <!-- Full name -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-name"
          >
            الاسم الكامل
          </label>


          <input
            class="auth-input"
            type="text"
            id="register-name"
            autocomplete="name"
          >


          <p
            class="field-error"
            id="register-name-error"
          ></p>

        </div>


        <!-- University ID -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-id"
          >
            الرقم الجامعي
          </label>


          <input
            class="auth-input ltr"
            type="text"
            id="register-id"
            inputmode="numeric"
            maxlength="10"
          >


          <p
            class="field-error"
            id="register-id-error"
          ></p>

        </div>


        <!-- Mobile -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-mobile"
          >
            رقم الجوال
          </label>


          <input
            class="auth-input ltr"
            type="tel"
            id="register-mobile"
            inputmode="numeric"
            maxlength="10"
            placeholder="05XXXXXXXX"
            autocomplete="tel"
          >


          <p
            class="field-error"
            id="register-mobile-error"
          ></p>

        </div>


        <!-- Major -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-major"
          >
            التخصص
          </label>


          <input
            class="auth-input"
            type="text"
            id="register-major"
          >


          <p
            class="field-error"
            id="register-major-error"
          ></p>

        </div>


        <!-- Email -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-email"
          >
            البريد الإلكتروني
          </label>


          <input
            class="auth-input ltr"
            type="email"
            id="register-email"
            autocomplete="email"
          >


          <p
            class="field-error"
            id="register-email-error"
          ></p>

        </div>


        <!-- Password -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-password"
          >
            كلمة المرور
          </label>


          <div class="password-wrap">

            <input
              class="auth-input ltr"
              type="password"
              id="register-password"
              autocomplete="new-password"
            >


            <button
              type="button"
              class="toggle-password"
              data-target="register-password"
            >
              إظهار
            </button>

          </div>


          <p
            class="field-error"
            id="register-password-error"
          ></p>

        </div>


        <!-- Confirm password -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="register-confirm"
          >
            تأكيد كلمة المرور
          </label>


          <div class="password-wrap">

            <input
              class="auth-input ltr"
              type="password"
              id="register-confirm"
              autocomplete="new-password"
            >


            <button
              type="button"
              class="toggle-password"
              data-target="register-confirm"
            >
              إظهار
            </button>

          </div>


          <p
            class="field-error"
            id="register-confirm-error"
          ></p>

        </div>


        <button
          type="submit"
          class="auth-btn"
        >
          إنشاء حساب
        </button>

      </form>


      <p class="switch-row">

        لديك حساب بالفعل؟

        <button
          type="button"
          class="link-btn"
          id="go-login-from-register"
        >
          تسجيل الدخول
        </button>

      </p>


    </section>


    <!-- ======================================================
         RESET PASSWORD
    ======================================================= -->

    <section
      class="auth-panel"
      id="panel-reset"
    >


      <h2 class="panel-title">
        إعادة تعيين كلمة المرور
      </h2>


      <p class="panel-desc">
        أدخل البريد الإلكتروني المسجل، ثم أدخل كلمة المرور الجديدة.
      </p>


      <p
        class="auth-message"
        id="reset-message"
        role="alert"
      ></p>


      <form
        id="reset-form"
        novalidate
      >


        <!-- Email -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="reset-email"
          >
            البريد الإلكتروني المسجل
          </label>


          <input
            class="auth-input ltr"
            type="email"
            id="reset-email"
            autocomplete="email"
          >


          <p
            class="field-error"
            id="reset-email-error"
          ></p>

        </div>


        <!-- New password -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="reset-password"
          >
            كلمة المرور الجديدة
          </label>


          <div class="password-wrap">

            <input
              class="auth-input ltr"
              type="password"
              id="reset-password"
              autocomplete="new-password"
            >


            <button
              type="button"
              class="toggle-password"
              data-target="reset-password"
            >
              إظهار
            </button>

          </div>


          <p
            class="field-error"
            id="reset-password-error"
          ></p>

        </div>


        <!-- Confirm new password -->

        <div class="auth-field">

          <label
            class="auth-label"
            for="reset-confirm"
          >
            تأكيد كلمة المرور الجديدة
          </label>


          <div class="password-wrap">

            <input
              class="auth-input ltr"
              type="password"
              id="reset-confirm"
              autocomplete="new-password"
            >


            <button
              type="button"
              class="toggle-password"
              data-target="reset-confirm"
            >
              إظهار
            </button>

          </div>


          <p
            class="field-error"
            id="reset-confirm-error"
          ></p>

        </div>


        <button
          type="submit"
          class="auth-btn"
        >
          تغيير كلمة المرور
        </button>

      </form>


      <p class="switch-row">

        <button
          type="button"
          class="link-btn"
          id="back-to-login"
        >
          العودة إلى تسجيل الدخول
        </button>

      </p>


    </section>


  </div>

</main>



<script>
  /* ==========================================================
     ELEMENTS
  ========================================================== */

  var authTabs = document.getElementById("auth-tabs");
  var tabLogin = document.getElementById("tab-login");
  var tabRegister = document.getElementById("tab-register");

  var panels = {
    login: document.getElementById("panel-login"),
    register: document.getElementById("panel-register"),
    reset: document.getElementById("panel-reset")
  };

  /* ==========================================================
     PANEL NAVIGATION
  ========================================================== */

  function showPanel(name) {

    Object.keys(panels).forEach(function (key) {
      panels[key].classList.remove("active");
    });

    panels[name].classList.add("active");

    authTabs.style.display =
      name === "reset" ? "none" : "flex";

    tabLogin.classList.toggle(
      "active",
      name === "login"
    );

    tabRegister.classList.toggle(
      "active",
      name === "register"
    );

    clearAllMessages();
  }

  function clearAllMessages() {

    document.querySelectorAll(".auth-message")
      .forEach(function (message) {
        message.classList.remove("show");
      });

    document.querySelectorAll(".field-error")
      .forEach(function (error) {
        error.classList.remove("show");
      });

    document.querySelectorAll(".auth-input")
      .forEach(function (input) {
        input.classList.remove("invalid");
      });
  }

  tabLogin.addEventListener("click", function () {
    showPanel("login");
  });

  tabRegister.addEventListener("click", function () {
    showPanel("register");
  });

  document.getElementById("go-register")
    .addEventListener("click", function () {
      showPanel("register");
    });

  document.getElementById("go-login-from-register")
    .addEventListener("click", function () {
      showPanel("login");
    });

  document.getElementById("go-reset")
    .addEventListener("click", function () {
      showPanel("reset");
    });

  document.getElementById("back-to-login")
    .addEventListener("click", function () {
      showPanel("login");
    });

  /* ==========================================================
     LOGIN ACCOUNT TYPE
  ========================================================== */

  var selectedUserType = "student";

  var userTypeButtons =
    document.querySelectorAll(".user-type-btn");

  var loginIdentifier =
    document.getElementById("login-identifier");

  var loginIdentifierLabel =
    document.getElementById("login-identifier-label");

  userTypeButtons.forEach(function (button) {

    button.addEventListener("click", function () {

      selectedUserType = button.dataset.userType;

      userTypeButtons.forEach(function (btn) {
        btn.classList.remove("active");
      });

      button.classList.add("active");

      loginIdentifier.value = "";

      document.getElementById("login-password").value = "";

      clearAllMessages();

      if (selectedUserType === "student") {

        loginIdentifierLabel.textContent =
          "الرقم الجامعي";

        loginIdentifier.type = "text";
        loginIdentifier.inputMode = "numeric";
        loginIdentifier.maxLength = 10;
        loginIdentifier.placeholder = "";

      } else {

        loginIdentifierLabel.textContent =
          "البريد الإلكتروني للنادي";

        loginIdentifier.type = "email";
        loginIdentifier.inputMode = "email";

        loginIdentifier.removeAttribute("maxlength");
        loginIdentifier.placeholder = "";
      }

    });
  });

  /* ==========================================================
     SHOW / HIDE PASSWORD
  ========================================================== */

  document.querySelectorAll(".toggle-password")
    .forEach(function (button) {

      button.addEventListener("click", function () {

        var input =
          document.getElementById(button.dataset.target);

        if (input.type === "password") {

          input.type = "text";
          button.textContent = "إخفاء";

        } else {

          input.type = "password";
          button.textContent = "إظهار";

        }

      });
    });

  /* ==========================================================
     HELPERS
  ========================================================== */

  function setError(inputId, message) {

    var input = document.getElementById(inputId);
    var error = document.getElementById(inputId + "-error");

    input.classList.add("invalid");
    error.textContent = message;
    error.classList.add("show");
  }

  function clearErrors(form) {

    form.querySelectorAll(".auth-input")
      .forEach(function (input) {
        input.classList.remove("invalid");
      });

    form.querySelectorAll(".field-error")
      .forEach(function (error) {
        error.classList.remove("show");
      });
  }

  function showMessage(id, type, message) {

    var box = document.getElementById(id);

    box.className = "auth-message show " + type;
    box.textContent = message;
  }

  function hideMessage(id) {

    document.getElementById(id)
      .classList.remove("show");
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function isValidUniversityId(id) {
    return /^\d{10}$/.test(id);
  }

  function isValidMobile(mobile) {
    return /^05\d{8}$/.test(mobile);
  }

  /* ==========================================================
     LOGIN - DATABASE
  ========================================================== */

  var loginForm =
    document.getElementById("login-form");

  loginForm.addEventListener("submit", function (event) {

    event.preventDefault();

    clearErrors(loginForm);
    hideMessage("login-message");

    var identifier = loginIdentifier.value.trim();

    var password =
      document.getElementById("login-password").value;

    var valid = true;

    /* Student validation */

    if (selectedUserType === "student") {

      if (identifier === "") {

        setError(
          "login-identifier",
          "يرجى إدخال الرقم الجامعي."
        );

        valid = false;

      } else if (!isValidUniversityId(identifier)) {

        setError(
          "login-identifier",
          "الرقم الجامعي يجب أن يتكون من 10 أرقام."
        );

        valid = false;
      }

    } else {

      /* Club validation */

      if (identifier === "") {

        setError(
          "login-identifier",
          "يرجى إدخال البريد الإلكتروني للنادي."
        );

        valid = false;

      } else if (!isValidEmail(identifier)) {

        setError(
          "login-identifier",
          "صيغة البريد الإلكتروني غير صحيحة."
        );

        valid = false;
      }
    }

    /* Password validation */

    if (password === "") {

      setError(
        "login-password",
        "يرجى إدخال كلمة المرور."
      );

      valid = false;

    } else if (password.length < 8) {

      setError(
        "login-password",
        "كلمة المرور يجب ألا تقل عن 8 أحرف."
      );

      valid = false;
    }

    if (!valid) {

      showMessage(
        "login-message",
        "error",
        "يرجى تصحيح بيانات تسجيل الدخول."
      );

      return;
    }

    /* Send login information to PHP */

    var submitButton =
      loginForm.querySelector('button[type="submit"]');

    submitButton.disabled = true;

    fetch("login_process.php", {

      method: "POST",

      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },

      body: new URLSearchParams({
        user_type: selectedUserType,
        identifier: identifier,
        password: password
      })

    })
    .then(function (response) {

      if (!response.ok) {
        throw new Error("Login request failed");
      }

      return response.json();

    })
    .then(function (data) {

      if (data.success) {

        window.location.href = data.redirect;

      } else {

        showMessage(
          "login-message",
          "error",
          data.message
        );

      }

    })
    .catch(function () {

      showMessage(
        "login-message",
        "error",
        "تعذر الاتصال بالخادم. حاولي مرة أخرى."
      );

    })
    .finally(function () {

      submitButton.disabled = false;

    });

  });

  /* ==========================================================
     REGISTRATION - VALIDATION
     Database connection will be added next
  ========================================================== */

  var registerForm =
    document.getElementById("register-form");

  registerForm.addEventListener("submit", function (event) {

    event.preventDefault();

    clearErrors(registerForm);
    hideMessage("register-message");

    var fullName =
      document.getElementById("register-name").value.trim();

    var universityId =
      document.getElementById("register-id").value.trim();

    var mobile =
      document.getElementById("register-mobile").value.trim();

    var major =
      document.getElementById("register-major").value.trim();

    var email =
      document.getElementById("register-email").value.trim();

    var password =
      document.getElementById("register-password").value;

    var confirmPassword =
      document.getElementById("register-confirm").value;

    var valid = true;

    if (fullName === "") {

      setError(
        "register-name",
        "يرجى إدخال الاسم الكامل."
      );

      valid = false;
    }

    if (universityId === "") {

      setError(
        "register-id",
        "يرجى إدخال الرقم الجامعي."
      );

      valid = false;

    } else if (!isValidUniversityId(universityId)) {

      setError(
        "register-id",
        "الرقم الجامعي يجب أن يتكون من 10 أرقام."
      );

      valid = false;
    }

    if (mobile === "") {

      setError(
        "register-mobile",
        "يرجى إدخال رقم الجوال."
      );

      valid = false;

    } else if (!isValidMobile(mobile)) {

      setError(
        "register-mobile",
        "رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام."
      );

      valid = false;
    }

    if (major === "") {

      setError(
        "register-major",
        "يرجى إدخال التخصص."
      );

      valid = false;
    }

    if (email === "") {

      setError(
        "register-email",
        "يرجى إدخال البريد الإلكتروني."
      );

      valid = false;

    } else if (!isValidEmail(email)) {

      setError(
        "register-email",
        "صيغة البريد الإلكتروني غير صحيحة."
      );

      valid = false;
    }

    if (password === "") {

      setError(
        "register-password",
        "يرجى إدخال كلمة المرور."
      );

      valid = false;

    } else if (password.length < 8) {

      setError(
        "register-password",
        "كلمة المرور يجب ألا تقل عن 8 أحرف."
      );

      valid = false;
    }

    if (confirmPassword === "") {

      setError(
        "register-confirm",
        "يرجى تأكيد كلمة المرور."
      );

      valid = false;

    } else if (password !== confirmPassword) {

      setError(
        "register-confirm",
        "كلمة المرور وتأكيدها غير متطابقتين."
      );

      valid = false;
    }

    if (!valid) {

      showMessage(
        "register-message",
        "error",
        "يرجى تصحيح البيانات الموضحة أدناه."
      );

      return;
    }

    
    /* Send registration data to PHP */

    var submitButton =
      registerForm.querySelector('button[type="submit"]');

    submitButton.disabled = true;

    fetch("register_process.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams({
        full_name: fullName,
        university_id: universityId,
        phone_number: mobile,
        major: major,
        email: email,
        password: password,
        confirm_password: confirmPassword
      })
    })
    .then(function(response) {
      if (!response.ok) {
        throw new Error("Registration request failed");
      }
      return response.json();
    })
    .then(function(data) {

      if (data.success) {

        showMessage(
          "register-message",
          "success",
          data.message
        );

        registerForm.reset();

        setTimeout(function() {
          showPanel("login");
        }, 1500);

      } else {

        showMessage(
          "register-message",
          "error",
          data.message
        );

      }

    })
    .catch(function() {

      showMessage(
        "register-message",
        "error",
        "تعذر الاتصال بالخادم. حاولي مرة أخرى."
      );

    })
    .finally(function() {
      submitButton.disabled = false;
    });


  });

  /* ==========================================================
     RESET PASSWORD - TEMPORARILY DISABLED
  ========================================================== */

  var resetForm =
    document.getElementById("reset-form");

  resetForm.addEventListener("submit", function (event) {

    event.preventDefault();

    clearErrors(resetForm);

    showMessage(
      "reset-message",
      "error",
      "إعادة تعيين كلمة المرور غير متاحة حاليًا."
    );

  });

</script>



</body>
</html>
