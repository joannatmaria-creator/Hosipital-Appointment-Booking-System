
<?php
session_start();
require_once "../config/database.php";

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Only logged-in patients can book appointments.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "patient"
) {
    header("Location: ../login.php");
    exit;
}

$patientId = (int) $_SESSION["user_id"];

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Get the selected doctor's ID.
$doctorId = $_POST["doctor_id"]
    ?? $_GET["doctor_id"]
    ?? $_GET["id"]
    ?? "";

$doctor = null;
$error = "";
$success = false;

// Preserve entered form values after validation errors.
$appointmentDate = trim($_POST["appointment_date"] ?? "");
$appointmentTime = trim($_POST["appointment_time"] ?? "");
$reason = trim($_POST["reason"] ?? "");

$allowedTimes = [
    "09:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00"
];

// Validate doctor ID.
if (
    !is_scalar($doctorId) ||
    !ctype_digit((string)$doctorId) ||
    (int)$doctorId < 1
) {
    $error = "Invalid doctor selection. Please select a doctor first.";
} else {
    $doctorId = (int)$doctorId;

    // Load the selected active doctor.
    try {
        $stmt = $conn->prepare(
            "SELECT d.doctor_id, u.full_name, d.specialization,
                    d.department
             FROM doctors d
             INNER JOIN users u ON d.user_id = u.user_id
             WHERE d.doctor_id = ?
               AND d.status = 'Active'
               AND u.role = 'doctor'"
        );

        $stmt->bind_param("i", $doctorId);
        $stmt->execute();

        $doctor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$doctor) {
            $error = "The selected doctor is unavailable. Please choose another doctor.";
        }
    } catch (Throwable $ex) {
        error_log("Booking doctor lookup: " . $ex->getMessage());
        $error = "Unable to load the selected doctor. Please try again.";
    }
}

