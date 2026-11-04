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
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

if (!isset($_SESSION["applications_csrf"])) {
    $_SESSION["applications_csrf"] = bin2hex(random_bytes(32));
}

$error = "";

/* قبول أو رفض الطلب */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $application_id = filter_var(
        $_POST["application_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $decision = $_POST["decision"] ?? "";
    $token = $_POST["csrf"] ?? "";

    if (
        !$application_id ||
        !in_array($decision, ["accepted", "rejected"], true) ||
        !is_string($token) ||
        !hash_equals($_SESSION["applications_csrf"], $token)
    ) {
        $error = "طلب غير صالح.";

    } else {

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare(
                "SELECT a.university_id, a.application_type,
                        a.opportunity_id, t.status
                 FROM application a
                 INNER JOIN application_tracking t
                    ON t.application_id = a.application_id
                 WHERE a.application_id = ?
                   AND a.club_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param(
                "ii",
                $application_id,
                $club_id
            );

            $stmt->execute();
            $application = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$application || $application["status"] !== "pending") {
                throw new Exception("هذا الطلب غير موجود أو تمت مراجعته مسبقًا.");
            }

            $stmt = $conn->prepare(
                "UPDATE application_tracking
                 SET status = ?
                 WHERE application_id = ?
                   AND status = 'pending'"
            );

            $stmt->bind_param(
                "si",
                $decision,
                $application_id
            );

            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new Exception("تعذر تحديث حالة الطلب.");
            }

            $stmt->close();

            /* تحديث العضوية عند قبول طلب الانضمام */
            $type = strtolower(trim($application["application_type"]));

            $is_membership = in_array(
                $type,
                ["membership", "both"],
                true
            );

            if ($is_membership && $decision === "accepted") {

                $university_id = $application["university_id"];

                $stmt = $conn->prepare(
                    "SELECT membership_id
                     FROM membership
                     WHERE university_id = ?
                       AND club_id = ?
                     LIMIT 1
                     FOR UPDATE"
                );

                $stmt->bind_param(
                    "si",
                    $university_id,
                    $club_id
                );

                $stmt->execute();
                $membership = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($membership) {

                    $stmt = $conn->prepare(
                        "UPDATE membership
                         SET status = 'active',
                             joined_date = CURDATE(),
                             expiry_date = DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
                         WHERE membership_id = ?"
                    );

                    $stmt->bind_param(
                        "i",
                        $membership["membership_id"]
                    );

                } else {

                    $stmt = $conn->prepare(
                        "INSERT INTO membership
                         (university_id, club_id, status, joined_date, expiry_date)
                         VALUES (?, ?, 'active', CURDATE(),
                                 DATE_ADD(CURDATE(), INTERVAL 6 MONTH))"
                    );

                    $stmt->bind_param(
                        "si",
                        $university_id,
                        $club_id
                    );
                }

                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

            header("Location: applications.php?updated=1");
            exit;

        } catch (Throwable $ex) {

            $conn->rollback();
            error_log($ex->getMessage());
            $error = "تعذر معالجة الطلب. حاولي مرة أخرى.";
        }
    }
}

/* جلب الطلبات قيد المراجعة للنادي الحالي */
$stmt = $conn->prepare(
    "SELECT a.application_id,
            a.application_type,
            a.application_date,
            a.opportunity_id,
            s.full_name,
            s.major,
            s.email,
            s.phone_number,
            s.bio,
            o.title AS opportunity_title
     FROM application a
     INNER JOIN application_tracking t
        ON t.application_id = a.application_id
     INNER JOIN student s
        ON s.university_id = a.university_id
     LEFT JOIN opportunity o
        ON o.opportunity_id = a.opportunity_id
       AND o.club_id = a.club_id
     WHERE a.club_id = ?
       AND t.status = 'pending'
     ORDER BY a.application_date DESC,
              a.application_id DESC"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
 
  .apps-page {
    --teal-dark: #2E5560;
    --gold-dark: #EDD08A;
    --line: rgba(46, 85, 96, 0.18);

    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 34px 24px 60px;
  }


  .apps-page .page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 8px;
    font-size: 30px;
    color: var(--ink-dark);
  }

  .apps-page .page-title img {
    height: 34px;
  }

  .apps-page .page-subtitle {
    margin: 0 0 28px;
    font-size: 16px;
    font-weight: 400;
  }


