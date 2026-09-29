
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'doctor'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

// Get the logged-in doctor's details.
$stmt = $conn->prepare(
    "SELECT department
     FROM doctors
     WHERE user_id = ? AND status = 'Active'"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    http_response_code(403);
    exit("Doctor profile not found or inactive.");
}

$doctorName = htmlspecialchars(
    $_SESSION['full_name'] ?? 'Doctor',
    ENT_QUOTES,
    'UTF-8'
);

$department = htmlspecialchars(
    $doctor['department'],
    ENT_QUOTES,
    'UTF-8'
);

// Get appointment counts for this doctor.
$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
        COALESCE(SUM(status = 'Completed'), 0) AS completed
     FROM appointments
     WHERE doctor_id = (
         SELECT doctor_id
         FROM doctors
         WHERE user_id = ?
     )"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | CityCare Hospital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f6fb;
            color: #263449;
        }

        header {
            background: #12345a;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h2 {
            font-size: 24px;
        }

        header a {
            color: white;
            text-decoration: none;
            background: #e74c3c;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            color: #12345a;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #64748b;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            border-left: 5px solid #2878c8;
        }

        .card h3 {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 30px;
            font-weight: bold;
            color: #12345a;
        }

        .actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .actions h2 {
            color: #12345a;
            margin-bottom: 20px;
        }

        .action-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .action-links a {
            display: inline-block;
            background: #2878c8;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
        }

        .action-links a:hover {
            background: #12345a;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            margin-top: 30px;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px;
            }
            .container {
                margin-top: 25px;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>CityCare Hospital</h2>
    <a href="../logout.php">Logout</a>
</header>

<div class="container">

    <section class="welcome">
        <h1>Welcome, <?= $doctorName ?>!</h1>
        <p>Doctor Dashboard | <?= $department ?></p>
    </section>

    <section class="cards">
        <div class="card">
            <h3>Total Appointments</h3>
            <p><?= (int) $counts['total'] ?></p>
        </div>

        <div class="card">
            <h3>Pending Appointments</h3>
            <p><?= (int) $counts['pending'] ?></p>
        </div>

        <div class="card">
            <h3>Confirmed Appointments</h3>
            <p><?= (int) $counts['confirmed'] ?></p>
        </div>

        <div class="card">
            <h3>Completed Appointments</h3>
            <p><?= (int) $counts['completed'] ?></p>
        </div>
    </section>

    <section class="actions">
        <h2>Manage Appointments</h2>
        <div class="action-links">
            <a href="appointments.php">View Appointments</a>
        </div>
    </section>

</div>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>