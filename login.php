
<?php
session_start();
require_once "config/database.php";

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password_hash, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user["password_hash"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                $stmt->close();
                $conn->close();

                if ($user["role"] === "admin") {
                    header("Location: admin/dashboard.php");
                } elseif ($user["role"] === "doctor") {
                    header("Location: doctor/dashboard.php");
                } else {
                    header("Location: patient/dashboard.php");
                }
                exit;
            }
        }

        $error = "Invalid email or password.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | CityCare Hospital</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #eaf5fc;
            color: #18324a;
        }

        /* ================= NAVBAR ================= */

        .navbar {
            height: 64px;
            background: #063b63;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 7%;
            color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;

            font-size: 18px;
            font-weight: bold;
        }

        .logo-icon {
            width: 32px;
            height: 32px;

            background: white;
            color: #0877c9;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }

        .back-home {
            color: white;
            text-decoration: none;
            font-size: 13px;
        }

        .back-home:hover {
            color: #66c9ff;
        }

        /* ================= LOGIN AREA ================= */

        .login-section {
            min-height: calc(100vh - 64px);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px 20px;

            background:
                linear-gradient(
                    135deg,
                    #eaf7ff,
                    #dceef9
                );
        }

        .login-container {
            width: 900px;
            min-height: 500px;

            background: white;

            border-radius: 12px;

            box-shadow:
                0 10px 35px rgba(0, 50, 80, 0.12);

            display: grid;
            grid-template-columns: 1fr 1fr;

            overflow: hidden;
        }

        /* ================= LEFT SIDE ================= */

        .login-image {
            background:
                linear-gradient(
                    rgba(5, 61, 96, 0.20),
                    rgba(5, 61, 96, 0.20)
                ),
                url("images/hospital.jpg");

            background-size: cover;
            background-position: center;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
        }

        .image-content {
            color: white;
            text-align: center;

            background: rgba(3, 52, 82, 0.65);

            padding: 30px;
            border-radius: 10px;
        }

        .image-content h2 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .image-content p {
            font-size: 14px;
            line-height: 1.6;
        }

        /* ================= FORM ================= */

        .login-form-area {
            padding: 55px 50px;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .heart-icon {
            width: 48px;
            height: 48px;

            background: #e7f4ff;
            color: #087fe0;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;

            margin-bottom: 18px;
        }

        .login-form-area h1 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #718394;
            font-size: 13px;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;

            font-size: 13px;
            font-weight: bold;

            margin-bottom: 7px;
        }

        .form-group input {
            width: 100%;

            padding: 12px 13px;

            border: 1px solid #d8e4ec;
            border-radius: 6px;

            outline: none;

            font-size: 13px;
        }

        .form-group input:focus {
            border-color: #1688e5;

            box-shadow:
                0 0 0 2px rgba(22, 136, 229, 0.10);
        }

        .forgot {
            display: block;

            text-align: right;

            margin-top: -8px;
            margin-bottom: 20px;

            color: #087fe0;

            font-size: 12px;

            text-decoration: none;
        }

        .login-button {
            width: 100%;

            border: none;

            background: #087fe0;
            color: white;

            padding: 13px;

            border-radius: 6px;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        .login-button:hover {
            background: #0568b9;
        }

        .register-text {
            text-align: center;

            margin-top: 22px;

            font-size: 13px;

            color: #718394;
        }

        .register-text a {
            color: #087fe0;
            text-decoration: none;
            font-weight: bold;
        }

        /* ================= ERROR MESSAGE ================= */

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 750px) {

            .login-container {
                width: 100%;

                grid-template-columns: 1fr;
            }

            .login-image {
                min-height: 220px;
            }

            .login-form-area {
                padding: 40px 30px;
            }

        }

    </style>

</head>

<body>

    <!-- ================= NAVBAR ================= -->

    <nav class="navbar">

        <div class="logo">
            <div class="logo-icon">❤</div>
            <span>CityCare Hospital</span>
        </div>

        <a href="index.php" class="back-home">
            ← Back to Home
        </a>

    </nav>

    <!-- ================= LOGIN SECTION ================= -->

    <section class="login-section">

        <div class="login-container">

            <!-- LEFT IMAGE -->

            <div class="login-image"></div>

            <!-- LOGIN FORM -->

            <div class="login-form-area">

                <div class="heart-icon">❤</div>

                <h1>Login to your Account</h1>

                <p class="subtitle">
                    Enter your details to continue
                </p>

                <?php if ($error !== ""): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">

                    <div class="form-group">

                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <a href="#" class="forgot">
                        Forgot Password?
                    </a>

                    <button type="submit" class="login-button">
                        Login
                    </button>

                </form>

                <p class="register-text">
                    Don't have an account?
                    <a href="register.php">Register</a>
                </p>

            </div>

        </div>

    </section>

</body>
</html>