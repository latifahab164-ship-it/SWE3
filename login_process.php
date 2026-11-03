
<?php
session_start();
require_once "MerasDB.php";

header("Content-Type: application/json; charset=utf-8");

function respond($success, $message, $redirect = null) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "redirect" => $redirect
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    respond(false, "طريقة الطلب غير صحيحة.");
}

$type = $_POST["user_type"] ?? "";
$identifier = trim($_POST["identifier"] ?? "");
$password = $_POST["password"] ?? "";

if (!in_array($type, ["student", "club"], true)
    || $identifier === ""
    || $password === "") {
    respond(false, "يرجى إدخال بيانات تسجيل الدخول.");
}

if ($type === "student") {

    if (!preg_match('/^[0-9]{10}$/', $identifier)) {
        respond(false, "الرقم الجامعي غير صحيح.");
    }

    $stmt = $conn->prepare(
        "SELECT university_id, password_hash, status
         FROM student
         WHERE university_id = ?"
    );

    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user["password_hash"])) {
        respond(false, "الرقم الجامعي أو كلمة المرور غير صحيحة.");
    }

    if ($user["status"] !== "active") {
        respond(false, "هذا الحساب غير نشط.");
    }

    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION["role"] = "student";
    $_SESSION["university_id"] = $user["university_id"];

    respond(true, "تم تسجيل الدخول بنجاح.", "home.php");

} else {

    if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        respond(false, "البريد الإلكتروني غير صحيح.");
    }

    $stmt = $conn->prepare(
        "SELECT ca.club_id, ca.password_hash
         FROM club_account ca
         JOIN club c ON ca.club_id = c.club_id
         WHERE ca.email = ? AND c.is_active = 1"
    );

    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user["password_hash"])) {
        respond(false, "البريد الإلكتروني أو كلمة المرور غير صحيحة.");
    }

    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION["role"] = "club";
    $_SESSION["club_id"] = (int)$user["club_id"];

    respond(true, "تم تسجيل الدخول بنجاح.", "clubAdmin.php");
}
?>
