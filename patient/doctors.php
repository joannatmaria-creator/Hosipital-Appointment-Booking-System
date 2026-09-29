
<?php
require_once "../config/database.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$doctors = [];
$error = "";

try {
    $sql = "SELECT
                d.doctor_id,
                u.full_name,
                d.department,
                d.qualification,
                d.specialization,
                d.experience
            FROM doctors d
            INNER JOIN users u ON d.user_id = u.user_id
            WHERE d.status = 'Active'
              AND u.role = 'doctor'
            ORDER BY u.full_name ASC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
} catch (mysqli_sql_exception $ex) {
    error_log("Patient doctors page: " . $ex->getMessage());
    $error = "Unable to load doctors. Please try again later.";
}

$departmentIcons = [
    "General Medicine" => "🩺",
    "Cardiology" => "❤️",
    "Dermatology" => "🧴",
    "Orthopedics" => "🦴",
    "Pediatrics" => "🧸",
    "Gynecology" => "🌸"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors | CityCare Hospital</title>

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

        nav {
            display: flex;
            gap: 22px;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            color: #93c5fd;
        }

        .logout {
            background: #e74c3c;
            padding: 10px 16px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .page-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-heading h1 {
            color: #12345a;
            font-size: 30px;
            margin-bottom: 10px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 15px;
        }

        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .doctor-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .doctor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .doctor-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 16px;
            background: #e8f2fc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
        }

        .doctor-card h2 {
            color: #12345a;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .specialization {
            color: #2878c8;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .department {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .qualification {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .experience {
            color: #475569;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .view-btn {
            display: inline-block;
            background: #2878c8;
            color: white;
            padding: 11px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .view-btn:hover {
            background: #12345a;
        }

        .message {
            background: white;
            padding: 25px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            color: #64748b;
            line-height: 1.6;
        }

        .error {
            color: #b91c1c;
            border: 1px solid #fca5a5;
            background: #fee2e2;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            margin-top: 30px;
        }

        @media (max-width: 850px) {
            .doctor-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            header {
                padding: 18px;
                flex-wrap: wrap;
                gap: 15px;
            }

            nav {
                gap: 12px;
                flex-wrap: wrap;
            }

            .doctor-grid {
                grid-template-columns: 1fr;
            }

            .page-heading h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>CityCare Hospital</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="doctors.php">Doctors</a>
        <a href="my_appointments.php">My Appointments</a>
        <a href="../logout.php" class="logout">Logout</a>
    </nav>
</header>

<main class="container">

    <div class="page-heading">
        <h1>Our Doctors</h1>
        <p>Meet our experienced doctors and choose the right specialist for your needs.</p>
    </div>

    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= e($error) ?>
        </div>

    <?php elseif (count($doctors) === 0): ?>

        <div class="message">
            No doctors are currently available.
            Please check again later.
        </div>

    <?php else: ?>

        <div class="doctor-grid">

            <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card">

                    <div class="doctor-icon">
                        <?= $departmentIcons[$doctor["department"]] ?? "🩺" ?>
                    </div>

                    <h2><?= e($doctor["full_name"]) ?></h2>

                    <p class="specialization">
                        <?= e($doctor["specialization"]) ?>
                    </p>

                    <p class="department">
                        <?= e($doctor["department"]) ?>
                    </p>

                    <p class="qualification">
                        <?= e($doctor["qualification"]) ?>
                    </p>

                    <p class="experience">
                        <?= (int)$doctor["experience"] ?> years of experience
                    </p>

                    <a class="view-btn"
                       href="doctor_details.php?id=<?= (int)$doctor["doctor_id"] ?>">
                        View Details
                    </a>

                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>