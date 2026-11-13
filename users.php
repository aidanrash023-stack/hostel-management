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

// Verify that the logged-in admin account still exists.
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

// Retrieve admin accounts without exposing passwords.
$stmt = $conn->prepare(
    "SELECT id, name, username FROM users ORDER BY id DESC"
);
$stmt->execute();
$users = $stmt->get_result();

$messages = [
    "added" => "Admin account added successfully!",
    "updated" => "Admin account updated successfully!",
    "deleted" => "Admin account deleted successfully!",
    "not_found" => "The admin account no longer exists.",
    "self_delete" => "You cannot delete your own account.",
    "delete_failed" => "Unable to delete the account. Please try again."
];

$status = $_GET["status"] ?? "";

$messageClass = in_array(
    $status,
    ["not_found", "self_delete", "delete_failed"],
    true
) ? "error" : "success";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admins | Hostel Management</title>

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

        .account-note {
            color: #64748b;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <nav aria-label="Main navigation">
        <strong>Hostel Management</strong>

        <a href="dashboard.php">Dashboard</a>
        <a href="residents.php">Residents</a>
        <a href="users.php" aria-current="page">Admins</a>

        <form action="logout.php" method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escape($_SESSION["csrf_token"]) ?>">

            <button type="submit">Log Out</button>
        </form>
    </nav>

    <main>
        <div class="page-heading">
            <h1>Admin List</h1>

            <a href="add_user.php" class="button">
                + Add Admin
            </a>
        </div>

        <?php if (is_string($status) && isset($messages[$status])): ?>
            <p class="<?= escape($messageClass) ?>" role="status">
                <?= escape($messages[$status]) ?>
            </p>
        <?php endif; ?>

        <div class="panel table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Admin Name</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($users->num_rows === 0): ?>
                        <tr>
                            <td colspan="4">
                                No admin accounts found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $number = 1; ?>

                        <?php while ($row = $users->fetch_assoc()): ?>
                            <?php
                            $isCurrentUser =
                                (int) $row["id"] ===
                                (int) $_SESSION["user_id"];
                            ?>

                            <tr>
                                <td><?= $number++ ?></td>

                                <td>
                                    <?= escape($row["name"]) ?>

                                    <?php if ($isCurrentUser): ?>
                                        <small class="account-note">
                                            (You)
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= escape($row["username"]) ?>
                                </td>

                                <td>
                                    <div class="row-actions">
                                        <a
                                            class="button"
                                            href="edit_user.php?id=<?= (int) $row["id"] ?>">
                                            Edit
                                        </a>

                                        <?php if (!$isCurrentUser): ?>
                                            <form
                                                action="delete_user.php"
                                                method="POST"
                                                onsubmit="return confirm('Delete this admin account? This cannot be undone.');">
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $row["id"] ?>">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escape($_SESSION["csrf_token"]) ?>">

                                                <button
                                                    type="submit"
                                                    class="button button-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="account-note">
                                                Current account
                                            </span>
                                        <?php endif; ?>
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