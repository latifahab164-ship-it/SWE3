
<?php
session_start();
require_once "MerasDB.php";

// Check club session
if (
    !isset($_SESSION["role"], $_SESSION["club_id"]) ||
    $_SESSION["role"] !== "club"
) {
    header("Location: login.php");
    exit;
}

$club_id = (int) $_SESSION["club_id"];

// Escape output
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// 1. Get logged-in club information
$stmt = $conn->prepare(
    "SELECT club_id, club_name, logo
     FROM club
     WHERE club_id = ? AND is_active = 1"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$club) {
    exit("النادي غير موجود أو غير نشط.");
}

// 2. Count active club members
$stmt = $conn->prepare(
    "SELECT COUNT(DISTINCT university_id) AS total
     FROM membership
     WHERE club_id = ?
       AND status = 'active'
       AND (expiry_date IS NULL OR expiry_date >= CURDATE())"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$members_count = (int) $result["total"];
$stmt->close();

// 3. Get latest 3 club opportunities
$stmt = $conn->prepare(
    "SELECT opportunity_id, title, type,
            work_mode, start_date, start_time,
            registration_deadline, volunteer_hours
     FROM opportunity
     WHERE club_id = ?
     ORDER BY opportunity_id DESC
     LIMIT 3"
);

$stmt->bind_param("i", $club_id);
$stmt->execute();
$opportunities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Get latest 5 pending applications
$stmt = $conn->prepare(
    "SELECT
        a.application_id,
        a.application_type,
        a.application_date,
        s.full_name,
        o.title AS opportunity_title
     FROM application a
     INNER JOIN application_tracking t
         ON a.application_id = t.application_id
     INNER JOIN student s
         ON a.university_id = s.university_id
     LEFT JOIN opportunity o
         ON a.opportunity_id = o.opportunity_id
         AND o.club_id = a.club_id
     WHERE a.club_id = ?
       AND t.status = 'pending'
     ORDER BY a.application_date DESC,
              a.application_id DESC
     LIMIT 5"
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

  .club-dashboard {
      --teal-dark: #2E5560;
    --gold-dark: #EDD08A;
    --line: rgba(46, 85, 96, 0.18);
	
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 34px 24px 60px;
  }

  /* ---------- عنوان الصفحة ---------- */
  .club-dashboard .page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 28px;
    font-size: 30px;
    color: var(--ink-dark);
  }

  .club-dashboard .page-title img {
    height: 34px;
  }


  .club-dashboard .section {
    margin-bottom: 40px;
  }


  .club-dashboard .section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
    padding: 14px 22px;
background-color: transparent;
border-bottom: none;
    border-radius: 14px;
  }

  .club-dashboard .section-title {
    margin: 0;
    font-size: 21px;
    color: var(--ink-dark);
  }

 
  .club-dashboard .btn {
    display: inline-block;
    padding: 9px 22px;
    border-radius: 999px;
    border: 2px solid var(--ink);
    background-color: var(--ink);
    color: #2E5560;
    font-family: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color .2s, color .2s, border-color .2s;
  }

  .club-dashboard .btn:hover {
    border: 2px solid var(--gold);
    background-color: var(--gold);
    color: var(--ink-dark);
  }

  .club-dashboard .btn-light {
background-color: transparent;
color: var(--ink);
border-color: var(--teal);
  }

  .club-dashboard .btn-light:hover {
    background-color: var(--yellow);
    border-color: var(--teal-dark);
    color: var(--ink-dark);
  }


  .club-dashboard .section-head .btn-light {
    color: var(--ink);
    border-color: var(--teal);
  }

  .club-dashboard .section-head .btn-light:hover {
    background-color: var(--teal-light);
    border-color: var(--teal);
    color: var(--ink-dark);
  }


  .club-dashboard .card {
    background-color: #fff;
    border-radius: 18px;
    border: 1px solid var(--line);
    border-top: 5px solid var(--teal-light);
    box-shadow: 0 6px 20px rgba(46, 85, 96, 0.10);
    padding: 20px;
  }


  .club-dashboard .overview {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
  }

  .club-dashboard .club-card {
    display: flex;
    align-items: center;
    gap: 18px;
  }

  .club-dashboard .club-logo {
    width: 84px;
    height: 84px;
    flex-shrink: 0;
    border-radius: 50%;
    border: 3px solid var(--teal-dark);
    background-color: var(--beige);
    overflow: hidden;
  }

  .club-dashboard .club-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .club-dashboard .label {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 500;
    color: var(--teal-dark);
  }

  .club-dashboard .club-name {
    margin: 0;
    font-size: 24px;
    color: var(--ink-dark);
  }

  .club-dashboard .stat-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    border-top-color: var(--teal-light);
  }

  .club-dashboard .stat-number {
    margin: 0;
    font-size: 40px;
    font-weight: 500;
    line-height: 1.2;
    color: var(--ink-dark);
  }


  .club-dashboard .opportunities-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
  }

  .club-dashboard .opp-card {
    display: flex;
    flex-direction: column;
  }

  .club-dashboard .opp-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .club-dashboard .badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .club-dashboard .badge-opp {
    background-color: var(--teal-light);
    border: 1px solid var(--teal);
  }

  .club-dashboard .opp-mode {
    font-size: 13px;
    color: var(--teal-dark);
  }

  .club-dashboard .opp-title {
    margin: 0 0 4px;
    font-size: 20px;
    color: var(--ink-dark);
  }

  .club-dashboard .opp-club {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 400;
    color: var(--teal-dark);
  }


  .club-dashboard .opp-details {
    list-style: none;
    margin: 0 0 14px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--line);
    border-right: 5px solid var(--teal-light);
    background-color: var(--header-bg);
    font-size: 14px;
    line-height: 2;
  }

  .club-dashboard .opp-details b {
    margin-left: 4px;
    color: var(--ink-dark);
  }

  .club-dashboard .opp-link {
    display: inline-block;
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .club-dashboard .opp-link:hover {
    color: var(--teal-dark);
  }


  .club-dashboard .opp-card .btn {
    margin-top: auto;
    text-align: center;
    color: #fff;
  }


  .club-dashboard .legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 14px;
  }

  .club-dashboard .badge-member {
    background-color: var(--teal-light);
    border: 1px solid var(--teal);
  }

  .club-dashboard .badge-apply {
    background-color: var(--gold);
    border: 1px solid var(--gold-dark);
  }

  .club-dashboard .app-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }


  .club-dashboard .app-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    background-color: #fff;
    border-radius: 14px;
    border: 1px solid var(--line);
    border-right: 8px solid var(--teal-light);
    box-shadow: 0 4px 14px rgba(46, 85, 96, 0.08);
    padding: 14px 18px;
  }

  .club-dashboard .app-row.type-opportunity {
    border-right-color: var(--gold-dark);
  }

  .club-dashboard .app-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .club-dashboard .app-student {
    margin: 0;
    font-size: 17px;
    font-weight: 500;
    color: var(--ink-dark);
  }

  .club-dashboard .app-meta {
    margin: 0;
    font-size: 14px;
    font-weight: 400;
    color: var(--teal-dark);
  }

  .club-dashboard .app-side {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }

  .club-dashboard .status {
    padding: 3px 12px;
    border-radius: 999px;
    border: 1px solid var(--gold-dark);
    font-size: 13px;
    font-weight: 500;
    background-color: var(--yellow);
    color: var(--ink-dark);
  }


  .club-dashboard .empty {
    grid-column: 1 / -1;
    margin: 0;
    text-align: center;
    padding: 34px 20px;
    border-radius: 18px;
    border: 2px dashed var(--teal-dark);
    background-color: var(--header-bg);
    font-size: 16px;
    color: var(--ink-dark);
  }


  @media (max-width: 900px) {
    .club-dashboard .opportunities-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 800px) {
    .club-dashboard {
      padding: 24px 16px 44px;
    }

    .club-dashboard .page-title {
      font-size: 24px;
    }

    .club-dashboard .section-head {
      padding-right: 16px;
      padding-left: 16px;
    }

    .club-dashboard .overview,
    .club-dashboard .opportunities-grid {
      grid-template-columns: 1fr;
    }

    .club-dashboard .club-card {
      flex-direction: column;
      text-align: center;
    }

    .club-dashboard .app-row {
      flex-direction: column;
      align-items: flex-start;
    }

    .club-dashboard .app-side {
      width: 100%;
      justify-content: space-between;
    }
  }