.apps-page .filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
    padding: 14px 18px;
    background-color: transparent;
    border-radius: 0;
    border: none;
    box-shadow: none;
}

  .apps-page .filter-label {
    margin-left: 6px;
    font-size: 15px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .apps-page .filter-btn {
    padding: 9px 20px;
    border-radius: 999px;
    border: 2px solid var(--teal);
    background-color: transparent;
    color: var(--ink-dark);
    font-family: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color .2s, color .2s;
  }

  .apps-page .filter-btn:hover {
    background-color: var(--yellow);
  }


.apps-page .filter-btn.active {
    background-color: var(--ink);
    border-color: #2E5560;
    color: #fff;
}

  
  .apps-page .section {
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    overflow: hidden;
  }


  .apps-page .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 22px;
    background-color: transparent;
    border-bottom: none;
  }

  .apps-page .section-title {
    margin: 0;
    font-size: 21px;
    color: var(--ink-dark);
  }

  .apps-page .legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .apps-page .section-body {
    padding: 20px 22px 22px;
    background-color: #fff;
  }


  .apps-page .badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .apps-page .badge-member {
    background-color: var(--teal-light);
    border: 1px solid var(--teal);
  }

  .apps-page .badge-apply {
    background-color: var(--gold);
    border: 1px solid var(--gold-dark);
  }


  .apps-page .app-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }


  .apps-page .app-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    background-color: #fff;
    border-radius: 14px;
    border: 1px solid var(--line);
    border-right: 8px solid var(--teal-light);
    box-shadow: 0 4px 14px rgba(46, 85, 96, 0.08);
    padding: 16px 20px;
  }

  .apps-page .app-row.type-opportunity {
    border-right-color: var(--gold-dark);
  }

  .apps-page .app-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .apps-page .app-student {
    margin: 0;
    font-size: 18px;
    color: var(--ink-dark);
  }

  .apps-page .app-meta {
    margin: 0;
    font-size: 14px;
    font-weight: 400;
    color: var(--teal-dark);
  }

  .apps-page .app-side {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }

  .apps-page .status {
    padding: 3px 12px;
    border-radius: 999px;
    border: 1px solid var(--gold-dark);
    font-size: 13px;
    font-weight: 500;
    background-color: var(--yellow);
    color: var(--ink-dark);
  }


  .apps-page .app-actions {
    display: flex;
    gap: 8px;
  }

  .apps-page .btn-action {
    padding: 6px 18px;
    border-radius: 999px;
    border: 2px solid var(--ink-dark);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color .2s, color .2s, border-color .2s;
  }

.apps-page .btn-accept {
    background-color: var(--ink);
    border-color: #2E5560;
    color: #fff;
}

.apps-page .btn-accept:hover {
    background-color: var(--gold);
    border-color: var(--gold);
    color: var(--ink-dark);
}

.apps-page .btn-reject {
    background-color: transparent;
    border-color: var(--teal);
    color: var(--ink-dark);
}

.apps-page .btn-reject:hover {
    background-color: var(--yellow);
    border-color: var(--gold);
    color: var(--ink-dark);
}


  .apps-page .empty {
    margin: 0;
    text-align: center;
    padding: 36px 20px;
    border-radius: 14px;
    border: 2px dashed var(--teal-dark);
    background-color: #fff;
    font-size: 16px;
    color: var(--ink-dark);
  }


  @media (max-width: 800px) {
    .apps-page {
      padding: 24px 16px 44px;
    }

    .apps-page .page-title {
      font-size: 24px;
    }

    .apps-page .filter-bar {
      padding: 14px;
    }

    .apps-page .filter-label {
      width: 100%;
    }

    .apps-page .filter-btn {
      flex: 1 1 auto;
      text-align: center;
    }

    .apps-page .section-head,
    .apps-page .section-body {
      padding-right: 16px;
      padding-left: 16px;
    }

    .apps-page .app-row {
      flex-direction: column;
      align-items: flex-start;
    }

    .apps-page .app-side {
      width: 100%;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .apps-page .app-actions {
      width: 100%;
    }

    .apps-page .btn-action {
      flex: 1;
    }
  }
  
  .apps-page #all-applications {
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: visible;
}

