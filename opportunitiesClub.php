
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

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function validDate($value) {
    if (!is_string($value)) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $value
    );

    return $date &&
        $date->format("Y-m-d") === $value;
}

function validTime($value) {
    if (!is_string($value)) {
        return false;
    }

    $time = DateTimeImmutable::createFromFormat(
        "!H:i",
        $value
    );

    return $time &&
        $time->format("H:i") === $value;
}

$message = "";
$message_type = "";

$old = [];
$editing_id = 0;
$form_error = false;

/* =========================================
   إضافة فرصة جديدة
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = (string) ($_POST["action"] ?? "add");
    $opportunity_id = filter_var($_POST["opportunity_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if (in_array($action, ["add", "edit"], true)) {
        $old = $_POST;
        $editing_id = ($action === "edit" && $opportunity_id !== false) ? (int) $opportunity_id : 0;
    }

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            (string) $_POST["csrf_token"]
        )
    ) {
        $message = "انتهت صلاحية الطلب. أعيدي تحميل الصفحة.";
        $message_type = "error";

    } elseif (in_array($action, ["delete", "close"], true)) {
        if ($opportunity_id === false) {
            $message = "معرّف الفرصة غير صحيح.";
            $message_type = "error";
        } else {
            try {
                // لا نحذف فرصة لها أي طلب مرتبط بها، حتى لو كان مقبولًا أو مرفوضًا.
                if ($action === "delete") {
                    $stmt = $conn->prepare("DELETE FROM opportunity
                        WHERE opportunity_id = ? AND club_id = ?
                        AND NOT EXISTS (
                            SELECT 1 FROM application a
                            WHERE a.opportunity_id = opportunity.opportunity_id
                        )");
                    $stmt->bind_param("ii", $opportunity_id, $club_id);
                } else {
                    $stmt = $conn->prepare("UPDATE opportunity SET status = 'closed'
                        WHERE opportunity_id = ? AND club_id = ? AND status = 'open'");
                    $stmt->bind_param("ii", $opportunity_id, $club_id);
                }
                $stmt->execute();
                $changed = $stmt->affected_rows;
                $stmt->close();
                if ($changed > 0) {
                    $_SESSION["opportunity_success"] = $action === "delete"
                        ? "تم حذف الفرصة بنجاح." : "تم إغلاق الفرصة بنجاح.";
                    header("Location: opportunitiesClub.php");
                    exit;
                }
                $message = $action === "delete"
                    ? "لا يمكن حذف هذه الفرصة؛ قد تكون مرتبطة بطلبات طلاب. استخدمي إغلاق الفرصة بدلًا من ذلك."
                    : "لم يتم الإغلاق؛ تأكدي أن الفرصة موجودة ومتاحة.";
                $message_type = "error";
            } catch (Throwable $error) {
                error_log("Opportunity action error: " . $error->getMessage());
                $message = "تعذر تنفيذ العملية. تحققي من ارتباطات الفرصة.";
                $message_type = "error";
            }
        }
    } elseif (in_array($action, ["add", "edit"], true)) {

        $title = trim((string) ($_POST["title"] ?? ""));
        $description = trim((string) ($_POST["description"] ?? ""));
        $type = trim((string) ($_POST["type"] ?? ""));
        $semester = trim((string) ($_POST["semester"] ?? ""));
        $work_mode = trim((string) ($_POST["work_mode"] ?? ""));

        $start_date = (string) ($_POST["start_date"] ?? "");
        $end_date = (string) ($_POST["end_date"] ?? "");
        $start_time = (string) ($_POST["time"] ?? "");
        $deadline = (string) ($_POST["deadline"] ?? "");

        $location = trim((string) ($_POST["place"] ?? ""));
        $skills = trim((string) ($_POST["skills"] ?? ""));
        $main_tasks = trim((string) ($_POST["main_tasks"] ?? ""));

        $seats = filter_var(
            $_POST["seats"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $volunteer_hours = null;

        if (
            isset($_POST["volunteer_hours"]) &&
            $_POST["volunteer_hours"] !== ""
        ) {
            $volunteer_hours = filter_var(
                $_POST["volunteer_hours"],
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 0]]
            );
        }

        $valid = true;

        if (
            $title === "" ||
            mb_strlen($title) > 75 ||
            $description === "" ||
            $type === "" ||
            mb_strlen($type) > 75 ||
            $semester === "" ||
            mb_strlen($semester) > 50 ||
            !in_array(
                $work_mode,
                ["حضوري", "عن بعد", "هجين"],
                true
            ) ||
            $location === "" ||
            mb_strlen($location) > 75 ||
            $main_tasks === "" ||
            mb_strlen($skills) > 255 ||
            $seats === false ||
            (
                isset($_POST["volunteer_hours"]) &&
                $_POST["volunteer_hours"] !== "" &&
                $volunteer_hours === false
            )
        ) {
            $valid = false;
        }

        if (
            !validDate($start_date) ||
            !validDate($end_date) ||
            !validDate($deadline) ||
            !validTime($start_time)
        ) {
            $valid = false;
        }

        if ($valid) {

            if (
                $end_date < $start_date ||
                $deadline >= $start_date
            ) {
                $valid = false;
            }
        }

        if ($action === "edit" && $opportunity_id === false) {
            $valid = false;
        }

        if (!$valid) {
            $form_error = true;
            $message =
                "راجعي البيانات المدخلة. تأكدي من الحقول والتواريخ وعدد المقاعد.";
            $message_type = "error";

        } else {

            $status = "open";
            $registration_deadline = $deadline . " 23:59:59";

            $skills_value = $skills === "" ? null : $skills;

            try {

                if ($action === "edit") {
                    $stmt = $conn->prepare("UPDATE opportunity SET
                        title = ?, description = ?, type = ?, semester = ?,
                        work_mode = ?, start_date = ?, end_date = ?, start_time = ?,
                        location = ?, required_skills = ?, registration_deadline = ?,
                        max_seats = ?, main_tasks = ?, volunteer_hours = ?
                        WHERE opportunity_id = ? AND club_id = ?");
                    $stmt->bind_param(
                        "sssssssssssisiii",
                        $title, $description, $type, $semester, $work_mode,
                        $start_date, $end_date, $start_time, $location,
                        $skills_value, $registration_deadline, $seats,
                        $main_tasks, $volunteer_hours, $opportunity_id, $club_id
                    );
                } else {
                $stmt = $conn->prepare(
                    "INSERT INTO opportunity (
                        club_id,
                        title,
                        description,
                        type,
                        semester,
                        work_mode,
                        start_date,
                        end_date,
                        start_time,
                        location,
                        status,
                        required_skills,
                        registration_deadline,
                        max_seats,
                        main_tasks,
                        volunteer_hours
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?
                    )"
                );

                $stmt->bind_param(
                    "issssssssssssisi",
                    $club_id,
                    $title,
                    $description,
                    $type,
                    $semester,
                    $work_mode,
                    $start_date,
                    $end_date,
                    $start_time,
                    $location,
                    $status,
                    $skills_value,
                    $registration_deadline,
                    $seats,
                    $main_tasks,
                    $volunteer_hours
                );

                }
                $stmt->execute();
                $stmt->close();

                $_SESSION["opportunity_success"] = $action === "edit"
                    ? "تم حفظ تعديلات الفرصة بنجاح."
                    : "تمت إضافة الفرصة بنجاح.";

                header("Location: opportunitiesClub.php");
                exit;

            } catch (Throwable $error) {

                error_log(
                    "Opportunity insert error: " .
                    $error->getMessage()
                );

                $message =
                    "تعذر حفظ الفرصة. تحققي من البيانات وحاولي مجددًا.";
                $form_error = true;
                $message_type = "error";
            }
        }
    }
}

/* رسالة نجاح بعد إعادة التوجيه */

