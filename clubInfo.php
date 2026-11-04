
<?php
session_start();
require_once "MerasDB.php";

if (!isset($_SESSION["role"], $_SESSION["club_id"]) ||
    $_SESSION["role"] !== "club") {
    header("Location: login.php");
    exit;
}

$club_id = (int) $_SESSION["club_id"];

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["club_info_csrf"])) {
    $_SESSION["club_info_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
$editing = false;

// حفظ التعديلات
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $editing = true;

    $description = trim($_POST["description"] ?? "");
    $objectives = trim($_POST["objectives"] ?? "");

    $lines = array_filter(
        array_map("trim", preg_split('/\r\n|\r|\n/', $objectives)),
        "strlen"
    );

    $objectives = implode("\n", $lines);

    if (!hash_equals(
        $_SESSION["club_info_csrf"],
        $_POST["csrf"] ?? ""
    )) {
        $error = "انتهت صلاحية الطلب. حدّثي الصفحة وحاولي مرة أخرى.";

    } elseif ($description === "") {
        $error = "لا يمكن ترك نبذة النادي فارغة.";

    } elseif ($objectives === "") {
        $error = "لا يمكن ترك أهداف النادي فارغة.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE club
             SET description = ?, objectives = ?
             WHERE club_id = ? AND is_active = 1"
        );

        $stmt->bind_param("ssi", $description, $objectives, $club_id);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $stmt->close();
            header("Location: clubInfo.php?saved=1");
            exit;
        }

        $error = "تعذر حفظ التغييرات.";
        $stmt->close();
    }
}

// جلب معلومات النادي المسجل
$stmt = $conn->prepare(
    "SELECT c.club_name, c.description, c.category,
        c.logo, c.cover_image, c.leaders_structure, c.objectives,
        ca.email
     FROM club c
     LEFT JOIN club_account ca ON ca.club_id = c.club_id
     WHERE c.club_id = ? AND c.is_active = 1"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();

$club = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$club) {
    exit("النادي غير موجود أو غير نشط.");
}

$description_value = $error ? $description : $club["description"];
$objectives_value = $error ? $objectives : ($club["objectives"] ?? "");

$objectives_lines = array_filter(
    array_map(
        "trim",
        preg_split('/\r\n|\r|\n/', $club["objectives"] ?? "")
    ),
    "strlen"
);

$logo = trim($club["logo"] ?? "");
$cover = trim($club["cover_image"] ?? "");
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>مِراس - مسؤول النادي</title>
  <link rel="stylesheet" href="Style.css">
<style>
  .club-info-page {
    --teal-dark: #2E5560;
    --gold-dark: #EDD08A;
    --line: rgba(46, 85, 96, 0.18);

    /* ألوان مأخوذة من صفحات النوادي الخاصة بالطلاب (الفيديو المرجعي) */
    --ref-bg: #F7ECE6;
    --ref-card-top: #79A4AC;
    --ref-cover: #F4F4F4;
    --ref-btn: #274A54;
    --ref-tab-yellow: #F7DFA0;

    width: 100%;
    max-width: 860px;
    margin: 0 auto;
    padding: 34px 24px 60px;
  }

  /* ---------- عنوان الصفحة ---------- */
  .club-info-page .page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 28px;
    font-size: 30px;
    color: var(--ink-dark);
  }

  .club-info-page .page-title img {
    height: 34px;
  }

  /* ---------- الأقسام ---------- */
  .club-info-page .section {
    margin-bottom: 34px;
  }

  /* عنوان القسم بنفس شكل تبويبات صفحة النادي: خط رفيع مع خط أغمق تحت العنوان */
  .club-info-page .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
    padding: 0 4px 10px;
    background-color: transparent;
    border-bottom: 1px solid var(--line);
    border-radius: 0;
  }

  .club-info-page .section-title {
    display: inline-block;
    margin: 0;
    padding: 6px 22px 10px;
    margin-bottom: -11px;
    font-size: 19px;
    color: var(--ink-dark);
    border-bottom: 3px solid var(--ref-btn);
  }

  /* ---------- البطاقة ---------- */
  .club-info-page .card {
    background-color: #fff;
    border-radius: 22px;
    border: 1px solid var(--line);
    border-top: 5px solid var(--ref-card-top);
    box-shadow: 0 6px 22px rgba(46, 85, 96, 0.10);
    padding: 22px;
  }

  .club-info-page .label {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 500;
    color: var(--teal-dark);
  }

  .club-info-page .value {
    margin: 0;
    font-size: 17px;
    line-height: 1.8;
    color: var(--ink-dark);
  }

  /* ---------- الشعار والاسم (بطاقة تعريف النادي) ---------- */
  .club-info-page .club-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 0;
    padding-top: 130px;       /* مساحة لشريط الغلاف */
    overflow: hidden;
    border-top: 5px solid var(--ref-card-top);
  }

  /* شريط الغلاف الزخرفي في أعلى البطاقة */