// Process the appointment form.
if ($_SERVER["REQUEST_METHOD"] === "POST" && $doctor) {

    // Verify CSRF token.
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        $error = "Invalid form submission. Please refresh the page and try again.";
    }

    // Validate appointment date.
    if ($error === "") {
        $dateObject = DateTime::createFromFormat(
            "!Y-m-d",
            $appointmentDate
        );

        $dateErrors = DateTime::getLastErrors();

        $validDate = $dateObject !== false
            && (
                $dateErrors === false ||
                (
                    $dateErrors["warning_count"] === 0 &&
                    $dateErrors["error_count"] === 0
                )
            )
            && $dateObject->format("Y-m-d") === $appointmentDate;

        if (!$validDate) {
            $error = "Please enter a valid appointment date.";
        } elseif ($appointmentDate < date("Y-m-d")) {
            $error = "Appointment date cannot be in the past.";
        }
    }

    // Validate appointment time.
    if ($error === "") {
        if (!in_array($appointmentTime, $allowedTimes, true)) {
            $error = "Please select a valid appointment time.";
        }
    }

    // Validate reason for visit.
    if ($error === "") {
        if ($reason === "") {
            $error = "Please enter the reason for your visit.";
        } elseif (strlen($reason) > 5000) {
            $error = "The reason for your visit is too long.";
        }
    }

    // Save appointment to MySQL.
    if ($error === "") {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO appointments
                    (patient_id, doctor_id, appointment_date,
                     appointment_time, reason)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iisss",
                $patientId,
                $doctorId,
                $appointmentDate,
                $appointmentTime,
                $reason
            );

            $stmt->execute();
            $stmt->close();

            $success = true;

            // Prevent accidental form resubmission.
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            $appointmentDate = "";
            $appointmentTime = "";
            $reason = "";

        } catch (mysqli_sql_exception $ex) {
            error_log("Appointment booking: " . $ex->getMessage());

            if ($ex->getCode() === 1062) {
                $error = "This time slot is already booked. Please select another time.";
            } else {
                $error = "Unable to book the appointment. Please try again.";
            }
        } catch (Throwable $ex) {
            error_log("Appointment booking: " . $ex->getMessage());
            $error = "An unexpected error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Book Appointment - CityCare Hospital</title>

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

        /* Booking Container */
        .booking-container {
            width: 80%;
            max-width: 800px;
            margin: 45px auto;
        }

        .booking-box {
            background-color: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .booking-box h1 {
            text-align: center;
            color: #063b63;
            margin-bottom: 10px;
        }

        .booking-box .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        /* Doctor */
        .selected-doctor {
            background-color: #eaf6fc;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .selected-doctor h3 {
            color: #063b63;
            margin-bottom: 6px;
        }

        .selected-doctor p {
            color: #555;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #063b63;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background-color: #063b63;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 5px;
        }

        .submit-btn:hover {
            background-color: #075487;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #063b63;
            text-decoration: none;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message h3 {
            margin-bottom: 8px;
        }

        .appointments-btn {
            display: inline-block;
            margin-top: 15px;
            background-color: #063b63;
            color: white;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 5px;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .booking-container {
                width: 92%;
            }

            .nav-links {
                gap: 10px;
                flex-wrap: wrap;
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

    <!-- Booking -->
    <div class="booking-container">

        <div class="booking-box">

            <h1>Book an Appointment</h1>

            <p class="subtitle">
                Schedule your visit with our doctor
            </p>

            <?php if ($error !== ""): ?>
                <div class="error-message" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>

                <div class="success-message" role="status">
                    <h3>Appointment Booked Successfully!</h3>
                    <p>
                        Your appointment has been saved.
                        Its current status is Pending.
                    </p>

                    <a href="my_appointments.php"
                       class="appointments-btn">
                        View My Appointments
                    </a>
                </div>

            <?php endif; ?>

            <?php if ($doctor && !$success): ?>

                <!-- Selected Doctor -->
                <div class="selected-doctor">

                    <h3>Selected Doctor</h3>

                    <p>
                        <strong><?= e($doctor["full_name"]) ?></strong>
                        &mdash;
                        <?= e($doctor["specialization"]) ?>
                    </p>

                    <p>
                        Department: <?= e($doctor["department"]) ?>
                    </p>

                </div>

                <!-- Appointment Form -->
                <form action="" method="POST">

                    <input type="hidden" name="doctor_id"
                           value="<?= (int)$doctor["doctor_id"] ?>">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">

                    <div class="form-row">

                        <div class="form-group">

                            <label for="date">
                                Appointment Date
                            </label>

                            <input
                                type="date"
                                id="date"
                                name="appointment_date"
                                min="<?= e(date('Y-m-d')) ?>"
                                value="<?= e($appointmentDate) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="time">
                                Appointment Time
                            </label>

                            <select id="time"
                                    name="appointment_time"
                                    required>

                                <option value="">
                                    Select Time
                                </option>

                                <option value="09:00"
                                    <?= $appointmentTime === "09:00" ? "selected" : "" ?>>
                                    09:00 AM
                                </option>

                                <option value="10:00"
                                    <?= $appointmentTime === "10:00" ? "selected" : "" ?>>
                                    10:00 AM
                                </option>

                                <option value="11:00"
                                    <?= $appointmentTime === "11:00" ? "selected" : "" ?>>
                                    11:00 AM
                                </option>

                                <option value="12:00"
                                    <?= $appointmentTime === "12:00" ? "selected" : "" ?>>
                                    12:00 PM
                                </option>

                                <option value="13:00"
                                    <?= $appointmentTime === "13:00" ? "selected" : "" ?>>
                                    01:00 PM
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="reason">
                            Reason for Visit
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            placeholder="Briefly describe your reason for visiting..."
                            required
                        ><?= e($reason) ?></textarea>

                    </div>

                    <button type="submit" class="submit-btn">
                        Confirm Appointment
                    </button>

                </form>

            <?php elseif (!$doctor): ?>

                <a href="doctors.php" class="back">
                    &larr; Back to Doctors
                </a>

            <?php endif; ?>

            <?php if ($doctor): ?>
                <a href="doctors.php" class="back">
                    &larr; Back to Doctors
                </a>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>