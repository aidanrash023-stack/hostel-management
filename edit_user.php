<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "db_connect.php";

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
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

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: users.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT name, username FROM users WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    exit("Admin account not found.");
}

$name = $user["name"];
$username = $user["username"];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid request. Please reload the page.");
    }

    $nameInput = $_POST["name"] ?? "";
    $usernameInput = $_POST["username"] ?? "";
    $passwordInput = $_POST["password"] ?? "";
    $confirmInput = $_POST["confirm_password"] ?? "";

    $name = is_string($nameInput) ? trim($nameInput) : "";
    $username = is_string($usernameInput) ? trim($usernameInput) : "";
    $password = is_string($passwordInput) ? $passwordInput : "";
    $confirmPassword = is_string($confirmInput) ? $confirmInput : "";

    if ($name === "" || $username === "") {
        $error = "Please enter a name and username.";
    } elseif (strlen($name) > 100 || strlen($username) > 50) {
        $error = "Name or username exceeds the maximum length.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers and underscores.";
    } elseif (
        $password !== "" &&
        (strlen($password) < 8 || strlen($password) > 72)
    ) {
        $error = "Password must be between 8 and 72 bytes.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            if ($password === "") {
                $stmt = $conn->prepare(
                    "UPDATE users SET name = ?, username = ? WHERE id = ?"
                );
                $stmt->bind_param("ssi", $name, $username, $id);
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET name = ?, username = ?, password = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    "sssi",
                    $name,
                    $username,
                    $hashedPassword,
                    $id
                );
            }

            $stmt->execute();
            $stmt->close();

            if ($id === (int) $_SESSION["user_id"]) {
                $_SESSION["name"] = $name;
            }

            header("Location: users.php?status=updated");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $error = "This username is already taken.";
            } else {
                error_log($e->getMessage());
                $error = "Unable to update the account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin | Hostel Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav aria-label="Main navigation">
        <strong>Hostel Management</strong>
        <a href="dashboard.php">Dashboard</a>
        <a href="residents.php">Residents</a>
        <a href="users.php">Admins</a>

        <form action="logout.php" method="POST">
            <input type="hidden" name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">
            <button type="submit">Log Out</button>
        </form>
    </nav>

    <main>
        <h1>Edit Admin</h1>

        <?php if ($error !== ""): ?>
            <p class="error" role="alert"><?= escape($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="panel resident-form">
            <input type="hidden" name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">

            <label for="name">Admin Name</label>
            <input type="text" id="name" name="name"
                maxlength="100" required
                value="<?= escape($name) ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                maxlength="50" pattern="[a-zA-Z0-9_]+"
                title="Use letters, numbers and underscores only."
                autocomplete="off" required
                value="<?= escape($username) ?>">

            <p>Leave both password fields blank to keep the current password.</p>

            <label for="password">New Password</label>
            <input type="password" id="password" name="password"
                minlength="8" maxlength="72"
                autocomplete="new-password">

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password"
                name="confirm_password"
                minlength="8" maxlength="72"
                autocomplete="new-password">

            <div class="form-actions">
                <button type="submit" class="button">Save Changes</button>
                <a href="users.php" class="cancel-link">Cancel</a>
            </div>
        </form>
    </main>
</body>

</html>