.club-info-page .club-card::before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 130px;
    background-color: var(--ref-cover);
    background-image: var(--club-cover);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}


  .club-info-page .club-card > div:last-child {
    padding: 0 0 22px;
  }

  .club-info-page .club-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 84px;
    height: 84px;
    flex-shrink: 0;
    margin: -42px 24px 22px 0;   /* يتداخل مع شريط الغلاف */
    border-radius: 50%;
    border: 3px solid var(--teal-dark);
    background-color: #fff;
    overflow: hidden;
    font-size: 34px;
    font-weight: 500;
    color: var(--teal-dark);
  }

  .club-info-page .club-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .club-info-page .club-name {
    margin: 0;
    font-size: 26px;
    color: var(--ink-dark);
  }

  /* ---------- النبذة ---------- */
  .club-info-page .about-text {
    margin: 0;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid var(--line);
    border-right: 5px solid var(--ref-card-top);
    background-color: var(--header-bg);
    font-size: 16px;
    line-height: 2;
    color: var(--ink-dark);
  }

  /* ---------- بيانات النادي (شبكة) ---------- */
  .club-info-page .info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
  }

  .club-info-page .info-item .value {
    word-break: break-word;
  }

  /* البريد الإلكتروني يبقى من اليسار لليمين داخل الصفحة العربية */
  .club-info-page .email {
    direction: ltr;
    text-align: right;
  }

  /* ---------- الأهداف ---------- */
  .club-info-page .objectives-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .club-info-page .objectives-list li {
    background-color: #fff;
    border-radius: 14px;
    border: 1px solid var(--line);
    border-right: 8px solid var(--ref-card-top);
    box-shadow: 0 4px 14px rgba(46, 85, 96, 0.08);
    padding: 14px 18px;
    font-size: 16px;
    color: var(--ink-dark);
  }

  /* ---------- الأزرار (نفس أزرار صفحات النوادي: داكنة بزوايا مدوّرة) ---------- */
  .club-info-page .btn {
    display: inline-block;
    padding: 10px 26px;
    border-radius: 12px;
    border: 2px solid var(--ref-btn);
    background-color: var(--ref-btn);
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color .2s, color .2s, border-color .2s;
  }

  .club-info-page .btn:hover {
    border-color: var(--ref-tab-yellow);
    background-color: var(--ref-tab-yellow);
    color: var(--ink-dark);
  }

  .club-info-page .btn-light {
    background-color: transparent;
    color: var(--ref-btn);
    border-color: var(--ref-btn);
  }

  .club-info-page .btn-light:hover {
    background-color: var(--ref-tab-yellow);
    border-color: var(--ref-tab-yellow);
    color: var(--ink-dark);
  }

  /* ---------- وضع التعديل ---------- */
  /* في الوضع العادي تظهر النصوص فقط، وفي وضع التعديل تظهر مربعات الكتابة */
  .club-info-page .edit-area,
  .club-info-page .edit-actions {
    display: none;
  }

  .club-info-page.is-editing .edit-area {
    display: block;
  }

  .club-info-page.is-editing .edit-actions {
    display: flex;
  }

  .club-info-page.is-editing .view-area,
  .club-info-page.is-editing #edit-btn {
    display: none;
  }

  .club-info-page .edit-textarea {
    display: block;
    width: 100%;
    box-sizing: border-box;
    min-height: 120px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--line);
    border-right: 5px solid var(--ref-card-top);
    background-color: var(--header-bg);
    font-family: inherit;
    font-size: 16px;
    line-height: 2;
    color: var(--ink-dark);
    resize: vertical;
  }

  .club-info-page .edit-textarea:focus {
    outline: none;
    border-color: var(--teal-dark);
    border-right-color: var(--teal-dark);
  }

  .club-info-page .edit-hint {
    margin: 0 0 8px;
    font-size: 13px;
    color: var(--teal-dark);
  }

  .club-info-page .edit-actions {
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 40px;
  }

  /* ---------- الرسائل ---------- */
  .club-info-page .message {
    display: none;
    margin: 0 0 24px;
    padding: 12px 18px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .club-info-page .message.show {
    display: block;
  }

  .club-info-page .message-success {
    background-color: var(--teal-light);
    border: 1px solid var(--teal);
  }

  .club-info-page .message-error {
    background-color: var(--yellow);
    border: 1px solid var(--gold-dark);
  }

  /* ---------- الشاشات الصغيرة ---------- */
  @media (max-width: 900px) {
    .club-info-page .info-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 800px) {
    .club-info-page {
      padding: 24px 16px 44px;
    }

    .club-info-page .page-title {
      font-size: 24px;
    }

    .club-info-page .section-head {
      padding-right: 0;
      padding-left: 0;
    }

    .club-info-page .info-grid {
      grid-template-columns: 1fr;
    }

    .club-info-page .club-card {
      flex-direction: column;
      text-align: center;
      gap: 0;
    }

    .club-info-page .club-logo {
      margin: -42px 0 12px;
    }

    .club-info-page .edit-actions .btn {
      flex: 1;
      text-align: center;
    }
  }
</style>
 </head>
<body data-role="club" data-page="club-info">


<header class="header" id="site-header"></header>


<main class="page club-info-page<?= $editing ? " is-editing" : "" ?>" id="club-info-page">

 
<h1 class="page-title">
    <img src="image/sunMeras.png" alt="">
    معلومات النادي
</h1>

<!-- رسائل النجاح والخطأ -->
<p class="message message-success<?= isset($_GET["saved"]) && $_GET["saved"] === "1" ? " show" : "" ?>"
   id="success-message" role="status">
    تم حفظ التغييرات بنجاح.
</p>

<p class="message message-error<?= $error !== "" ? " show" : "" ?>"
   id="error-message" role="alert">
    <?= e($error) ?>
</p>

<form id="club-edit-form" method="post" action="clubInfo.php">
    <input type="hidden" name="csrf"
           value="<?= e($_SESSION["club_info_csrf"]) ?>">
</form>

<!-- هوية النادي -->
<section class="section" id="club-identity">
    <div class="section-head">
        <h2 class="section-title">هوية النادي</h2>
    </div>

    <div class="card club-card"
     style="--club-cover: url('<?= $cover !== '' ? 'image/' . e(rawurlencode(basename($cover))) : '' ?>');">
        <div class="club-logo">
            <?php if ($logo !== ""): ?>
                <img src="image/<?= e(basename($logo)) ?>" alt="شعار النادي">
            <?php else: ?>
                <?= e(mb_substr($club["club_name"], 0, 1)) ?>
            <?php endif; ?>
        </div>

        <div>
            <p class="label">اسم النادي</p>
            <h3 class="club-name" id="club-name">
                <?= e($club["club_name"]) ?>
            </h3>
        </div>
    </div>
</section>

<!-- نبذة النادي -->
<section class="section" id="club-about">
    <div class="section-head">
        <h2 class="section-title">نبذة عن النادي</h2>
        <button type="button" class="btn btn-light" id="edit-btn">
            تعديل المعلومات
        </button>
    </div>

    <div class="card">
        <p class="about-text view-area" id="club-description">
            <?= nl2br(e($club["description"])) ?>
        </p>

        <div class="edit-area">
            <label class="label" for="description-input">
                نبذة عن النادي
            </label>

            <textarea
                class="edit-textarea"
                id="description-input"
                name="description"
                form="club-edit-form"
                rows="4"><?= e($description_value) ?></textarea>
        </div>
    </div>
</section>

<!-- بيانات النادي -->
<section class="section" id="club-details">
    <div class="section-head">
        <h2 class="section-title">بيانات النادي</h2>
    </div>

    <div class="info-grid">

        <div class="card info-item">
            <p class="label">تصنيف النادي</p>
            <p class="value" id="club-college">
                <?= e($club["category"]) ?>
            </p>
        </div>

        <div class="card info-item">
            <p class="label">الهيكل القيادي</p>
            <p class="value" id="club-leader">
                <?= nl2br(e($club["leaders_structure"] ?: "غير محدد")) ?>
            </p>
        </div>

        <div class="card info-item">
            <p class="label">البريد الإلكتروني للنادي</p>
            <p class="value email" id="club-email">
                <?= e($club["email"] ?: "غير متوفر") ?>
            </p>
        </div>

    </div>
</section>

<!-- أهداف النادي -->
<section class="section" id="club-objectives">
    <div class="section-head">
        <h2 class="section-title">أهداف النادي</h2>
    </div>

    <div class="card">

        <ul class="objectives-list view-area" id="objectives-list">

            <?php if (empty($objectives_lines)): ?>

                <li>لم تُضف أهداف النادي بعد.</li>

            <?php else: ?>

                <?php foreach ($objectives_lines as $objective): ?>
                    <li><?= e($objective) ?></li>
                <?php endforeach; ?>

            <?php endif; ?>

        </ul>

        <div class="edit-area">
            <label class="label" for="objectives-input">
                أهداف النادي
            </label>

            <p class="edit-hint">
                اكتبي كل هدف في سطر منفصل.
            </p>

            <textarea
                class="edit-textarea"
                id="objectives-input"
                name="objectives"
                form="club-edit-form"
                rows="6"><?= e($objectives_value) ?></textarea>
        </div>

    </div>
</section>

<!-- أزرار الحفظ والإلغاء -->
<div class="edit-actions">

    <button type="submit"
            form="club-edit-form"
            class="btn"
            id="save-btn">
        حفظ التغييرات
    </button>

    <button type="button"
            class="btn btn-light"
            id="cancel-btn">
        إلغاء
    </button>

</div>

</main>

  
  <footer class="footer" id="site-footer"></footer>

  <script src="layout.js" defer></script>


<script>
    var page = document.getElementById("club-info-page");
    var editBtn = document.getElementById("edit-btn");
    var cancelBtn = document.getElementById("cancel-btn");

    var descriptionInput =
        document.getElementById("description-input");

    var objectivesInput =
        document.getElementById("objectives-input");

    var errorMessage =
        document.getElementById("error-message");

    var successMessage =
        document.getElementById("success-message");

    var initialDescription = descriptionInput.value;
    var initialObjectives = objectivesInput.value;

    // تفعيل وضع التعديل
    editBtn.addEventListener("click", function () {

        errorMessage.classList.remove("show");
        successMessage.classList.remove("show");

        page.classList.add("is-editing");
        descriptionInput.focus();

    });

    // إلغاء التعديل
    cancelBtn.addEventListener("click", function () {

        descriptionInput.value = initialDescription;
        objectivesInput.value = initialObjectives;

        errorMessage.classList.remove("show");
        page.classList.remove("is-editing");

    });
</script>


</body>
</html>
