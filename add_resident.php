<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "db_connect.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$data = [
    "name" => "",
    "matric_no" => "",
    "phone" => "",
    "block" => "",
    "room_no" => "",
    "check_in_date" => ""
];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid request. Please reload the page and try again.");
    }

    foreach ($data as $field => $value) {
        $input = $_POST[$field] ?? "";
        $data[$field] = is_string($input) ? trim($input) : "";
    }

    $date = DateTime::createFromFormat(
        "!Y-m-d",
        $data["check_in_date"]
    );

    if (in_array("", $data, true)) {
        $error = "Please complete all fields.";
    } elseif (
        strlen($data["name"]) > 100 ||
        strlen($data["matric_no"]) > 30 ||
        strlen($data["phone"]) > 20 ||
        strlen($data["block"]) > 20 ||
        strlen($data["room_no"]) > 20
    ) {
        $error = "One or more fields exceed the maximum length.";
    } elseif (
        !$date ||
        $date->format("Y-m-d") !== $data["check_in_date"] ||
        $data["check_in_date"] < "1000-01-01"
    ) {
        $error = "Please enter a valid check-in date.";
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO residents
                (name, matric_no, phone, block, room_no, check_in_date)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $data["name"],
                $data["matric_no"],
                $data["phone"],
                $data["block"],
                $data["room_no"],
                $data["check_in_date"]
            );

            $stmt->execute();
            $stmt->close();

            header("Location: residents.php?status=added");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $error = "This matric number is already registered.";
            } else {
                error_log($e->getMessage());
                $error = "Unable to save the resident. Please try again.";
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
    <title>Add Resident | Hostel Management</title>
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
        <h1>Add Resident</h1>

        <?php if ($error !== ""): ?>
            <p class="error" role="alert"><?= escape($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="panel resident-form">
            <input type="hidden" name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">

            <label for="name">Student Name</label>
            <input type="text" id="name" name="name"
                maxlength="100" required
                value="<?= escape($data["name"]) ?>">

            <label for="matric_no">Matric Number</label>
            <input type="text" id="matric_no" name="matric_no"
                maxlength="30" required
                value="<?= escape($data["matric_no"]) ?>">

            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone"
                maxlength="20" required
                value="<?= escape($data["phone"]) ?>">

            <label for="block">Block</label>
            <input type="text" id="block" name="block"
                placeholder="Example: A" maxlength="20" required
                value="<?= escape($data["block"]) ?>">

            <label for="room_no">Room Number</label>
            <input type="text" id="room_no" name="room_no"
                placeholder="Example: A-101" maxlength="20" required
                value="<?= escape($data["room_no"]) ?>">

            <label for="check_in_date">Check-in Date</label>
            <input type="date" id="check_in_date" name="check_in_date"
                min="1000-01-01" max="9999-12-31" required
                value="<?= escape($data["check_in_date"]) ?>">

            <div class="form-actions">
                <button type="submit" class="button">Save Resident</button>
                <a href="residents.php" class="cancel-link">Cancel</a>
            </div>
        </form>
    </main>
</body>

</html>