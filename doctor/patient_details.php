
<?php
session_start();

require_once "../config/database.php";

// Allow only logged-in doctors.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'doctor'
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

$appointmentId = filter_input(
    INPUT_GET,
    'appointment_id',
    FILTER_VALIDATE_INT
);

if (!$appointmentId || $appointmentId < 1) {
    http_response_code(400);
    exit("Invalid appointment ID.");
}

// Escape output.
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

try {
    // Fetch the appointment only if it belongs to this doctor.
    $stmt = $conn->prepare(
        "SELECT
            a.appointment_id,
            a.patient_id,
            p.full_name,
            p.phone,
            p.email,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            d.department
         FROM appointments a
         INNER JOIN users p
            ON a.patient_id = p.user_id
         INNER JOIN doctors d
            ON a.doctor_id = d.doctor_id
         WHERE a.appointment_id = ?
           AND d.user_id = ?
           AND d.status = 'Active'
           AND p.role = 'patient'"
    );

    $stmt->bind_param("ii", $appointmentId, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    $stmt->close();

    if (!$patient) {
        http_response_code(404);
        exit("Appointment not found or access denied.");
    }

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Unable to load patient details.");
}

$appointmentDate = date(
    'd M Y',
    strtotime($patient['appointment_date'])
);

$appointmentTime = date(
    'h:i A',
    strtotime($patient['appointment_time'])
);

$statusClass = strtolower($patient['status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details | CityCare Hospital</title>

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
            background: #e74c3c;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .container {
            max-width: 900px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            margin-bottom: 22px;
            color: #2878c8;
            text-decoration: none;
            font-weight: bold;
        }

        h1 {
            color: #12345a;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .card h2 {
            color: #12345a;
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5eaf1;
            padding-bottom: 12px;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .detail label {
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 7px;
        }

        .detail p {
            font-size: 15px;
            font-weight: bold;
            color: #263449;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .pending {
            background: #fff0c2;
            color: #966000;
        }

        .confirmed {
            background: #d9f7e4;
            color: #16733d;
        }

        .completed {
            background: #dceaff;
            color: #2055a0;
        }

        .rejected {
            background: #ffe0e0;
            color: #b42318;
        }

        .cancelled {
            background: #e5e7eb;
            color: #475569;
        }

        .button {
            display: inline-block;
            padding: 11px 20px;
            background: #2878c8;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 5px;
        }

        .button:hover {
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

            .details {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .card {
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

<div class="container">

    <a href="appointments.php" class="back">
        &larr; Back to Appointments
    </a>

    <h1>Patient Details</h1>
    <p class="subtitle">
        View the patient's information and appointment.
    </p>

    <section class="card">
        <h2>Personal Information</h2>

        <div class="details">
            <div class="detail">
                <label>Patient ID</label>
                <p><?= (int)$patient['patient_id'] ?></p>
            </div>

            <div class="detail">
                <label>Full Name</label>
                <p><?= e($patient['full_name']) ?></p>
            </div>

            <div class="detail">
                <label>Age</label>
                <p>Not provided</p>
            </div>

            <div class="detail">
                <label>Gender</label>
                <p>Not provided</p>
            </div>

            <div class="detail">
                <label>Phone Number</label>
                <p><?= e($patient['phone'] ?? 'Not provided') ?></p>
            </div>

            <div class="detail">
                <label>Email Address</label>
                <p><?= e($patient['email'] ?? 'Not provided') ?></p>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Appointment Information</h2>

        <div class="details">
            <div class="detail">
                <label>Appointment ID</label>
                <p><?= (int)$patient['appointment_id'] ?></p>
            </div>

            <div class="detail">
                <label>Department</label>
                <p><?= e($patient['department']) ?></p>
            </div>

            <div class="detail">
                <label>Appointment Date</label>
                <p><?= e($appointmentDate) ?></p>
            </div>

            <div class="detail">
                <label>Appointment Time</label>
                <p><?= e($appointmentTime) ?></p>
            </div>

            <div class="detail">
                <label>Reason for Visit</label>
                <p><?= e($patient['reason'] ?? 'Not provided') ?></p>
            </div>

            <div class="detail">
                <label>Appointment Status</label>
                <p>
                    <span class="status <?= e($statusClass) ?>">
                        <?= e($patient['status']) ?>
                    </span>
                </p>
            </div>
        </div>
    </section>

    <a href="appointments.php" class="button">
        Back to Appointments
    </a>

</div>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>