if (isset($_SESSION["opportunity_success"])) {

    $message = $_SESSION["opportunity_success"];
    $message_type = "success";

    unset($_SESSION["opportunity_success"]);
}

/* =========================================
   جلب فرص النادي وعدد المتقدمين
========================================= */

$stmt = $conn->prepare(
    "SELECT o.*,
        (SELECT COUNT(*) FROM application a
         WHERE a.opportunity_id = o.opportunity_id) AS applicants_count
     FROM opportunity o
     WHERE o.club_id = ?
     ORDER BY o.opportunity_id DESC"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();

$opportunities = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$status_labels = [
    "open" => "متاحة",
    "closed" => "مغلقة",
    "ended" => "منتهية"
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>مِراس - مسؤول النادي</title>
  <link rel="stylesheet" href="Style.css">

<style>
.opps-page {
    --teal-dark: #2E5560;
    --gold-dark: #EDD08A;
    --line: rgba(46, 85, 96, 0.18);

    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 34px 24px 60px;
}

.opps-page .page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}

.opps-page .page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 8px;
    font-size: 30px;
    color: var(--ink-dark);
}

.opps-page .page-title img {
    height: 34px;
}

.opps-page .page-subtitle {
    margin: 0;
    font-size: 16px;
    font-weight: 400;
}

