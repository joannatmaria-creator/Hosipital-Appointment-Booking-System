
<?php
require_once "../config/database.php";

// Validate doctor ID
$doctor_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$doctor = null;
$error = "";

if (!$doctor_id || $doctor_id <= 0) {
    $error = "Invalid doctor ID.";
} else {
    try {
        $sql = "SELECT
                    d.doctor_id,
                    d.department,
                    d.qualification,
                    d.specialization,
                    d.experience,
                    d.status,
                    u.full_name
                FROM doctors d
                INNER JOIN users u
                    ON d.user_id = u.user_id
                WHERE d.doctor_id = ?
                  AND d.status = 'Active'
                  AND u.role = 'doctor'";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Unable to prepare doctor query.");
        }

        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();

        $stmt->close();

        if (!$doctor) {
            $error = "Doctor not found or currently unavailable.";
        }
    } catch (Throwable $e) {
        error_log("Doctor details error: " . $e->getMessage());
        $error = "Unable to load doctor details. Please try again later.";
    }
}

// Escape output
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Doctor Details | CityCare Hospital</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }

        header {
            background: #0f2d52;
            color: white;
            padding: 20px 8%;
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
            font-weight: bold;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }

        .doctor-card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .doctor-card h1 {
            color: #0f2d52;
            margin-bottom: 10px;
        }

        .specialization {
            color: #2563eb;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .details {
            margin-top: 20px;
        }

        .detail-row {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
        }

        .detail-row strong {
            width: 160px;
            color: #334155;
        }

        .detail-row span {
            color: #475569;
        }

        .availability {
            margin-top: 25px;
            padding: 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 6px;
        }

        .book-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 13px 25px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .book-btn:hover {
            background: #1d4ed8;
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            .container {
                margin: 25px auto;
                padding: 15px;
            }

            .doctor-card {
                padding: 22px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>CityCare Hospital</h2>
    <a href="doctors.php">Back to Doctors</a>
</header>

<div class="container">

    <?php if ($error !== ""): ?>

        <div class="error-box">
            <h2>Doctor Details</h2>
            <p><?= e($error) ?></p>
            <a class="back-link" href="doctors.php">
                Back to Doctors
            </a>
        </div>

    <?php else: ?>

        <div class="doctor-card">
            <h1><?= e($doctor["full_name"]) ?></h1>

            <p class="specialization">
                <?= e($doctor["specialization"]) ?>
            </p>

            <div class="details">
                <div class="detail-row">
                    <strong>Department:</strong>
                    <span><?= e($doctor["department"]) ?></span>
                </div>

                <div class="detail-row">
                    <strong>Qualification:</strong>
                    <span><?= e($doctor["qualification"]) ?></span>
                </div>

                <div class="detail-row">
                    <strong>Experience:</strong>
                    <span><?= e($doctor["experience"]) ?> years</span>
                </div>

                <div class="detail-row">
                    <strong>Status:</strong>
                    <span><?= e($doctor["status"]) ?></span>
                </div>
            </div>

            <div class="availability">
                Available for appointment booking
            </div>

            <a class="book-btn"
               href="book_appointment.php?doctor_id=<?= (int)$doctor["doctor_id"] ?>">
                Book Appointment
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>