.apps-page #all-applications .section-body {
    background: transparent;
}

  /* تفاصيل الطلب والملف الشخصي */
  .apps-page .btn-details {
    background: transparent;
    border-color: var(--teal);
    color: var(--ink-dark);
  }
  .apps-page .btn-details:hover { background: var(--yellow); }
  .apps-page .details-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1000;
  }
  .apps-page .details-box {
    width: 100%;
    max-width: 430px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    color: var(--ink-dark);
  }
  .apps-page .details-box h3 { margin: 0 0 16px; }
  .apps-page .details-box p { margin: 10px 0; }
  .apps-page .details-box .btn-action { margin-top: 16px; }

  .apps-page .app-actions form {
    margin: 0;
    display: inline-flex;
}
</style>

</head>

<body data-role="club" data-page="manage-requests">

  <!-- ===== Club Admin Header ===== -->
<header class="header" id="site-header"></header>

  <main class="page apps-page">

  <h1 class="page-title">
    <img src="image/sunMeras.png" alt="">
    طلبات الطلاب
  </h1>
  <p class="page-subtitle">الطلبات قيد المراجعة المقدمة إلى النادي: طلبات الانضمام وطلبات التقديم على الفرص.</p>

<?php if ($error !== ""): ?>
    <p role="alert" style="color:#9B2525; margin-bottom:16px;">
        <?= e($error) ?>
    </p>
<?php endif; ?>

<?php if (isset($_GET["updated"]) && $_GET["updated"] === "1"): ?>
    <p role="status" style="color:#2E5560; margin-bottom:16px;">
        تمت مراجعة الطلب بنجاح.
    </p>
<?php endif; ?>

  <!-- ===== التصفية ===== -->
  <div class="filter-bar" id="filter-bar">
    <span class="filter-label">عرض:</span>
    <button type="button" class="filter-btn active" data-filter="all">جميع الطلبات</button>
    <button type="button" class="filter-btn" data-filter="membership">طلبات الانضمام للنادي</button>
    <button type="button" class="filter-btn" data-filter="opportunity">طلبات التقديم على الفرص</button>
  </div>

  <!-- ===== كل الطلبات ===== -->
  <section class="section" id="all-applications">
    <div class="section-head">
      <h2 class="section-title">الطلبات المستلمة</h2>
      <div class="legend">
        <span class="badge badge-member">طلب انضمام للنادي</span>
        <span class="badge badge-apply">طلب تقديم على فرصة</span>
      </div>
    </div>

    <div class="section-body">
      <div class="app-list" id="app-list">

     
<?php foreach ($applications as $app): ?>

    <?php
        $type = strtolower(trim($app["application_type"]));

        $is_membership = in_array(
            $type,
            ["membership", "both"],
            true
        );

        $is_opportunity = in_array(
            $type,
            ["opportunity", "both"],
            true
        );

        $filter_type = $type === "membership"
            ? "membership"
            : "opportunity";

        $badge_text = $type === "both"
            ? "طلب انضمام وفرصة"
            : ($is_membership
                ? "طلب انضمام للنادي"
                : "طلب تقديم على فرصة");
    ?>

    <article
        class="app-row <?= $is_opportunity ? 'type-opportunity' : 'type-membership' ?>"
        data-id="<?= (int) $app["application_id"] ?>"
        data-type="<?= e($filter_type) ?>"
        data-status="pending"
        data-application-type="<?= e($type) ?>"
    >

        <div class="app-info">

            <h3 class="app-student">
                <?= e($app["full_name"]) ?>
            </h3>

            <p class="app-meta">
                التخصص: <?= e($app["major"] ?: "غير محدد") ?>
            </p>

            <?php if ($is_opportunity): ?>
                <p class="app-meta">
                    الفرصة:
                    <?= e($app["opportunity_title"] ?: "غير محددة") ?>
                </p>
            <?php endif; ?>

            <p class="app-meta">
                تاريخ الطلب:
                <?= e($app["application_date"]) ?>
            </p>

        </div>

        <div class="app-side">

            <span class="badge <?= $is_opportunity ? 'badge-apply' : 'badge-member' ?>">
                <?= e($badge_text) ?>
            </span>

            <span class="status">قيد المراجعة</span>

            <div class="app-actions">

                <button
                    type="button"
                    class="btn-action btn-details"
                    data-email="<?= e($app["email"]) ?>"
                    data-phone="<?= e($app["phone_number"]) ?>"
                    data-bio="<?= e($app["bio"]) ?>"
                >
                    عرض التفاصيل
                </button>

                <form method="post" action="applications.php">
                    <input type="hidden"
                           name="application_id"
                           value="<?= (int) $app["application_id"] ?>">

                    <input type="hidden"
                           name="csrf"
                           value="<?= e($_SESSION["applications_csrf"]) ?>">

                    <input type="hidden"
                           name="decision"
                           value="accepted">

                    <button type="submit"
                            class="btn-action btn-accept">
                        قبول
                    </button>
                </form>

                <form method="post" action="applications.php">
                    <input type="hidden"
                           name="application_id"
                           value="<?= (int) $app["application_id"] ?>">

                    <input type="hidden"
                           name="csrf"
                           value="<?= e($_SESSION["applications_csrf"]) ?>">

                    <input type="hidden"
                           name="decision"
                           value="rejected">

                    <button type="submit"
                            class="btn-action btn-reject">
                        رفض
                    </button>
                </form>

            </div>

        </div>

    </article>

