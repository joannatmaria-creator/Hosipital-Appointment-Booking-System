
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'patient'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$patientId = (int) $_SESSION['user_id'];
$patientName = htmlspecialchars(
    $_SESSION['full_name'] ?? 'Patient',
    ENT_QUOTES,
    'UTF-8'
);

$upcomingAppointment = null;

$sql = "SELECT
            a.appointment_date,
            a.appointment_time,
            a.status,
            u.full_name AS doctor_name,
            d.department
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.patient_id = ?
          AND a.status IN ('Pending', 'Confirmed')
          AND (
              a.appointment_date > CURDATE()
              OR (
                  a.appointment_date = CURDATE()
                  AND a.appointment_time >= CURTIME()
              )
          )
        ORDER BY a.appointment_date ASC,
                 a.appointment_time ASC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patientId);
$stmt->execute();

$result = $stmt->get_result();
$upcomingAppointment = $result->fetch_assoc();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Patient Dashboard - CityCare Hospital</title>

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

        /* Welcome */
        .welcome {
            background: white;
            padding: 30px 7%;
            border-bottom: 1px solid #ddd;
        }

        .welcome h1 {
            color: #063b63;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #666;
        }

        /* Dashboard */
        .dashboard {
            width: 86%;
            margin: 35px auto;
        }

        .dashboard h2 {
            color: #063b63;
            margin-bottom: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .card h3 {
            color: #063b63;
            margin-bottom: 10px;
        }

        .card p {
            color: #666;
            line-height: 1.5;
        }

        .card a {
            display: inline-block;
            margin-top: 18px;
            background-color: #063b63;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 5px;
        }

        /* Appointment */
        .appointment {
            background: white;
            margin-top: 30px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .appointment h3 {
            color: #063b63;
            margin-bottom: 15px;
        }

        .appointment p {
            color: #666;
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 800px) {
            .cards {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 10px;
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

    <!-- Welcome -->
    <div class="welcome">

        <h1>Welcome, <?= $patientName ?>!</h1>

        <p>
            Manage your appointments and find the right doctor
            for your healthcare needs.
        </p>

    </div>

    <!-- Dashboard -->
    <div class="dashboard">

        <h2>Patient Dashboard</h2>

        <div class="cards">

            <div class="card">

                <h3>Find a Doctor</h3>

                <p>
                    Browse available doctors and view their
                    departments and specializations.
                </p>

                <a href="doctors.php">View Doctors</a>

            </div>

            <div class="card">

                <h3>Book Appointment</h3>

                <p>
                    Choose a doctor and book an appointment
                    according to your healthcare needs.
                </p>

                <a href="doctors.php">Book Now</a>

            </div>

            <div class="card">

                <h3>My Appointments</h3>

                <p>
                    View your upcoming and previous appointments.
                </p>

                <a href="my_appointments.php">View Appointments</a>

            </div>

        </div>

        <!-- Appointment Section -->
        <div class="appointment">

            <h3>Upcoming Appointment</h3>

            <?php if ($upcomingAppointment): ?>

                <p>
                    <strong>Doctor:</strong>
                    <?= htmlspecialchars(
                        $upcomingAppointment['doctor_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    <strong>Department:</strong>
                    <?= htmlspecialchars(
                        $upcomingAppointment['department'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= date(
                        'd-m-Y',
                        strtotime($upcomingAppointment['appointment_date'])
                    ) ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?= date(
                        'h:i A',
                        strtotime($upcomingAppointment['appointment_time'])
                    ) ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars(
                        $upcomingAppointment['status'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            <?php else: ?>

                <p><strong>Doctor:</strong> Not booked</p>
                <p><strong>Department:</strong> Not available</p>
                <p><strong>Date:</strong> Not booked yet</p>
                <p><strong>Status:</strong> No upcoming appointment</p>

            <?php endif; ?>

        </div>

    </div>

</body>
</html>