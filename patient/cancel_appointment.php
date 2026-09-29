
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

$patientId = (int)$_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$appointmentId = $_POST["appointment_id"]
    ?? $_GET["id"]
    ?? "";

$appointment = null;
$error = "";
$success = false;

// Validate appointment ID.
if (
    !is_scalar($appointmentId) ||
    !ctype_digit((string)$appointmentId) ||
    (int)$appointmentId < 1
) {
    $error = "Invalid appointment. Please select an appointment from My Appointments.";
} else {
    $appointmentId = (int)$appointmentId;

    // Fetch only an appointment belonging to this patient.
    try {
        $sql = "SELECT
                    a.appointment_id,
                    a.appointment_date,
                    a.appointment_time,
                    a.status,
                    d.department,
                    u.full_name
                FROM appointments a
                INNER JOIN doctors d
                    ON a.doctor_id = d.doctor_id
                INNER JOIN users u
                    ON d.user_id = u.user_id
                WHERE a.appointment_id = ?
                  AND a.patient_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointmentId, $patientId);
        $stmt->execute();

        $appointment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$appointment) {
            $error = "Appointment not found or you do not have permission to access it.";
        }
    } catch (Throwable $ex) {
        error_log("Cancellation lookup error: " . $ex->getMessage());
        $error = "Unable to load the appointment. Please try again later.";
    }
}

// Process cancellation only after the patient confirms.
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $appointment !== null &&
    $error === ""
) {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        $error = "Invalid form submission. Please refresh and try again.";
    } elseif (
        !in_array($appointment["status"], ["Pending", "Confirmed"], true)
    ) {
        $error = "This appointment cannot be cancelled because its status is "
            . $appointment["status"] . ".";
    } else {
        try {
            // Change the status only if the appointment is still cancellable.
            $stmt = $conn->prepare(
                "UPDATE appointments
                 SET status = 'Cancelled'
                 WHERE appointment_id = ?
                   AND patient_id = ?
                   AND status IN ('Pending', 'Confirmed')"
            );

            $stmt->bind_param("ii", $appointmentId, $patientId);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $success = true;
                $appointment["status"] = "Cancelled";

                // Prevent accidental reuse of the form token.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
            } else {
                $error = "The appointment could not be cancelled. It may already have been updated.";
            }

            $stmt->close();

        } catch (Throwable $ex) {
            error_log("Appointment cancellation error: " . $ex->getMessage());
            $error = "Unable to cancel the appointment. Please try again later.";
        }
    }
}

// Format date and time for display.
$displayDate = "";
$displayTime = "";

if ($appointment !== null) {
    $date = DateTime::createFromFormat(
        "!Y-m-d",
        $appointment["appointment_date"]
    );

    $displayDate = $date
        ? $date->format("d F Y")
        : $appointment["appointment_date"];

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

    $displayTime = $time
        ? $time->format("h:i A")
        : $appointment["appointment_time"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment | CityCare Hospital</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f7fb;
            color: #26384a;
        }

        nav {
            background: #12345a;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        nav h2 {
            font-size: 24px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 14px;
        }

        nav a:hover {
            color: #69d3ff;
        }

        .container {
            max-width: 650px;
            margin: 65px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .warning-icon {
            width: 65px;
            height: 65px;
            background: #fff0ed;
            color: #d94836;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        h1 {
            color: #12345a;
            font-size: 26px;
            margin-bottom: 12px;
        }

        .description {
            color: #687989;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .appointment-details {
            background: #f4f8fc;
            border: 1px solid #dce7f1;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
        }

        .appointment-details h3 {
            color: #12345a;
            font-size: 17px;
            margin-bottom: 15px;
        }

        .detail {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 13px;
            font-size: 14px;
        }

        .detail:last-child {
            margin-bottom: 0;
        }

        .detail span:first-child {
            color: #687989;
        }

        .detail span:last-child {
            color: #243447;
            font-weight: bold;
            text-align: right;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 22px;
            border-radius: 6px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
        }

        .cancel-btn:hover {
            background: #b92534;
        }

        .back-btn {
            background: #e4edf5;
            color: #12345a;
        }

        .back-btn:hover {
            background: #d1e0ed;
        }

        .note {
            margin-top: 20px;
            color: #7b8997;
            font-size: 12px;
        }

        .message {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        footer {
            background: #12345a;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }

        @media (max-width: 600px) {
            nav {
                flex-direction: column;
            }

            nav a {
                margin: 0 7px;
            }

            .card {
                padding: 25px 18px;
            }

            .detail {
                gap: 8px;
            }
        }
    </style>
</head>

<body>

<nav>
    <h2>CityCare Hospital</h2>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="doctors.php">Doctors</a>
        <a href="my_appointments.php">My Appointments</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="card">

        <div class="warning-icon">!</div>

        <h1>Cancel Appointment</h1>

        <?php if ($error !== ""): ?>

            <div class="message error-message" role="alert">
                <?= e($error) ?>
            </div>

            <div class="buttons">
                <a href="my_appointments.php" class="btn back-btn">
                    Back to My Appointments
                </a>
            </div>

        <?php elseif ($success): ?>

            <div class="message success-message" role="status">
                <strong>Appointment Cancelled Successfully!</strong>
                <p>Your appointment has been cancelled and the database has been updated.</p>
            </div>

            <div class="appointment-details">
                <h3>Cancelled Appointment</h3>

                <div class="detail">
                    <span>Doctor</span>
                    <span><?= e($appointment["full_name"]) ?></span>
                </div>

                <div class="detail">
                    <span>Department</span>
                    <span><?= e($appointment["department"]) ?></span>
                </div>

                <div class="detail">
                    <span>Date</span>
                    <span><?= e($displayDate) ?></span>
                </div>

                <div class="detail">
                    <span>Time</span>
                    <span><?= e($displayTime) ?></span>
                </div>

                <div class="detail">
                    <span>Status</span>
                    <span><?= e($appointment["status"]) ?></span>
                </div>
            </div>

            <div class="buttons">
                <a href="my_appointments.php" class="btn back-btn">
                    My Appointments
                </a>
            </div>

        <?php else: ?>

            <p class="description">
                Are you sure you want to cancel this appointment?
                Please review the appointment details before proceeding.
            </p>

            <div class="appointment-details">
                <h3>Appointment Details</h3>

                <div class="detail">
                    <span>Doctor</span>
                    <span><?= e($appointment["full_name"]) ?></span>
                </div>

                <div class="detail">
                    <span>Department</span>
                    <span><?= e($appointment["department"]) ?></span>
                </div>

                <div class="detail">
                    <span>Date</span>
                    <span><?= e($displayDate) ?></span>
                </div>

                <div class="detail">
                    <span>Time</span>
                    <span><?= e($displayTime) ?></span>
                </div>

                <div class="detail">
                    <span>Status</span>
                    <span><?= e($appointment["status"]) ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="appointment_id"
                       value="<?= (int)$appointment["appointment_id"] ?>">

                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION["csrf_token"]) ?>">

                <div class="buttons">
                    <a href="my_appointments.php" class="btn back-btn">
                        Go Back
                    </a>

                    <button type="submit" class="btn cancel-btn">
                        Confirm Cancellation
                    </button>
                </div>
            </form>

            <p class="note">
                Only pending and confirmed appointments can be cancelled.
            </p>

        <?php endif; ?>

    </div>
</div>

<footer>
    <p>&copy; 2026 CityCare Hospital. All rights reserved.</p>
</footer>

</body>
</html>