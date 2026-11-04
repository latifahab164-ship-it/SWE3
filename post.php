
<?php
session_start();
require_once "MerasDB.php";

if (
    !isset($_SESSION["role"], $_SESSION["club_id"]) ||
    $_SESSION["role"] !== "club"
) {
    header("Location: login.php");
    exit;
}

$club_id = (int) $_SESSION["club_id"];

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["posts_csrf"])) {
    $_SESSION["posts_csrf"] = bin2hex(random_bytes(32));
}

$types = [
    "general" => "إعلان عام",
    "event" => "فعالية",
    "workshop" => "ورشة عمل",
    "activity" => "نشاط طلابي",
    "other" => "أخرى"
];

$error = "";
$open_form = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $post_id = filter_var(
        $_POST["post_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $token = $_POST["csrf"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["posts_csrf"], $token)) {

        $error = "الطلب غير صالح. حدثي الصفحة وحاولي مرة أخرى.";

    } elseif (!in_array($action, ["add", "edit", "delete"], true)) {

        $error = "عملية غير صالحة.";

    } else {

        try {

            if ($action === "delete") {

                if (!$post_id) {
                    throw new Exception("رقم المنشور غير صالح.");
                }

                $stmt = $conn->prepare(
                    "SELECT media FROM post
                     WHERE post_id = ? AND club_id = ?"
                );
                $stmt->bind_param("ii", $post_id, $club_id);
                $stmt->execute();
                $old_post = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$old_post) {
                    throw new Exception("المنشور غير موجود.");
                }

                $stmt = $conn->prepare(
                    "DELETE FROM post
                     WHERE post_id = ? AND club_id = ?"
                );
                $stmt->bind_param("ii", $post_id, $club_id);
                $stmt->execute();
                $stmt->close();

                // لا نحذف الصورة من القرص هنا لتجنب حذف ملف مشترك.

            } else {

                $title = trim($_POST["title"] ?? "");
                $content = trim($_POST["content"] ?? "");
                $type = $_POST["type"] ?? "";

                if ($title === "" || $content === "") {
                    throw new Exception("العنوان والمحتوى مطلوبان.");
                }

                if (mb_strlen($title) > 150) {
                    throw new Exception("عنوان المنشور طويل جدًا.");
                }

                if (!isset($types[$type])) {
                    throw new Exception("نوع الإعلان غير صالح.");
                }

                $old_media = null;

                if ($action === "edit") {

                    if (!$post_id) {
                        throw new Exception("رقم المنشور غير صالح.");
                    }

                    $stmt = $conn->prepare(
                        "SELECT media FROM post
                         WHERE post_id = ? AND club_id = ?"
                    );
                    $stmt->bind_param("ii", $post_id, $club_id);
                    $stmt->execute();
                    $old_post = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$old_post) {
                        throw new Exception("المنشور غير موجود.");
                    }

                    $old_media = $old_post["media"];
                }

                $media = $old_media;
                $new_file = null;

                if (
                    isset($_FILES["image"]) &&
                    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
                ) {

                    $file = $_FILES["image"];

                    if ($file["error"] !== UPLOAD_ERR_OK) {
                        throw new Exception("حدث خطأ أثناء رفع الصورة.");
                    }

                    if ($file["size"] > 5 * 1024 * 1024) {
                        throw new Exception("حجم الصورة يجب ألا يتجاوز 5MB.");
                    }

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file["tmp_name"]);

                    $allowed = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    if (!isset($allowed[$mime]) ||
                        !getimagesize($file["tmp_name"])) {
                        throw new Exception("صيغة الصورة غير مدعومة.");
                    }

                    $folder = __DIR__ . "/uploads/posts";

                    if (!is_dir($folder) &&
                        !mkdir($folder, 0755, true)) {
                        throw new Exception("تعذر إنشاء مجلد الصور.");
                    }

                    $filename = bin2hex(random_bytes(16))
                        . "." . $allowed[$mime];

                    $new_file = $folder . "/" . $filename;

                    if (!move_uploaded_file(
                        $file["tmp_name"],
                        $new_file
                    )) {
                        throw new Exception("تعذر حفظ الصورة.");
                    }

                    $media = "uploads/posts/" . $filename;
                }

                try {

                    if ($action === "add") {

                        $stmt = $conn->prepare(
                            "INSERT INTO post
                             (club_id, title, content, type, media)
                             VALUES (?, ?, ?, ?, ?)"
                        );

                        $stmt->bind_param(
                            "issss",
                            $club_id,
                            $title,
                            $content,
                            $type,
                            $media
                        );

                    } else {

                        $stmt = $conn->prepare(
                            "UPDATE post
                             SET title = ?, content = ?,
                                 type = ?, media = ?
                             WHERE post_id = ? AND club_id = ?"
                        );

                        $stmt->bind_param(
                            "ssssii",
                            $title,
                            $content,
                            $type,
                            $media,
                            $post_id,
                            $club_id
                        );
                    }

                    $stmt->execute();
                    $stmt->close();

                } catch (Throwable $db_error) {

                    if ($new_file && is_file($new_file)) {
                        unlink($new_file);
                    }

                    throw $db_error;
                }
            }

            header("Location: post.php?success=" . $action);
            exit;

        } catch (Throwable $ex) {

            error_log($ex->getMessage());
            $error = "تعذر تنفيذ العملية. تأكدي من البيانات وحاولي مرة أخرى.";
            $open_form = ($action === "add");
        }
    }
}