.opps-page .btn {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 999px;
    border: 2px solid var(--ink);
    background-color: #2E5560;
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition:
        background-color .2s,
        color .2s,
        border-color .2s;
}

.opps-page .btn:hover {
    background-color: var(--gold);
    border-color: var(--gold);
    color: var(--ink-dark);
}

.opps-page .btn-light {
    background-color: transparent;
    color: var(--ink);
    border-color: var(--teal);
}

.opps-page .btn-light:hover {
    background-color: var(--yellow);
    border-color: var(--gold);
    color: var(--ink-dark);
}

.opps-page .btn-add {
    padding: 12px 28px;
    font-size: 16px;
}

.opps-page .section {
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    overflow: hidden;
}

.opps-page .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 22px;
    background-color: transparent;
    border-bottom: none;
}

.opps-page .section-title {
    margin: 0;
    font-size: 21px;
    color: var(--ink-dark);
}

.opps-page .legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.opps-page .section-body {
    padding: 20px 22px 22px;
    background-color: #fff;
}

.opps-page .status {
    display: inline-block;
    padding: 3px 14px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 500;
}

.opps-page .status-open {
    background-color: var(--teal-dark);
    border-color: var(--ink);
    color: #fff;
}

.opps-page .status-closed {
    background-color: var(--gold);
    border-color: var(--gold-dark);
    color: var(--ink-dark);
}

.opps-page .status-ended {
    background-color: var(--beige);
    border-color: var(--ink);
    color: var(--ink);
}

.opps-page .opps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.opps-page .opp-card {
    display: flex;
    flex-direction: column;
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    border-top: 5px solid var(--teal-light);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    padding: 18px 20px 20px;
}

.opps-page .opp-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.opps-page .opp-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--teal-dark);
}

.opps-page .opp-title {
    margin: 0 0 8px;
    font-size: 20px;
    color: var(--ink-dark);
}

.opps-page .opp-desc {
    margin: 0 0 14px;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.9;
}

.opps-page .opp-details {
    list-style: none;
    margin: auto 0 0;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--line);
    border-right: 5px solid var(--teal-light);
    background-color: var(--header-bg);
    font-size: 14px;
    line-height: 2;
}

.opps-page .opp-details b {
    margin-left: 4px;
    color: var(--ink-dark);
}

.opps-page .opp-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}
.opps-page .opp-actions .btn {
    padding: 7px 15px;
    font-size: 13px;
}
.opps-page .opp-actions form { margin: 0; }

