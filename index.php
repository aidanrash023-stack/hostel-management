<?php
session_start();
require_once "db_connect.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$error = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid request. Please reload the login page.");
    }

    $usernameInput = $_POST["username"] ?? "";
    $passwordInput = $_POST["password"] ?? "";

    $username = is_string($usernameInput) ? trim($usernameInput) : "";
    $password = is_string($passwordInput) ? $passwordInput : "";

    $stmt = $conn->prepare(
        "SELECT id, name, password FROM users WHERE username = ?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION = [
            "user_id" => $user["id"],
            "name" => $user["name"],
            "csrf_token" => bin2hex(random_bytes(32))
        ];

        header("Location: dashboard.php");
        exit;
    }

    $error = "Incorrect username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Hostel Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #eef2f6;
            font-family: Arial, sans-serif;
            color: #183153;
        }

        .login-box {
            width: min(400px, 90%);
            margin: 25px 0;
            padding: 35px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px #18315315;
        }

        h1 {
            margin-top: 0;
            font-size: 26px;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 16px 0 8px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font: inherit;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 24px;
            background: #183153;
            color: white;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font: inherit;
        }

        button:hover {
            background: #294c78;
        }

        .error {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <main class="login-box">
        <h1>Hostel Management</h1>
        <p class="subtitle">Log in to your admin account.</p>

        <?php if ($error !== ""): ?>
            <p class="error" role="alert"><?= escape($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                maxlength="50" autocomplete="username" required
                value="<?= escape($username) ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                autocomplete="current-password" required>

            <button type="submit">Log In</button>
        </form>
    </main>
</body>

</html>