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

$stmt = $conn->prepare("SELECT * FROM residents ORDER BY id DESC");
$stmt->execute();
$residents = $stmt->get_result();

$messages = [
    "added" => "Resident added successfully!",
    "updated" => "Resident details updated successfully!",
    "deleted" => "Resident deleted successfully!",
    "not_found" => "The resident record no longer exists."
];

$status = $_GET["status"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents | Hostel Management</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .row-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .row-actions form {
            margin: 0;
        }

        .row-actions .button {
            padding: 8px 12px;
            font-size: 14px;
        }

        .button-danger {
            background: #b91c1c;
        }

        .button-danger:hover {
            background: #991b1b;
        }
    </style>
</head>

<body>
    <nav aria-label="Main navigation">
        <strong>Hostel Management</strong>
        <a href="dashboard.php">Dashboard</a>
        <a href="residents.php" aria-current="page">Residents</a>
        <a href="users.php">Admins</a>

        <form action="logout.php" method="POST">
            <input type="hidden" name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">
            <button type="submit">Log Out</button>
        </form>
    </nav>

    <main>
        <div class="page-heading">
            <h1>Resident List</h1>
            <a class="button" href="add_resident.php">+ Add Resident</a>
        </div>

        <?php if (is_string($status) && isset($messages[$status])): ?>
            <p class="success" role="status">
                <?= escape($messages[$status]) ?>
            </p>
        <?php endif; ?>

        <div class="panel table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Student Name</th>
                        <th>Matric Number</th>
                        <th>Phone Number</th>
                        <th>Block</th>
                        <th>Room Number</th>
                        <th>Check-in Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($residents->num_rows === 0): ?>
                        <tr>
                            <td colspan="8">No residents found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $number = 1; ?>

                        <?php while ($row = $residents->fetch_assoc()): ?>
                            <tr>
                                <td><?= $number++ ?></td>
                                <td><?= escape($row["name"]) ?></td>
                                <td><?= escape($row["matric_no"]) ?></td>
                                <td><?= escape($row["phone"]) ?></td>
                                <td><?= escape($row["block"]) ?></td>
                                <td><?= escape($row["room_no"]) ?></td>
                                <td><?= escape($row["check_in_date"]) ?></td>

                                <td>
                                    <div class="row-actions">
                                        <a class="button"
                                            href="edit_resident.php?id=<?= (int) $row["id"] ?>">
                                            Edit
                                        </a>

                                        <form action="delete_resident.php"
                                            method="POST"
                                            onsubmit="return confirm('Delete this resident record? This cannot be undone.');">

                                            <input type="hidden" name="id"
                                                value="<?= (int) $row["id"] ?>">

                                            <input type="hidden" name="csrf_token"
                                                value="<?= escape($_SESSION["csrf_token"]) ?>">

                                            <button type="submit"
                                                class="button button-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>