/* جلب منشورات النادي الحالي */
$stmt = $conn->prepare(
    "SELECT post_id, title, content, type, media, created_at
     FROM post
     WHERE club_id = ?
     ORDER BY created_at DESC, post_id DESC"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?> 


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>مِراس - مسؤول النادي</title>
  <link rel="stylesheet" href="Style.css">

<style>

  .posts-page {
    --teal-dark: #2E5560;
    --gold-dark: #EDD08A;
    --line: rgba(46, 85, 96, 0.18);
 
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 34px 24px 60px;
  }
 

  .posts-page .page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 8px;
    font-size: 30px;
    color: var(--ink-dark);
  }
 
  .posts-page .page-title img {
    height: 34px;
  }
 
  .posts-page .page-subtitle {
    margin: 0 0 28px;
    font-size: 16px;
    font-weight: 400;
  }
 

  .posts-page .btn {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 999px;
    border: 2px solid #2E5560;
    background-color: #2E5560;
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color .2s, color .2s, border-color .2s;
  }
 
.posts-page .btn:hover {
    background-color: var(--gold);
    border-color: var(--gold);
    color: var(--ink-dark);
}
 
.posts-page .btn-light {
    background-color: transparent;
    color: var(--ink);
    border-color: var(--teal);
}

.posts-page .btn-light:hover {
    background-color: var(--yellow);
    border-color: var(--gold);
    color: var(--ink-dark);
}
 

  .posts-page .btn-add {
    padding: 12px 28px;
    font-size: 16px;
  }
 

  .posts-page .section {
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    overflow: hidden;
  }
 
  .posts-page .add-section {
    margin-top: 28px;
  }
 
  .posts-page .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 22px;
    background-color: transparent;
    border-bottom: none;
  }
 
  .posts-page .section-title {
    margin: 0;
    font-size: 21px;
    color: #2E5560;
  }
 
  .posts-page .section-body {
    padding: 20px 22px 22px;
    background-color: #fff;
  }
 

  .posts-page .posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
  }
 

  .posts-page .posts-grid:not(.show-all) .post-extra {
    display: none;
  }
 
  .posts-page .post-card {
    display: flex;
    flex-direction: column;
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    border-top: 5px solid var(--teal-light);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    padding: 18px 20px 20px;
  }
 
  .posts-page .post-top {
    margin-bottom: 12px;
  }
 
  .posts-page .badge {
    display: inline-block;
    padding: 3px 14px;
    border-radius: 999px;
    background-color: var(--teal-light);
    border: 1px solid var(--teal);
    font-size: 13px;
    font-weight: 500;
    color: var(--ink-dark);
  }
 
  .posts-page .post-title {
    margin: 0 0 8px;
    font-size: 20px;
    color: var(--ink-dark);
  }
 
  .posts-page .post-content {
    margin: 0 0 14px;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.9;
  }
 

  .posts-page .post-details {
    list-style: none;
    margin: auto 0 0;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid var(--line);
    border-right: 5px solid var(--teal-light);
    background-color: var(--header-bg);
    font-size: 14px;
    line-height: 2;
  }
 
  .posts-page .post-details b {
    margin-left: 4px;
    color: var(--ink-dark);
  }
 
  .posts-page .more-wrap {
    margin-top: 22px;
    text-align: center;
  }
 

  .posts-page .empty {
    grid-column: 1 / -1;
    margin: 0;
    text-align: center;
    padding: 40px 20px;
    border-radius: 14px;
    border: 2px dashed var(--teal-dark);
    background-color: #fff;
    font-size: 16px;
    color: var(--ink-dark);
  }
 

  .posts-page .add-text {
    margin: 0 0 16px;
    font-size: 15px;
    font-weight: 400;
  }
 

  .posts-page .form-wrap {
    display: grid;
    grid-template-rows: 0fr;
    visibility: hidden;
    transition: grid-template-rows .35s ease, visibility .35s;
  }
 
  .posts-page .form-wrap.open {
    grid-template-rows: 1fr;
    visibility: visible;
  }
 
  .posts-page .form-wrap-inner {
    min-height: 0;
    overflow: hidden;
  }
 
  .posts-page #post-form {
    margin-top: 22px;
    background-color: var(--header-bg);
    border: 1px solid var(--line);
    border-top: 5px solid var(--teal-light);
    border-radius: 18px;
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
  }
 
  .posts-page .form-title {
    margin: 0;
    padding: 14px 22px;
    background-color: transparent;
    border-bottom: none;
    border-radius: 13px 13px 0 0;
    font-size: 19px;
    color: #2E5560;
  }
 
  .posts-page .form-body {
    padding: 20px 22px 6px;
  }
 
  .posts-page .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
 
  .posts-page .field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
 

  .posts-page .field-full {
    grid-column: 1 / -1;
  }
 
  .posts-page .field label {
    font-size: 15px;
    font-weight: 500;
    color: var(--ink-dark);
  }
 
  .posts-page .optional {
    font-weight: 400;
    color: var(--teal-dark);
  }
 
  .posts-page .field input,
  .posts-page .field select,
  .posts-page .field textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 12px;
    border: 2px solid var(--teal-light);
    background-color: #fff;
    color: var(--ink);
    font-family: inherit;
    font-size: 15px;
  }
 
  .posts-page .field textarea {
    min-height: 130px;
    resize: vertical;
  }
 
  .posts-page .field input:focus,
  .posts-page .field select:focus,
  .posts-page .field textarea:focus {
    outline: none;
    border-color: var(--teal);
    box-shadow: 0 0 0 3px var(--yellow);
  }
 
  .posts-page .form-actions {
    display: flex;
    gap: 10px;
    padding: 18px 22px 22px;
  }
 
  /* ---------- الجوال ---------- */
  @media (max-width: 900px) {
    .posts-page .posts-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
 
  @media (max-width: 800px) {
    .posts-page {
      padding: 24px 16px 44px;
    }
 
    .posts-page .page-title {
      font-size: 24px;
    }
 
    .posts-page .section-head,
    .posts-page .section-body {
      padding-right: 16px;
      padding-left: 16px;
    }
 
    .posts-page .btn-add {
      width: 100%;
      text-align: center;
    }
 
    .posts-page .posts-grid,
    .posts-page .form-grid {
      grid-template-columns: 1fr;
    }
 
    .posts-page .form-actions .btn {
      flex: 1;
      text-align: center;
    }
  }
  
  .posts-page #previous-posts {
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: visible;
}

