
<?php
// Sample doctor data for frontend testing.
// Later, this will be fetched from MySQL.

$doctor = [
    "id" => 1,
    "name" => "Dr. Priya",
    "email" => "priya@example.com",
    "phone" => "9876543210",
    "department" => "General Medicine",
    "qualification" => "MBBS, MD",
    "specialization" => "General Physician",
    "experience" => 8,
    "status" => "Active"
];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor | CityCare Hospital</title>

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

        .save {
            background: #2878c8;
            color: white;
        }

        .save:hover {
            background: #12345a;
        }

        .cancel {
            background: #e5eaf1;
            color: #263449;
        }

        .cancel:hover {
            background: #cbd5e1;
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
        <h1>Edit Doctor</h1>
        <p>Update the doctor's professional and contact information.</p>
    </div>

    <form class="form-card" id="editDoctorForm">
        <h2>Personal Information</h2>

        <div class="form-grid">
            <div class="form-group">
                <label for="name">Doctor's Full Name</label>
                <input type="text" id="name" name="name"
                       value="<?= e($doctor['name']) ?>"
                       maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= e($doctor['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= e($doctor['phone']) ?>"
                       pattern="[0-9]{10}" maxlength="10" required>
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e($department) ?>"
                            <?= $doctor['department'] === $department ? 'selected' : '' ?>>
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
                <input type="text" id="qualification" name="qualification"
                       value="<?= e($doctor['qualification']) ?>"
                       maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" id="specialization" name="specialization"
                       value="<?= e($doctor['specialization']) ?>"
                       maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="experience">Years of Experience</label>
                <input type="number" id="experience" name="experience"
                       value="<?= (int)$doctor['experience'] ?>"
                       min="0" max="70" required>
            </div>

            <div class="form-group">
                <label for="status">Account Status</label>
                <select id="status" name="status" required>
                    <option value="Active"
                        <?= $doctor['status'] === 'Active' ? 'selected' : '' ?>>
                        Active
                    </option>
                    <option value="Inactive"
                        <?= $doctor['status'] === 'Inactive' ? 'selected' : '' ?>>
                        Inactive
                    </option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="save">Save Changes</button>
            <a href="doctors.php" class="cancel">Cancel</a>
        </div>
    </form>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

<script>
    document.getElementById("editDoctorForm").addEventListener("submit", function(event) {
        event.preventDefault();

        if (!this.reportValidity()) {
            return;
        }

        if (confirm("Are you sure you want to save these changes?")) {
            alert("Frontend demonstration only. Changes will be saved after MySQL integration.");
        }
    });
</script>

</body>
</html>