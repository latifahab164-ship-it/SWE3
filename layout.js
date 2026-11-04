
/* Shared header and footer.
   Edit them here once and every page changes.

   Student:
   <body data-role="student" data-page="home">

   Club:
   <body data-role="club" data-page="home">
*/

(function () {

  var role = document.body.dataset.role || "student";
  var page = document.body.dataset.page || "";

  /* ==========================================================
     روابط الهيدر
     ========================================================== */

  var NAV = {

    /* صفحات الطالب */
    student: [
      ["home", "الرئيســية", "home.php"],
      ["opportunities", "الفــرص", "opportunities.php"],
      ["clubs", "الأنــدية", "clubs.php"],
      ["requests", "طلبــاتي", "requests.php"],
      ["profile", "الملــف الشخصــي", "profile.php"]
    ],

    /* صفحات مسؤول النادي */
    club: [
      ["home", "الرئيســية", "clubAdmin.php"],
      ["manage-opportunities", "إدارة الفــرص", "opportunitiesClub.php"],
      ["manage-requests", "إدارة الطلبــات", "applications.php"],
      ["posts", "المنشــورات", "post.php"],
      ["achievements", "توثيــق الإنجــازات", "achievements.php"],
      ["club-info", "معلومــات النــادي", "clubInfo.php"]
    ]

  };

  /* ==========================================================
     الصفحة الرئيسية حسب نوع المستخدم
     ========================================================== */

  var homeHref =
    role === "club"
      ? "clubAdmin.php"
      : "home.php";

  /* ==========================================================
     نص الفوتر
     ========================================================== */

  var tagline =
    role === "club"
      ? "منصة تجمع طلاب جامعة الملك سعود بالأندية والفرص الجامعية."
      : "وجهَتــك إلى الأنديــة الطلابيَّــة بجامعــة الملــك سعــود.";

  /* ==========================================================
     إنشاء روابط الهيدر
     ========================================================== */

  var links = NAV[role].map(function (l) {

    return (
      '<a href="' +
      l[2] +
      '"' +
      (l[0] === page ? ' class="active"' : "") +
      ">" +
      l[1] +
      "</a>"
    );

  }).join("");

  /* ==========================================================
     HEADER
     ========================================================== */

  var header = document.getElementById("site-header");

  if (header) {

    header.innerHTML =

      /* الشعار */
      '<a href="' + homeHref + '" class="logo">' +
        '<img src="image/logoMeras.png" alt="شعار مِراس">' +
      '</a>' +

      /* زر القائمة للجوال */
      '<button type="button" ' +
        'class="menu-btn" ' +
        'aria-label="القائمة" ' +
        'aria-expanded="false" ' +
        'aria-controls="nav">' +

        '<span></span>' +
        '<span></span>' +
        '<span></span>' +

      '</button>' +

      /* روابط التنقل */
      '<nav class="nav" id="nav">' +

        links +

        /* تسجيل الخروج */
        '<a href="logout.php" class="logout">' +
          'تسجيــل الخــروج' +
        '</a>' +

      '</nav>';

    /* ========================================================
       قائمة الجوال
       ======================================================== */

    var btn = header.querySelector(".menu-btn");
    var nav = header.querySelector(".nav");

    btn.addEventListener("click", function () {

      var open = nav.classList.toggle("open");

      btn.setAttribute(
        "aria-expanded",
        open
      );

    });

  }

  /* ==========================================================
     FOOTER
     ========================================================== */

  var footer = document.getElementById("site-footer");

  if (footer) {

    footer.innerHTML =

      '<div class="footer-top">' +

        '<div class="footer-brand">' +

          '<div class="footer-title">' +

            '<img ' +
              'src="image/sunMeras.png"  ' +
              'alt="شمس مِراس" ' +
              'class="footer-sun">' +

            '<h2>مِـــراس</h2>' +

          '</div>' +

          '<p>' +
            tagline +
          '</p>' +

        '</div>' +

        '<nav class="footer-links" aria-label="روابط سريعة">' +

          '<a href="' + homeHref + '">' +
            'الرئيســية' +
          '</a>' +

          '<a href="' +
            (role === "club"
              ? "opportunitiesClub.php"
              : "opportunities.php") +
          '">' +
            'الفــرص' +
          '</a>' +

          '<a href="' +
            (role === "club"
              ? "clubInfo.php"
              : "clubs.php") +
          '">' +
            (role === "club"
              ? 'معلومــات النــادي'
              : 'الأنــدية') +
          '</a>' +

          '<a href="about.php">' +
            'عــن مِــراس' +
          '</a>' +

        '</nav>' +

      '</div>' +

      '<p class="copyright">' +
        '© 2026 مِراس. جميع الحقوق محفوظة.' +
      '</p>';

  }

})();