<?php endforeach; ?>

<p class="empty" id="empty-message">
    لا توجد طلبات قيد المراجعة حالياً
</p>

      </div>
    </div>
  </section>

  <!-- عرض تفاصيل الطلب والملف الشخصي -->
  <div class="details-overlay" id="details-overlay" role="dialog" aria-modal="true" aria-labelledby="details-title">
    <div class="details-box">
      <h3 id="details-title">تفاصيل الطلب والملف الشخصي</h3>
      <div id="details-content"></div>
      <button type="button" class="btn-action btn-details" id="close-details">إغلاق</button>
    </div>
  </div>

</main>

  <!-- ===== Footer (same in both pages) ===== -->
  <footer class="footer" id="site-footer"></footer>

  <script src="layout.js" defer></script>


<script>
var buttons = document.querySelectorAll(".apps-page .filter-btn");
var appList = document.getElementById("app-list");
var emptyMessage = document.getElementById("empty-message");
var currentFilter = "all";

var emptyTexts = {
    all: "لا توجد طلبات قيد المراجعة حالياً",
    membership: "لا توجد طلبات انضمام قيد المراجعة حالياً",
    opportunity: "لا توجد طلبات تقديم على الفرص قيد المراجعة حالياً"
};

function applyFilter(filter) {

    var rows = document.querySelectorAll(".apps-page .app-row");
    var visible = 0;

    rows.forEach(function (row) {

        var type = row.dataset.applicationType;

        var typeMatch =
            filter === "all" ||
            type === filter ||
            type === "both";

        var show =
            typeMatch && row.dataset.status === "pending";

        row.style.display = show ? "" : "none";

        if (show) visible++;
    });

    emptyMessage.textContent = emptyTexts[filter];
    emptyMessage.style.display = visible === 0 ? "" : "none";
}

buttons.forEach(function (btn) {

    btn.addEventListener("click", function () {

        buttons.forEach(function (b) {
            b.classList.remove("active");
        });

        btn.classList.add("active");

        currentFilter = btn.dataset.filter;
        applyFilter(currentFilter);
    });
});

/* عرض تفاصيل الطالب */
var detailsOverlay = document.getElementById("details-overlay");
var detailsContent = document.getElementById("details-content");
var closeDetails = document.getElementById("close-details");

appList.addEventListener("click", function (event) {

    var btn = event.target.closest(".btn-details");
    if (!btn) return;

    var row = btn.closest(".app-row");
    if (!row) return;

    var name = row.querySelector(".app-student").textContent.trim();

    var info = Array.from(
        row.querySelectorAll(".app-meta"),
        function (p) {
            return p.textContent.trim();
        }
    );

    var details = [
        "الطالب: " + name,
        "البريد الإلكتروني: " + (btn.dataset.email || "غير متوفر"),
        "رقم الجوال: " + (btn.dataset.phone || "غير متوفر"),
        "نبذة الطالب: " + (btn.dataset.bio || "غير متوفرة")
    ];

    detailsContent.replaceChildren();

    info.concat(details).forEach(function (value) {

        var p = document.createElement("p");
        p.textContent = value;
        detailsContent.appendChild(p);
    });

    detailsOverlay.style.display = "flex";
    closeDetails.focus();
});

function hideDetails() {
    detailsOverlay.style.display = "none";
}

closeDetails.addEventListener("click", hideDetails);

detailsOverlay.addEventListener("click", function (event) {
    if (event.target === detailsOverlay) {
        hideDetails();
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        hideDetails();
    }
});

applyFilter("all");
</script>


</body>
</html>