.posts-page #previous-posts .section-body {
    background: transparent;
}

.posts-page .post-editor[hidden] {
    display: none;
}
</style>

 </head>
<body data-role="club" data-page="posts">

  <!-- ===== Club Admin Header ===== -->
<header class="header" id="site-header"></header>

<main class="page posts-page">
 
  <!-- ===== عنوان الصفحة ===== -->
  <h1 class="page-title">
    <img src="image/sunMeras.png" alt="">
    إدارة المنشورات
  </h1>
  <p class="page-subtitle">عرض منشورات النادي السابقة وإضافة إعلانات جديدة.</p>
 
<?php
$success_messages = [
    "add" => "تم نشر المنشور بنجاح.",
    "edit" => "تم تعديل المنشور بنجاح.",
    "delete" => "تم حذف المنشور بنجاح."
];

$success = $_GET["success"] ?? "";
?>

<?php if (isset($success_messages[$success])): ?>
    <p role="status" style="color:#2E5560; margin-bottom:16px;">
        <?= e($success_messages[$success]) ?>
    </p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p role="alert" style="color:#9B2525; margin-bottom:16px;">
        <?= e($error) ?>
    </p>
<?php endif; ?>

  <!-- ===== المنشورات السابقة ===== -->
  <section class="section" id="previous-posts">
    <div class="section-head">
      <h2 class="section-title">المنشورات السابقة</h2>
    </div>
 
    <div class="section-body">
      <div class="posts-grid" id="posts-grid">

 