.opps-page .empty {
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

.opps-page .empty p {
    margin: 0 0 16px;
}

.opps-page .add-section {
    margin-top: 28px;
}

.opps-page .add-text {
    margin: 0 0 16px;
    font-size: 15px;
    font-weight: 400;
}

.opps-page .form-wrap {
    display: grid;
    grid-template-rows: 0fr;
    visibility: hidden;
    transition:
        grid-template-rows .35s ease,
        visibility .35s;
}

.opps-page .form-wrap.open {
    grid-template-rows: 1fr;
    visibility: visible;
}

.opps-page .form-wrap-inner {
    min-height: 0;
    overflow: hidden;
}

.opps-page #opp-form {
    margin-top: 22px;
    background-color: var(--header-bg);
    border: 1px solid var(--line);
    border-top: 5px solid var(--teal-light);
    border-radius: 18px;
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
}

.opps-page .form-title {
    margin: 0;
    padding: 14px 22px;
    background-color: transparent;
    border-bottom: none;
    border-radius: 13px 13px 0 0;
    font-size: 19px;
    color: var(--ink-dark);
}

.opps-page .form-body {
    padding: 20px 22px 6px;
}

.opps-page .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.opps-page .field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.opps-page .field-full {
    grid-column: 1 / -1;
}

.opps-page .field label {
    font-size: 15px;
    font-weight: 500;
    color: var(--ink-dark);
}

.opps-page .field input,
.opps-page .field textarea,
.opps-page .field select {
    width: 100%;
    padding: 10px 14px;
    border-radius: 12px;
    border: 2px solid var(--teal-light);
    background-color: #fff;
    color: var(--ink);
    font-family: inherit;
    font-size: 15px;
}

.opps-page .field textarea {
    min-height: 100px;
    resize: vertical;
}

.opps-page .field input:focus,
.opps-page .field textarea:focus,
.opps-page .field select:focus {
    outline: none;
    border-color: var(--teal);
    box-shadow: 0 0 0 3px var(--yellow);
}

.opps-page .form-actions {
    display: flex;
    gap: 10px;
    padding: 18px 22px 22px;
}

@media (max-width: 900px) {
    .opps-page .opps-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 800px) {
    .opps-page {
        padding: 24px 16px 44px;
    }

    .opps-page .page-title {
        font-size: 24px;
    }

    .opps-page .btn-add {
        width: 100%;
        text-align: center;
    }

    .opps-page .section-head,
    .opps-page .section-body {
        padding-right: 16px;
        padding-left: 16px;
    }

    .opps-page .opps-grid,
    .opps-page .form-grid {
        grid-template-columns: 1fr;
    }

    .opps-page .form-actions .btn {
        flex: 1;
        text-align: center;
    }
}

.opps-page #published-opportunities {
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: visible;
}

.opps-page #published-opportunities .section-body {
    background: transparent;
}
</style>
</head>

<body data-role="club" data-page="manage-opportunities">

<!-- الهيدر من layout.js -->
<header class="header" id="site-header"></header>

