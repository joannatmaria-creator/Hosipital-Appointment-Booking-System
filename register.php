
<?php
require_once "config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $name === "" || $email === "" || $phone === "" ||
        $password === "" || $confirmPassword === ""
    ) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "error";
        } else {
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "patient";

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, email, phone, password_hash, role)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $phone,
                $passwordHash,
                $role
            );

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CityCare Hospital</title>

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

        .register-section {
            min-height: calc(100vh - 64px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 20px;
            background: linear-gradient(135deg, #eaf7ff, #dceef9);
        }

        .register-container {
            width: 900px;
            min-height: 520px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 35px rgba(0, 50, 80, 0.12);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        .register-image {
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

        .register-form-area {
            padding: 35px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .heart-icon {
            width: 45px;
            height: 45px;
            background: #e7f4ff;
            color: #087fe0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .register-form-area h1 {
            font-size: 26px;
            margin-bottom: 7px;
        }

        .subtitle {
            color: #718394;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 13px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8e4ec;
            border-radius: 6px;
            outline: none;
            font-size: 13px;
        }

        .form-group input:focus {
            border-color: #1688e5;
            box-shadow: 0 0 0 2px rgba(22, 136, 229, 0.10);
        }

        .register-button {
            width: 100%;
            border: none;
            background: #087fe0;
            color: white;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 5px;
            transition: 0.3s;
        }

        .register-button:hover {
            background: #0568b9;
        }

        .login-text {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #718394;
        }

        .login-text a {
            color: #087fe0;
            text-decoration: none;
            font-weight: bold;
        }

        .message {
            padding: 10px;
            margin-bottom: 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message.error {
            color: #b42318;
            background: #fef3f2;
            border: 1px solid #fecdca;
        }

        .message.success {
            color: #067647;
            background: #ecfdf3;
            border: 1px solid #abefc6;
        }

        @media (max-width: 750px) {
            .register-container {
                width: 100%;
                grid-template-columns: 1fr;
            }

            .register-image {
                min-height: 220px;
            }

            .register-form-area {
                padding: 35px 30px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon">❤</div>
            <span>CityCare Hospital</span>
        </div>

        <a href="index.php" class="back-home">
            ← Back to Home
        </a>
    </nav>

    <section class="register-section">
        <div class="register-container">

            <div class="register-image">
                <div class="image-content">
                    <h2>Join CityCare</h2>
                    <p>
                        Create your patient account and
                        manage your healthcare appointments
                        easily.
                    </p>
                </div>
            </div>

            <div class="register-form-area">
                <div class="heart-icon">❤</div>

                <h1>Create Your Account</h1>

                <p class="subtitle">
                    Register as a patient at CityCare Hospital
                </p>

                <?php if ($message !== ""): ?>
                    <div class="message <?= htmlspecialchars($messageType) ?>">
                        <?= htmlspecialchars($message) ?>
                        <?php if ($messageType === "success"): ?>
                            <br>
                            <a href="login.php">Go to Login</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input
                            type="text"
                            name="name"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input
                            type="tel"
                            name="phone"
                            placeholder="Enter your phone number"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            maxlength="15"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Create a password"
                            minlength="8"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            required
                        >
                    </div>

                    <button type="submit" class="register-button">
                        Register
                    </button>
                </form>

                <p class="login-text">
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>
            </div>
        </div>
    </section>

</body>
</html>