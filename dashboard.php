<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "db_connect.php";

$totalResidents = $conn->query(
    "SELECT COUNT(*) AS total FROM residents"
)->fetch_assoc()["total"];

$totalUsers = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
)->fetch_assoc()["total"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ms">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Hostel Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f6;
            font-family: Arial, sans-serif;
            color: #183153;
        }

        nav {
            background: #183153;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        nav strong {
            margin-right: auto;
        }

        nav a {
            color: white;
            text-decoration: none;
        }

        nav form {
            margin: 0;
        }

        nav button {
            padding: 9px 16px;
            background: #fff;
            color: #183153;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        main {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px #1831530d;
        }

        .card h2 {
            font-size: 18px;
            color: #64748b;
        }

        .number {
            font-size: 40px;
            font-weight: bold;
            margin: 15px 0 0;
        }
    </style>
</head>

<body>
    <nav aria-label="Menu utama">
        <strong>Hostel Management</strong>
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="residents.php">residents</a>
        <form action="logout.php" method="POST">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars(
                            $_SESSION["csrf_token"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>">
            <button type="submit">Log </button>
        </form>
    </nav>

    <main>
        <h1>Dashboard</h1>

        <p>
            Selamat datang,
            <?= htmlspecialchars($_SESSION["name"] ?? "Admin", ENT_QUOTES, "UTF-8") ?>!
        </p>

        <div class="cards">
            <section class="card">
                <h2>Total residents</h2>
                <p class="number"><?= (int) $totalResidents ?></p>
            </section>

            <section class="card">
                <h2>Total admin</h2>
                <p class="number"><?= (int) $totalUsers ?></p>
            </section>
        </div>
    </main>
</body>

</html>