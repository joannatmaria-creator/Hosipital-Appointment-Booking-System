
<?php
$doctorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$doctorId || $doctorId < 1) {
    die("Invalid doctor ID. <a href='doctors.php'>Go back</a>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['confirm_delete'])) {
        // Frontend demonstration only.
        // Database deletion will be added later.
        echo "<script>
            alert('Doctor removed successfully!');
            window.location.href = 'dashboard.php';
        </script>";
        exit;
    }

    header("Location: doctors.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Doctor | CityCare Hospital</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #12345a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #12345a;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h2 {
            font-size: 24px;
        }

        header a {
            background: #2878c8;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }

        .container {
            width: 90%;
            max-width: 550px;
            margin: 70px auto;
            flex: 1;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .card h1 {
            font-size: 25px;
            margin-bottom: 20px;
        }

        .warning {
            background: #fff1f2;
            color: #be123c;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .doctor-info {
            background: #f3f6fa;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .cancel {
            background: #e2e8f0;
            color: #12345a;
        }

        .delete {
            background: #dc2626;
            color: white;
        }

        .delete:hover {
            background: #b91c1c;
        }

        .cancel:hover {
            background: #cbd5e1;
        }

        footer {
            text-align: center;
            padding: 25px 10px;
            color: #64748b;
        }

        @media (max-width: 500px) {
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>CityCare Hospital | Admin</h2>
    <a href="dashboard.php">Dashboard</a>
</header>

<main class="container">
    <div class="card">

        <h1>Remove Doctor</h1>

        <div class="warning">
            Are you sure you want to remove this doctor?
            Please confirm your action.
        </div>

        <div class="doctor-info">
            <strong>Doctor ID:</strong>
            <?php echo htmlspecialchars((string)$doctorId); ?>
        </div>

        <form method="POST" action="">
            <div class="buttons">

                <a href="doctors.php" class="btn cancel">
                    Cancel
                </a>

                <button
                    type="submit"
                    name="confirm_delete"
                    value="1"
                    class="btn delete"
                    onclick="return confirm('Are you sure you want to remove this doctor?');">
                    Remove Doctor
                </button>

            </div>
        </form>

    </div>
</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>