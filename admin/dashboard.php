
<?php
session_start();

// Allow only logged-in admins.
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Dynamic admin name.
$adminName = htmlspecialchars(
    $_SESSION['full_name'] ?? 'Admin',
    ENT_QUOTES,
    'UTF-8'
);

// Get doctor counts from MySQL.
$doctorSql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Active'), 0) AS active
    FROM doctors";

$doctorResult = $conn->query($doctorSql);
$doctorCounts = $doctorResult->fetch_assoc();

$totalDoctors = (int) $doctorCounts['total'];
$activeDoctors = (int) $doctorCounts['active'];
$inactiveDoctors = $totalDoctors - $activeDoctors;

// Get appointment counts from MySQL.
$appointmentSql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
    COALESCE(SUM(status = 'Completed'), 0) AS completed,
    COALESCE(SUM(status = 'Rejected'), 0) AS rejected
    FROM appointments";

$appointmentResult = $conn->query($appointmentSql);
$appointmentCounts = $appointmentResult->fetch_assoc();

$totalAppointments = (int) $appointmentCounts['total'];
$pendingAppointments = (int) $appointmentCounts['pending'];
$confirmedAppointments = (int) $appointmentCounts['confirmed'];
$completedAppointments = (int) $appointmentCounts['completed'];
$rejectedAppointments = (int) $appointmentCounts['rejected'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CityCare Hospital</title>

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
            background: #e74c3c;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        header a:hover {
            background: #c0392b;
        }

        .container {
            max-width: 1150px;
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

        .section-title {
            color: #12345a;
            font-size: 21px;
            margin-bottom: 18px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            background: white;
            padding: 23px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            border-left: 5px solid #2878c8;
        }

        .card h3 {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .card p {
            color: #12345a;
            font-size: 30px;
            font-weight: bold;
        }

        .card small {
            display: block;
            color: #64748b;
            margin-top: 8px;
        }

        .green {
            border-left-color: #198754;
        }

        .orange {
            border-left-color: #f0ad4e;
        }

        .red {
            border-left-color: #dc3545;
        }

        .management {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .management h2 {
            color: #12345a;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 15px;
        }

        .links a {
            display: block;
            background: #2878c8;
            color: white;
            text-align: center;
            text-decoration: none;
            padding: 15px;
            border-radius: 7px;
            font-size: 15px;
            transition: background 0.2s;
        }

        .links a:hover {
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

            .management {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>CityCare Hospital</h2>
    <a href="../logout.php">Logout</a>
</header>

<main class="container">

    <section class="welcome">
        <h1>Welcome, <?= $adminName ?>!</h1>
        <p>Manage doctors and monitor hospital appointments.</p>
    </section>

    <h2 class="section-title">Doctor Overview</h2>

    <section class="cards">
        <div class="card">
            <h3>Total Doctors</h3>
            <p><?= $totalDoctors ?></p>
        </div>

        <div class="card green">
            <h3>Active Doctors</h3>
            <p><?= $activeDoctors ?></p>
        </div>

        <div class="card orange">
            <h3>Inactive Doctors</h3>
            <p><?= $inactiveDoctors ?></p>
        </div>
    </section>

    <h2 class="section-title">Appointment Overview</h2>

    <section class="cards">
        <div class="card">
            <h3>Total Appointments</h3>
            <p><?= $totalAppointments ?></p>
        </div>

        <div class="card orange">
            <h3>Pending</h3>
            <p><?= $pendingAppointments ?></p>
        </div>

        <div class="card green">
            <h3>Confirmed</h3>
            <p><?= $confirmedAppointments ?></p>
        </div>

        <div class="card">
            <h3>Completed</h3>
            <p><?= $completedAppointments ?></p>
        </div>

        <div class="card red">
            <h3>Rejected</h3>
            <p><?= $rejectedAppointments ?></p>
        </div>
    </section>

    <section class="management">
        <h2>Doctor Management</h2>
        <div class="links">
            <a href="doctors.php">Manage Doctors</a>
        </div>
    </section>

    <section class="management">
        <h2>View All Appointments</h2>
        <div class="links">
            <a href="appointments.php">View All Appointments</a>
        </div>
    </section>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>