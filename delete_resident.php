<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: residents.php");
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
    exit("Invalid request. Please reload the resident list and try again.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: residents.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$status = $stmt->affected_rows > 0 ? "deleted" : "not_found";

$stmt->close();

header("Location: residents.php?status=" . $status);
exit;