<main class="page opps-page">

  <!-- عنوان الصفحة -->
  <div class="page-head">
    <div>
      <h1 class="page-title">
        <img src="image/sunMeras.png" alt="">
        إدارة الفرص
      </h1>

      <p class="page-subtitle">
        عرض الفرص التي نشرها النادي وإضافة فرص جديدة.
      </p>
    </div>
  </div>

  <!-- الفرص المنشورة -->
  <section class="section" id="published-opportunities">

    <div class="section-head">

      <h2 class="section-title">
        الفرص المنشورة
      </h2>

      <div class="legend">
        <span class="status status-open">متاحة</span>
        <span class="status status-closed">مغلقة</span>
        <span class="status status-ended">منتهية</span>
      </div>

    </div>

    <div class="section-body">

      <div class="opps-grid" id="opps-grid">

        <?php if (empty($opportunities)): ?>

          <div class="empty">

            <p>
              لم يقم النادي بنشر أي فرص حتى الآن.
            </p>

            <button
              type="button"
              class="btn btn-light"
              id="open-form-empty"
            >
              إضافة فرصة
            </button>

          </div>

        <?php else: ?>

          <?php foreach ($opportunities as $opp): ?>

            <?php
              $status = $opp["status"];

              if (!isset($status_labels[$status])) {
                  $status = "closed";
              }

              $date_text = $opp["start_date"];

              if (!empty($opp["start_time"])) {
                  $date_text .= "، " .
                      substr($opp["start_time"], 0, 5);
              }
            ?>

            <article class="opp-card">

              <div class="opp-top">

                <span class="opp-label">
                  <?= e($opp["type"]) ?>
                </span>

                <span class="status status-<?= e($status) ?>">
                  <?= e($status_labels[$status]) ?>
                </span>

              </div>

              <h3 class="opp-title">
                <?= e($opp["title"]) ?>
              </h3>

              <p class="opp-desc">
                <?= e($opp["description"]) ?>
              </p>

              <ul class="opp-details">

                <li>
                  <b>تاريخ الفرصة:</b>
                  <?= e($date_text) ?>
                </li>

                <li>
                  <b>عدد المتقدمين:</b>
                  <?= (int) $opp["applicants_count"] ?>
                </li>

              </ul>

              <div class="opp-actions">
                <button type="button" class="btn btn-light edit-opp"
                    data-id="<?= (int) $opp["opportunity_id"] ?>">تعديل</button>
                <?php if ($status === "open"): ?>
                  <form method="post" action="opportunitiesClub.php"
                        onsubmit="return confirm('هل تريدين إغلاق الفرصة ومنع التقديم الجديد؟');">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="action" value="close">
                    <input type="hidden" name="opportunity_id" value="<?= (int) $opp["opportunity_id"] ?>">
                    <button type="submit" class="btn btn-light">إغلاق</button>
                  </form>
                <?php endif; ?>
                <?php if ((int) $opp["applicants_count"] === 0): ?>
                  <form method="post" action="opportunitiesClub.php"
                        onsubmit="return confirm('هل أنتِ متأكدة من حذف الفرصة نهائيًا؟');">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="opportunity_id" value="<?= (int) $opp["opportunity_id"] ?>">
                    <button type="submit" class="btn btn-light">حذف</button>
                  </form>
                <?php endif; ?>
              </div>

            </article>

          <?php endforeach; ?>

        <?php endif; ?>

      </div>

    </div>

  </section>

  <!-- إضافة فرصة -->
  <section class="section add-section" id="add-opportunity">

    <div class="section-head">
      <h2 class="section-title">إضافة فرصة</h2>
    </div>

    <div class="section-body">

      <p class="add-text">
        يمكنك إضافة فرصة جديدة للنادي ليتمكن الطلاب من التقديم عليها.
      </p>

      <?php if ($message !== ""): ?>

        <p
          role="status"
          style="
            padding: 12px 16px;
            border-radius: 12px;
            background: <?= $message_type === "success"
                ? '#2E5560'
                : '#EDD08A' ?>;
            color: <?= $message_type === "success"
                ? '#fff'
                : '#2E5560' ?>;
            margin-bottom: 16px;
          "
        >
          <?= e($message) ?>
        </p>

      <?php endif; ?>

      <button
        type="button"
        class="btn btn-add"
        id="open-form"
        aria-expanded="false"
        aria-controls="form-wrap"
      >
        إضافة فرصة
      </button>

      <div
        class="form-wrap<?= $form_error ? ' open' : '' ?>"
        id="form-wrap"
      >

        <div class="form-wrap-inner">

          <form
            action="opportunitiesClub.php"
            method="post"
            id="opp-form"
          >

            <input type="hidden" name="action" id="opp-action" value="<?= $editing_id ? 'edit' : 'add' ?>">
            <input type="hidden" name="opportunity_id" id="opp-id" value="<?= $editing_id ?>">
            <input
              type="hidden"
              name="csrf_token"
              value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <h3 class="form-title">
              <span id="opp-form-heading"><?= $editing_id ? 'تعديل الفرصة' : 'إضافة فرصة جديدة' ?></span>
            </h3>

            <div class="form-body">

              <div class="form-grid">

                <!-- عنوان الفرصة -->
                <div class="field field-full">

                  <label for="opp-title">
                    عنوان الفرصة
                  </label>

                  <input
                    type="text"
                    id="opp-title"
                    name="title"
                    maxlength="75"
                    value="<?= e($old["title"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- الوصف -->
                <div class="field field-full">

                  <label for="opp-desc">
                    وصف الفرصة
                  </label>

                  <textarea
                    id="opp-desc"
                    name="description"
                    required
                  ><?= e($old["description"] ?? "") ?></textarea>

                </div>

                <!-- نوع الفرصة -->
                <div class="field">

                  <label for="opp-type">
                    نوع الفرصة
                  </label>

                  <input
                    type="text"
                    id="opp-type"
                    name="type"
                    maxlength="75"
                    placeholder="مثال: تطوعية، تدريبية"
                    value="<?= e($old["type"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- الفصل الدراسي -->
                <div class="field">

                  <label for="opp-semester">
                    الفصل الدراسي
                  </label>

                  <input
                    type="text"
                    id="opp-semester"
                    name="semester"
                    maxlength="50"
                    placeholder="مثال: الفصل الأول 2026"
                    value="<?= e($old["semester"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- نمط العمل -->
                <div class="field">

                  <label for="opp-work-mode">
                    نمط العمل
                  </label>

                  <select
                    id="opp-work-mode"
                    name="work_mode"
                    required
                  >

                    <option value="">
                      اختاري نمط العمل
                    </option>

                    <option
                      value="حضوري"
                      <?= ($old["work_mode"] ?? "") === "حضوري"
                          ? "selected" : "" ?>
                    >
                      حضوري
                    </option>

                    <option
                      value="عن بعد"
                      <?= ($old["work_mode"] ?? "") === "عن بعد"
                          ? "selected" : "" ?>
                    >
                      عن بعد
                    </option>

                    <option
                      value="هجين"
                      <?= ($old["work_mode"] ?? "") === "هجين"
                          ? "selected" : "" ?>
                    >
                      هجين
                    </option>

                  </select>

                </div>

                <!-- بداية الفرصة -->
                <div class="field">

                  <label for="opp-start-date">
                    تاريخ بداية الفرصة
                  </label>

                  <input
                    type="date"
                    id="opp-start-date"
                    name="start_date"
                    value="<?= e($old["start_date"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- نهاية الفرصة -->
                <div class="field">

                  <label for="opp-end-date">
                    تاريخ نهاية الفرصة
                  </label>

                  <input
                    type="date"
                    id="opp-end-date"
                    name="end_date"
                    value="<?= e($old["end_date"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- الوقت -->
                <div class="field">

                  <label for="opp-time">
                    وقت الفرصة
                  </label>

                  <input
                    type="time"
                    id="opp-time"
                    name="time"
                    value="<?= e($old["time"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- آخر موعد للتسجيل -->
                <div class="field">

                  <label for="opp-deadline">
                    آخر موعد للتسجيل
                  </label>

                  <input
                    type="date"
                    id="opp-deadline"
                    name="deadline"
                    value="<?= e($old["deadline"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- المكان -->
                <div class="field field-full">

                  <label for="opp-place">
                    مكان الفرصة
                  </label>

                  <input
                    type="text"
                    id="opp-place"
                    name="place"
                    maxlength="75"
                    value="<?= e($old["place"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- المهارات المطلوبة -->
                <div class="field field-full">

                  <label for="opp-skills">
                    المهارات المطلوبة (اختياري)
                  </label>

                  <textarea
                    id="opp-skills"
                    name="skills"
                    maxlength="255"
                  ><?= e($old["skills"] ?? "") ?></textarea>

                </div>

                <!-- المهام الأساسية -->
                <div class="field field-full">

                  <label for="opp-main-tasks">
                    المهام الأساسية
                  </label>

                  <textarea
                    id="opp-main-tasks"
                    name="main_tasks"
                    required
                  ><?= e($old["main_tasks"] ?? "") ?></textarea>

                </div>

                <!-- المقاعد -->
                <div class="field">

                  <label for="opp-seats">
                    عدد المقاعد المتاحة
                  </label>

                  <input
                    type="number"
                    id="opp-seats"
                    name="seats"
                    min="1"
                    value="<?= e($old["seats"] ?? "") ?>"
                    required
                  >

                </div>

                <!-- الساعات التطوعية -->
                <div class="field">

                  <label for="opp-volunteer-hours">
                    الساعات التطوعية (اختياري)
                  </label>

                  <input
                    type="number"
                    id="opp-volunteer-hours"
                    name="volunteer_hours"
                    min="0"
                    step="1"
                    value="<?= e($old["volunteer_hours"] ?? "") ?>"
                  >

                </div>

              </div>

            </div>

            <div class="form-actions">

              <button type="submit" class="btn">
                <span id="opp-submit-label"><?= $editing_id ? 'حفظ التعديلات' : 'إضافة الفرصة' ?></span>
              </button>

              <button
                type="button"
                class="btn btn-light"
                id="close-form"
              >
                إلغاء
              </button>

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
var oppForm = document.getElementById("opp-form");
var oppData = <?= json_encode(array_column($opportunities, null, 'opportunity_id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
function setEditMode(isEdit, id) {
    document.getElementById("opp-action").value = isEdit ? "edit" : "add";
    document.getElementById("opp-id").value = isEdit ? id : "";
    document.getElementById("opp-form-heading").textContent = isEdit ? "تعديل الفرصة" : "إضافة فرصة جديدة";
    document.getElementById("opp-submit-label").textContent = isEdit ? "حفظ التعديلات" : "إضافة الفرصة";
}
function startAdd() {
    oppForm.reset();
    setEditMode(false, "");
    openForm();
}
function startEdit(id) {
    var opp = oppData[id];
    if (!opp) return;
    var fields = {
        title: opp.title, description: opp.description, type: opp.type,
        semester: opp.semester, work_mode: opp.work_mode,
        start_date: opp.start_date, end_date: opp.end_date,
        time: (opp.start_time || "").substring(0, 5),
        deadline: (opp.registration_deadline || "").substring(0, 10),
        place: opp.location, skills: opp.required_skills,
        main_tasks: opp.main_tasks, seats: opp.max_seats,
        volunteer_hours: opp.volunteer_hours
    };
    oppForm.reset();
    Object.keys(fields).forEach(function(name) {
        var input = oppForm.elements.namedItem(name);
        if (input) input.value = fields[name] == null ? "" : fields[name];
    });
    setEditMode(true, id);
    openForm();
    document.getElementById("add-opportunity").scrollIntoView({behavior: "smooth"});
}
document.querySelectorAll(".edit-opp").forEach(function(btn) {
    btn.addEventListener("click", function() { startEdit(btn.dataset.id); });
});

function openForm() {

    formWrap.classList.add("open");

    openBtn.setAttribute(
        "aria-expanded",
        "true"
    );
}

function closeForm() {

    formWrap.classList.remove("open");

    openBtn.setAttribute(
        "aria-expanded",
        "false"
    );

    oppForm.reset();
    setEditMode(false, "");
}

openBtn.addEventListener("click", startAdd);

var emptyBtn = document.getElementById("open-form-empty");

if (emptyBtn) {
    emptyBtn.addEventListener("click", startAdd);
}

document.getElementById("close-form").addEventListener(
    "click",
    closeForm
);

/* إبقاء النموذج مفتوحًا إذا كانت فيه أخطاء */

if (formWrap.classList.contains("open")) {
    openBtn.setAttribute("aria-expanded", "true");
}

</script>

</body>
</html>
