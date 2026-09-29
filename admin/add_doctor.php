
<?php
session_start();
require_once "../config/database.php";

$departments = [
    "General Medicine",
    "Cardiology",
    "Dermatology",
    "Orthopedics",
    "Pediatrics",
    "Gynecology"
];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$success = "";
$error = "";

$values = [
    "name" => "",
    "email" => "",
    "phone" => "",
    "department" => "",
    "qualification" => "",
    "specialization" => "",
    "experience" => "",
    "status" => "Active",
    "username" => ""
];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($values as $key => $value) {
        $values[$key] = trim($_POST[$key] ?? "");
    }

    $password = $_POST["password"] ?? "";
    $csrfToken = $_POST["csrf_token"] ?? "";

    // Validate the form
    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $error = "Invalid form submission. Please try again.";
    } elseif (
        $values["name"] === "" ||
        $values["email"] === "" ||
        $values["phone"] === "" ||
        $values["department"] === "" ||
        $values["qualification"] === "" ||
        $values["specialization"] === "" ||
        $values["experience"] === "" ||
        $values["username"] === "" ||
        $password === ""
    ) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($values["email"], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9]{10}$/', $values["phone"])) {
        $error = "Phone number must contain exactly 10 digits.";
    } elseif (!in_array($values["department"], $departments, true)) {
        $error = "Please select a valid department.";
    } elseif (!in_array($values["status"], ["Active", "Inactive"], true)) {
        $error = "Please select a valid account status.";
    } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $values["username"])) {
        $error = "Username must be 3–50 characters and contain only letters, numbers, dots or underscores.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif (
        !ctype_digit($values["experience"]) ||
        (int)$values["experience"] > 70
    ) {
        $error = "Experience must be a number between 0 and 70.";
    } elseif (
        strlen($values["name"]) > 100 ||
        strlen($values["email"]) > 100 ||
        strlen($values["qualification"]) > 100 ||
        strlen($values["specialization"]) > 100
    ) {
        $error = "One or more fields exceed the allowed length.";
    } else {
        try {
            // Check for an existing email or username.
            $check = $conn->prepare(
                "SELECT user_id FROM users
                 WHERE email = ? OR username = ?"
            );
            $check->bind_param(
                "ss",
                $values["email"],
                $values["username"]
            );
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $error = "This email address or username is already registered.";
            } else {
                // Save both records as one transaction.
                $conn->begin_transaction();

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $experience = (int)$values["experience"];
                $role = "doctor";

                // Create the doctor's login account.
                $userStmt = $conn->prepare(
                    "INSERT INTO users
                    (full_name, username, email, phone, password_hash, role)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $userStmt->bind_param(
                    "ssssss",
                    $values["name"],
                    $values["username"],
                    $values["email"],
                    $values["phone"],
                    $passwordHash,
                    $role
                );
                $userStmt->execute();

                $userId = $conn->insert_id;
                $userStmt->close();

                // Create the doctor's professional record.
                $doctorStmt = $conn->prepare(
                    "INSERT INTO doctors
                    (user_id, department, qualification,
                     specialization, experience, status)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $doctorStmt->bind_param(
                    "isssis",
                    $userId,
                    $values["department"],
                    $values["qualification"],
                    $values["specialization"],
                    $experience,
                    $values["status"]
                );
                $doctorStmt->execute();
                $doctorStmt->close();

                $conn->commit();

                $success = "Doctor added successfully! The login account has also been created.";

                // Clear the form after successful insertion.
                foreach ($values as $key => $value) {
                    $values[$key] = "";
                }
                $values["status"] = "Active";

                // Generate a fresh token after submission.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
            }
        } catch (Throwable $ex) {
            if ($conn->errno || $conn->connect_errno === 0) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                    // Nothing further to roll back.
                }
            }

            error_log("Add doctor error: " . $ex->getMessage());

            if ($ex instanceof mysqli_sql_exception &&
                $ex->getCode() === 1062) {
                $error = "This email address or username is already registered.";
            } else {
                $error = "Unable to add the doctor. Please check the database and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor | CityCare Hospital</title>

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
            color: white;
            background: #e74c3c;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .container {
            max-width: 850px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            color: #2878c8;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 22px;
        }

        .heading {
            margin-bottom: 25px;
        }

        .heading h1 {
            color: #12345a;
            margin-bottom: 8px;
        }

        .heading p {
            color: #64748b;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .form-card h2 {
            color: #12345a;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5eaf1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #12345a;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2878c8;
            box-shadow: 0 0 0 2px rgba(40,120,200,0.15);
        }

        .hint {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-top: 6px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }

        button, .cancel {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .submit {
            background: #2878c8;
            color: white;
        }

        .submit:hover {
            background: #12345a;
        }

        .cancel {
            background: #e5eaf1;
            color: #263449;
        }

        .cancel:hover {
            background: #cbd5e1;
        }

        .message {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
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

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-group.full {
                grid-column: auto;
            }

            .form-card {
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

<main class="container">

    <a href="doctors.php" class="back">
        &larr; Back to Doctors
    </a>

    <div class="heading">
        <h1>Add New Doctor</h1>
        <p>Enter the doctor's details to add them to CityCare Hospital.</p>
    </div>

    <form class="form-card" id="doctorForm" method="POST"
          action="" autocomplete="off">
        <?php if ($success !== ""): ?>
            <div class="message success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="message error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION["csrf_token"]) ?>">

        <h2>Personal Information</h2>

        <div class="form-grid">
            <div class="form-group">
                <label for="name">Doctor's Full Name</label>
                <input type="text" id="name" name="name"
                       placeholder="e.g. Dr. Priya"
                       maxlength="100" required
                       value="<?= e($values["name"]) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="doctor@example.com"
                       maxlength="100" required
                       value="<?= e($values["email"]) ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       placeholder="10-digit mobile number"
                       pattern="[0-9]{10}" maxlength="10" required
                       value="<?= e($values["phone"]) ?>">
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e($department) ?>"
                            <?= $values["department"] === $department
                                ? "selected" : "" ?>>
                            <?= e($department) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h2>Professional Information</h2>

        <div class="form-grid">
            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" id="qualification"
                       name="qualification"
                       placeholder="e.g. MBBS, MD"
                       maxlength="100" required
                       value="<?= e($values["qualification"]) ?>">
            </div>

            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" id="specialization"
                       name="specialization"
                       placeholder="e.g. Cardiologist"
                       maxlength="100" required
                       value="<?= e($values["specialization"]) ?>">
            </div>

            <div class="form-group">
                <label for="experience">Years of Experience</label>
                <input type="number" id="experience"
                       name="experience" min="0" max="70"
                       placeholder="e.g. 8" required
                       value="<?= e($values["experience"]) ?>">
            </div>

            <div class="form-group">
                <label for="status">Account Status</label>
                <select id="status" name="status" required>
                    <option value="Active"
                        <?= $values["status"] === "Active"
                            ? "selected" : "" ?>>Active</option>
                    <option value="Inactive"
                        <?= $values["status"] === "Inactive"
                            ? "selected" : "" ?>>Inactive</option>
                </select>
            </div>
        </div>

        <h2>Doctor Login Information</h2>

        <div class="form-grid">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="Create a username"
                       minlength="3" maxlength="50" required
                       value="<?= e($values["username"]) ?>">
                <span class="hint">
                    Letters, numbers, dots and underscores are allowed.
                </span>
            </div>

            <div class="form-group">
                <label for="password">Temporary Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter a temporary password"
                       minlength="8" required autocomplete="new-password">
                <span class="hint">At least 8 characters.</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="submit">Add Doctor</button>
            <a href="doctors.php" class="cancel">Cancel</a>
        </div>
    </form>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

<script>
    document.getElementById("doctorForm").addEventListener(
        "submit",
        function(event) {
            if (!this.reportValidity()) {
                event.preventDefault();
            }
        }
    );
</script>

</body>
</html>