<?php if (empty($posts)): ?>

    <p class="empty">
        لم ينشر النادي أي منشورات حتى الآن.
    </p>

<?php else: ?>

    <?php foreach ($posts as $index => $post): ?>

        <article class="post-card <?= $index >= 3 ? 'post-extra' : '' ?>">

            <div class="post-top">
                <span class="badge">
                    <?= e($types[$post["type"]] ?? "أخرى") ?>
                </span>
            </div>

            <h3 class="post-title">
                <?= e($post["title"]) ?>
            </h3>

            <p class="post-content">
                <?= nl2br(e($post["content"])) ?>
            </p>

            <?php if (!empty($post["media"])): ?>
                <img
                    src="<?= e($post["media"]) ?>"
                    alt="صورة المنشور"
                    style="width:100%;border-radius:12px;margin-bottom:14px;"
                >
            <?php endif; ?>

            <ul class="post-details">
                <li>
                    <b>تاريخ النشر:</b>
                    <?= e(date("Y-m-d", strtotime($post["created_at"]))) ?>
                </li>
            </ul>

            <div class="post-actions"
                 style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">

                <button
                    type="button"
                    class="btn edit-post">
                    تعديل
                </button>

                <form method="post"
                      action="post.php"
                      onsubmit="return confirm('هل أنت متأكد من حذف هذا المنشور؟');">

                    <input type="hidden"
                           name="csrf"
                           value="<?= e($_SESSION["posts_csrf"]) ?>">

                    <input type="hidden" name="action" value="delete">

                    <input type="hidden"
                           name="post_id"
                           value="<?= (int)$post["post_id"] ?>">

                    <button type="submit" class="btn btn-light">
                        حذف
                    </button>
                </form>

            </div>

            <!-- نموذج تعديل المنشور -->
            <form method="post"
                  action="post.php"
                  enctype="multipart/form-data"
                  class="post-editor"
                  hidden
                  style="margin-top:16px;padding:16px;border-radius:12px;background:var(--header-bg);border:1px solid var(--line);">

                <input type="hidden" name="action" value="edit">

                <input type="hidden"
                       name="csrf"
                       value="<?= e($_SESSION["posts_csrf"]) ?>">

                <input type="hidden"
                       name="post_id"
                       value="<?= (int)$post["post_id"] ?>">

                <div class="field">
                    <label>عنوان المنشور</label>
                    <input type="text" name="title"
                           maxlength="150"
                           value="<?= e($post["title"]) ?>"
                           required>
                </div>

                <div class="field" style="margin-top:12px;">
                    <label>نوع الإعلان</label>
                    <select name="type" required>
                        <?php foreach ($types as $value => $label): ?>
                            <option
                                value="<?= e($value) ?>"
                                <?= $post["type"] === $value ? "selected" : "" ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field" style="margin-top:12px;">
                    <label>محتوى المنشور</label>
                    <textarea name="content" required><?= e($post["content"]) ?></textarea>
                </div>

                <div class="field" style="margin-top:12px;">
                    <label>تغيير الصورة (اختياري)</label>
                    <input type="file" name="image"
                           accept="image/jpeg,image/png,image/webp">
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
                    <button type="submit" class="btn">
                        حفظ التعديلات
                    </button>

                    <button type="button"
                            class="btn btn-light cancel-edit">
                        إلغاء
                    </button>
                </div>

            </form>

        </article>

    <?php endforeach; ?>