</style>
</head>
<body data-role="club" data-page="home">

  <!-- ===== Club Admin Header ===== -->
<header class="header" id="site-header"></header>

<main class="page club-dashboard">

  <h1 class="page-title">
    <img src="image/sunMeras.png" alt="">
    لوحة تحكم النادي
  </h1>

  <!-- ===== أ. نظرة عامة على النادي ===== -->
  <section class="section" id="club-overview">
    <div class="section-head">
      <h2 class="section-title">نظرة عامة على النادي</h2>
    </div>

    <div class="card club-card">

    <div class="club-logo">
        <?php if (!empty($club["logo"])): ?>
            <img
                src="image/<?= e(basename($club["logo"])) ?>"
                alt="شعار النادي"
            >
        <?php endif; ?>
    </div>

    <div>
        <p class="label">اسم النادي</p>

        <h3 class="club-name" id="club-name">
            <?= e($club["club_name"]) ?>
        </h3>
    </div>

</div>

      <div class="card stat-card">
        <p class="label">عدد الطلاب المنضمين</p>
        <p class="stat-number" id="members-count">
    <?= $members_count ?>
</p>
      </div>

    </div>
  </section>


  <!-- ===== ب. الفرص المنشورة ===== -->
  <section class="section" id="published-opportunities">
    <div class="section-head">
      <h2 class="section-title">الفرص المنشورة</h2>
     
      <a href="opportunitiesClub.php" class="btn btn-light">عرض الكل</a>
    </div>

    <!-- بيانات تجريبية للعرض فقط: تُستبدل بالبيانات من قاعدة البيانات -->
   
