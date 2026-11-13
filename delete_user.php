<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit;
}

require_once "db_connect.php";

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Invalid request. Please reload the page.");
}

// Check that the logged-in account still exists.
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentUser) {
    $_SESSION = [];
    session_destroy();
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: users.php");
    exit;
}

$currentUserId = (int) $_SESSION["user_id"];

if ($id === $currentUserId) {
    header("Location: users.php?status=self_delete");
    exit;
}

try {
    $stmt = $conn->prepare(
        "DELETE FROM users WHERE id = ? AND id <> ?"
    );
    $stmt->bind_param("ii", $id, $currentUserId);
    $stmt->execute();

    $status = $stmt->affected_rows > 0 ? "deleted" : "not_found";
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $status = "delete_failed";
}

header("Location: users.php?status=" . $status);
exit;
