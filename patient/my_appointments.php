
<?php
session_start();
require_once "../config/database.php";

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Allow only logged-in patients.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "patient"
) {
    header("Location: ../login.php");
    exit;
}

$patientId = (int) $_SESSION["user_id"];
$appointments = [];
$error = "";

try {
    $sql = "SELECT
                a.appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                d.department,
                u.full_name
            FROM appointments a
            INNER JOIN doctors d
                ON a.doctor_id = d.doctor_id
            INNER JOIN users u
                ON d.user_id = u.user_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC,
                     a.appointment_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patientId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();

} catch (Throwable $ex) {
    error_log("My appointments error: " . $ex->getMessage());
    $error = "Unable to load your appointments. Please try again later.";
}

// Map appointment statuses to CSS classes.
function statusClass($status) {
    switch ($status) {
        case "Confirmed":
            return "confirmed";
        case "Pending":
            return "pending";
        case "Completed":
            return "completed";
        case "Rejected":
            return "rejected";
        case "Cancelled":
            return "cancelled";
        default:
            return "pending";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Appointments - CityCare Hospital</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f4f7fa;
        }

        /* Navbar */
        .navbar {
            background-color: #063b63;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .logo span {
            color: #5cc8ff;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        .logout {
            background-color: #e74c3c;
            padding: 9px 16px;
            border-radius: 5px;
        }

        /* Page */
        .page {
            width: 86%;
            margin: 40px auto;
        }

        .page h1 {
            color: #063b63;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        /* Appointment Card */
        .appointment-card {
            background-color: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .appointment-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .appointment-top h2 {
            color: #063b63;
            font-size: 21px;
        }

        .status {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .confirmed {
            background-color: #d4edda;
            color: #217a35;
        }

        .pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .completed {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .cancelled {
            background-color: #e5e7eb;
            color: #374151;
        }

        .appointment-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .info h4 {
            color: #063b63;
            margin-bottom: 6px;
        }

        .info p {
            color: #666;
            overflow-wrap: anywhere;
        }

        .cancel-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 9px 16px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .cancel-btn:hover {
            background-color: #c0392b;
        }

        /* Empty Appointment */
        .empty {
            background-color: white;
            text-align: center;
            padding: 45px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .empty h2 {
            color: #063b63;
            margin-bottom: 10px;
        }

        .empty p {
            color: #666;
        }

        .book-btn {
            display: inline-block;
            margin-top: 18px;
            padding: 11px 20px;
            background-color: #063b63;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .appointment-info {
                grid-template-columns: 1fr;
            }

            .appointment-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .nav-links {
                gap: 10px;
                flex-wrap: wrap;
            }

            .page {
                width: 92%;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">

        <div class="logo">
            ♥ CityCare <span>Hospital</span>
        </div>

        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="doctors.php">Doctors</a>
            <a href="my_appointments.php">My Appointments</a>
            <a href="../logout.php" class="logout">Logout</a>
        </div>

    </div>

    <!-- Appointments -->
    <div class="page">

        <h1>My Appointments</h1>

        <p class="page-subtitle">
            View and manage your scheduled appointments.
        </p>

        <?php if ($error !== ""): ?>

            <div class="error-message" role="alert">
                <?= e($error) ?>
            </div>

        <?php elseif (empty($appointments)): ?>

            <div class="empty">
                <h2>No Appointments</h2>

                <p>You don't have any appointments yet.</p>

                <a href="doctors.php" class="book-btn">
                    Book an Appointment
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($appointments as $appointment): ?>

                <div class="appointment-card">

                    <div class="appointment-top">

                        <h2><?= e($appointment["full_name"]) ?></h2>

                        <span class="status <?= e(statusClass($appointment["status"])) ?>">
                            <?= e($appointment["status"]) ?>
                        </span>

                    </div>

                    <div class="appointment-info">

                        <div class="info">
                            <h4>Department</h4>
                            <p><?= e($appointment["department"]) ?></p>
                        </div>

                        <div class="info">
                            <h4>Date</h4>
                            <p>
                                <?php
                                $date = DateTime::createFromFormat(
                                    "!Y-m-d",
                                    $appointment["appointment_date"]
                                );
                                echo $date
                                    ? e($date->format("d F Y"))
                                    : e($appointment["appointment_date"]);
                                ?>
                            </p>
                        </div>

                        <div class="info">
                            <h4>Time</h4>
                            <p>
                                <?php
                                $time = DateTime::createFromFormat(
                                    "!H:i:s",
                                    $appointment["appointment_time"]
                                );

                                if (!$time) {
                                    $time = DateTime::createFromFormat(
                                        "!H:i",
                                        substr($appointment["appointment_time"], 0, 5)
                                    );
                                }

                                echo $time
                                    ? e($time->format("h:i A"))
                                    : e($appointment["appointment_time"]);
                                ?>
                            </p>
                        </div>

                    </div>

                    <?php if (
                        in_array(
                            $appointment["status"],
                            ["Pending", "Confirmed"],
                            true
                        )
                    ): ?>

                        <a
                            href="cancel_appointment.php?id=<?= (int)$appointment["appointment_id"] ?>"
                            class="cancel-btn"
                        >
                            Cancel Appointment
                        </a>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</body>
</html>