<div class="opportunities-grid" id="opportunities-grid">

    <?php if (empty($opportunities)): ?>

        <p class="empty">
            لم يقم النادي بنشر أي فرص حتى الآن.
        </p>

    <?php else: ?>

        <?php foreach ($opportunities as $opp): ?>

            <article class="card opp-card">

                <div class="opp-top">
                    <span class="badge badge-opp">
                        <?= e($opp["type"]) ?>
                    </span>

                    <span class="opp-mode">
                        <?= e($opp["work_mode"]) ?>
                    </span>
                </div>

                <h3 class="opp-title">
                    <?= e($opp["title"]) ?>
                </h3>

                <p class="opp-club">
                    <?= e($club["club_name"]) ?>
                </p>

                <ul class="opp-details">
                    <li>
                        <b>التاريخ:</b>
                        <?= e($opp["start_date"]) ?>
                        <?= e($opp["start_time"]) ?>
                    </li>

                    <li>
                        <b>الساعات التطوعية:</b>
                        <?= $opp["volunteer_hours"] !== null
                            ? (int) $opp["volunteer_hours"] . " ساعة"
                            : "غير محددة" ?>
                    </li>

                    <li>
                        <b>آخر موعد للتسجيل:</b>
                        <?= e($opp["registration_deadline"]) ?>
                    </li>
                </ul>

                <a href="opportunitiesClub.php" class="opp-link">
                    ◄ التفاصيل
                </a>

                <a href="applications.php" class="btn">
                    عرض المتقدمين
                </a>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

  </section>


  <!-- ===== ج. طلبات الطلاب ===== -->
  <section class="section" id="student-applications">
    <div class="section-head">
      <h2 class="section-title">طلبات الطلاب</h2>
      <a href="applications.html" class="btn btn-light">عرض الكل</a>
    </div>

    <div class="legend">
      <span class="badge badge-member">طلب انضمام للنادي</span>
      <span class="badge badge-apply">طلب تقديم على فرصة</span>
    </div>


    
<div class="app-list" id="applications-preview">

    <?php if (empty($applications)): ?>

        <p class="empty" id="apps-empty">
            لا توجد طلبات قيد المراجعة حتى الآن.
        </p>

    <?php else: ?>

        <?php foreach ($applications as $app): ?>

            <?php
                $is_opportunity =
                    $app["opportunity_id"] ?? null;

                // Use the actual application type
                $is_opportunity =
                    $app["opportunity_title"] !== null;
            ?>

            <article class="app-row <?= $is_opportunity
                ? 'type-opportunity'
                : 'type-membership' ?>"
                data-id="<?= (int) $app["application_id"] ?>"
                data-status="pending">

                <div class="app-info">

                    <h3 class="app-student">
                        <?= e($app["full_name"]) ?>
                    </h3>

                    <p class="app-meta">

                        <?php if ($is_opportunity): ?>
                            الفرصة:
                            <?= e($app["opportunity_title"]) ?>
                            •
                        <?php endif; ?>

                        تاريخ الطلب:
                        <?= e($app["application_date"]) ?>

                    </p>

                </div>

                <div class="app-side">

                    <span class="badge <?= $is_opportunity
                        ? 'badge-apply'
                        : 'badge-member' ?>">

                        <?= $is_opportunity
                            ? "طلب تقديم على فرصة"
                            : "طلب انضمام للنادي" ?>

                    </span>

                    <span class="status">
                        قيد المراجعة
                    </span>

                </div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</div>
  </section>

</main>

  <footer class="footer" id="site-footer"></footer>

  <script src="layout.js" defer></script>


  <script>
    var previewRows = document.querySelectorAll("#applications-preview .app-row");
    var visibleCount = 0;

    previewRows.forEach(function (row) {
      if (row.dataset.status !== "pending") {
        row.style.display = "none";
      } else {
        visibleCount++;
      }
    });

    if (visibleCount === 0) {
      document.getElementById("apps-empty").style.display = "";
    }
  </script>

</body>
</html>



