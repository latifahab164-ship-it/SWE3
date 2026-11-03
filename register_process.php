
<?php
require_once "MerasDB.php";

header("Content-Type: application/json; charset=utf-8");

function respond($success, $message) {
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    respond(false, "طريقة الطلب غير صحيحة.");
}

// Receive student information
$fullName = trim($_POST["full_name"] ?? "");
$universityId = trim($_POST["university_id"] ?? "");
$mobile = trim($_POST["phone_number"] ?? "");
$major = trim($_POST["major"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// Check required fields
if (
    $fullName === "" ||
    $universityId === "" ||
    $mobile === "" ||
    $major === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    respond(false, "يرجى تعبئة جميع الحقول.");
}

// Validate information
if (!preg_match('/^[0-9]{10}$/', $universityId)) {
    respond(false, "الرقم الجامعي يجب أن يتكون من 10 أرقام.");
}

if (!preg_match('/^05[0-9]{8}$/', $mobile)) {
    respond(false, "رقم الجوال غير صحيح.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "البريد الإلكتروني غير صحيح.");
}

if (
    mb_strlen($fullName) > 75 ||
    mb_strlen($major) > 100 ||
    strlen($email) > 150
) {
    respond(false, "بعض البيانات تتجاوز الطول المسموح.");
}

if (strlen($password) < 8) {
    respond(false, "كلمة المرور يجب ألا تقل عن 8 أحرف.");
}

if ($password !== $confirmPassword) {
    respond(false, "كلمتا المرور غير متطابقتين.");
}

// Check duplicate university ID
$stmt = $conn->prepare(
    "SELECT university_id FROM student WHERE university_id = ?"
);

$stmt->bind_param("s", $universityId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    respond(false, "الرقم الجامعي مسجل مسبقًا.");
}

$stmt->close();

// Check duplicate email
$stmt = $conn->prepare(
    "SELECT email FROM student WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    respond(false, "البريد الإلكتروني مسجل مسبقًا.");
}

$stmt->close();

// Hash password securely
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Save student to database
$stmt = $conn->prepare(
    "INSERT INTO student
    (university_id, full_name, email, phone_number,
     major, password_hash, status)
    VALUES (?, ?, ?, ?, ?, ?, 'active')"
);

$stmt->bind_param(
    "ssssss",
    $universityId,
    $fullName,
    $email,
    $mobile,
    $major,
    $passwordHash
);

try {
    $stmt->execute();
    $stmt->close();

    respond(true, "تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.");

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(false, "تعذر إنشاء الحساب. تحققي من البيانات وحاولي مرة أخرى.");
}
?>