<?php endif; ?>

      </div>
 
      <div class="more-wrap">
        <button type="button" class="btn btn-light" id="toggle-posts">عرض المزيد</button>
      </div>
    </div>
  </section>
 

  <section class="section add-section" id="add-post">
    <div class="section-head">
      <h2 class="section-title">إضافة منشور جديد</h2>
    </div>
 
    <div class="section-body">
      <p class="add-text">يمكنك كتابة إعلان جديد باسم النادي ونشره للطلاب.</p>
 
      <button type="button" class="btn btn-add" id="open-form" aria-expanded="false" aria-controls="form-wrap">إضافة منشور</button>
 

      <div class="form-wrap" id="form-wrap">
        <div class="form-wrap-inner">
 
        
<form action="post.php" method="post"
      enctype="multipart/form-data" id="post-form">

    <!-- تحديد العملية: إضافة منشور -->
    <input type="hidden" name="action" value="add">

    <!-- رمز الحماية -->
    <input type="hidden"
           name="csrf"
           value="<?= e($_SESSION["posts_csrf"]) ?>">

    <h3 class="form-title">منشور جديد</h3>

    <div class="form-body">
        <div class="form-grid">

            <div class="field">
                <label for="post-title">عنوان المنشور</label>
                <input type="text"
                       id="post-title"
                       name="title"
                       maxlength="150"
                       required>
            </div>

            <div class="field">
                <label for="post-type">نوع الإعلان</label>
                <select id="post-type" name="type" required>
                    <option value="general">إعلان عام</option>
                    <option value="event">فعالية</option>
                    <option value="workshop">ورشة عمل</option>
                    <option value="activity">نشاط طلابي</option>
                    <option value="other">أخرى</option>
                </select>
            </div>

 
                <div class="field field-full">
                  <label for="post-content">محتوى المنشور</label>
                  <textarea id="post-content" name="content" required></textarea>
                </div>
 
                <div class="field field-full">
                  <label for="post-image">صورة المنشور <span class="optional">(اختياري)</span></label>
                  <input type="file" id="post-image" name="image" accept="image/jpeg,image/png,image/webp">
                </div>
 
              </div>
            </div>
 
            <div class="form-actions">
              <button type="submit" class="btn">نشر المنشور</button>
              <button type="button" class="btn btn-light" id="close-form">إلغاء</button>
            </div>
          </form>
 
        </div>
      </div>
    </div>
  </section>
 
</main>
  
  <footer class="footer" id="site-footer"></footer>

  <script src="layout.js" defer></script>


<script>
var formWrap = document.getElementById("form-wrap");
var openBtn = document.getElementById("open-form");
var postForm = document.getElementById("post-form");

function openForm() {
    formWrap.classList.add("open");
    openBtn.setAttribute("aria-expanded", "true");
}

function closeForm() {
    formWrap.classList.remove("open");
    openBtn.setAttribute("aria-expanded", "false");
    postForm.reset();
}

openBtn.addEventListener("click", openForm);

document.getElementById("close-form")
    .addEventListener("click", closeForm);

/* عرض المزيد / عرض أقل */
var postsGrid = document.getElementById("posts-grid");
var toggleBtn = document.getElementById("toggle-posts");

if (postsGrid.querySelectorAll(".post-extra").length === 0) {
    toggleBtn.parentNode.style.display = "none";
}

toggleBtn.addEventListener("click", function () {

    var showAll = postsGrid.classList.toggle("show-all");

    toggleBtn.textContent = showAll
        ? "عرض أقل"
        : "عرض المزيد";
});

/* فتح وإغلاق تعديل المنشور */
postsGrid.addEventListener("click", function (event) {

    var card = event.target.closest(".post-card");
    if (!card) return;

    var editor = card.querySelector(".post-editor");
    if (!editor) return;

    if (event.target.closest(".edit-post")) {

        editor.hidden = !editor.hidden;

    } else if (event.target.closest(".cancel-edit")) {

        editor.reset();
        editor.hidden = true;
    }
});

<?php if ($open_form): ?>
openForm();
<?php endif; ?>
</script>


</body>
</html>
