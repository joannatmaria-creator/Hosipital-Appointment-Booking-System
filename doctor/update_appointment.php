
<?php

// Sample appointment data for frontend testing.
// This will be replaced with MySQL data later.

$appointment = [
    "id" => 1,
    "patient_name" => "Arun Kumar",
    "department" => "General Medicine",
    "doctor_name" => "Dr. Priya",
    "date" => "28 September 2026",
    "time" => "10:00 AM",
    "reason" => "Fever and headache",
    "status" => "Pending"
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
    <title>Update Appointment | CityCare Hospital</title>

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
            max-width: 750px;
            margin: 40px auto;
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
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 {
            color: #12345a;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5eaf1;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail label {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .detail p {
            font-size: 15px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #12345a;
            font-weight: bold;
        }

        select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        button, .cancel {
            border: none;
            padding: 12px 20px;
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

            .details {
                grid-template-columns: 1fr;
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

    <h1>Update Appointment</h1>
    <p class="subtitle">Review the appointment and update its status.</p>

    <section class="card">
        <h2>Appointment Details</h2>

        <div class="details">
            <div class="detail">
                <label>Appointment ID</label>
                <p><?= e($appointment['id']) ?></p>
            </div>

            <div class="detail">
                <label>Patient Name</label>
                <p><?= e($appointment['patient_name']) ?></p>
            </div>

            <div class="detail">
                <label>Doctor</label>
                <p><?= e($appointment['doctor_name']) ?></p>
            </div>

            <div class="detail">
                <label>Department</label>
                <p><?= e($appointment['department']) ?></p>
            </div>

            <div class="detail">
                <label>Date</label>
                <p><?= e($appointment['date']) ?></p>
            </div>

            <div class="detail">
                <label>Time</label>
                <p><?= e($appointment['time']) ?></p>
            </div>

            <div class="detail">
                <label>Reason for Visit</label>
                <p><?= e($appointment['reason']) ?></p>
            </div>

            <div class="detail">
                <label>Current Status</label>
                <p id="currentStatus"><?= e($appointment['status']) ?></p>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Change Appointment Status</h2>

        <form id="updateForm">
            <div class="form-group">
                <label for="status">New Status</label>
                <select id="status" name="status" required>
                    <option value="Pending"
                        <?= $appointment['status'] === 'Pending' ? 'selected' : '' ?>>
                        Pending
                    </option>
                    <option value="Confirmed"
                        <?= $appointment['status'] === 'Confirmed' ? 'selected' : '' ?>>
                        Confirmed
                    </option>
                    <option value="Rejected"
                        <?= $appointment['status'] === 'Rejected' ? 'selected' : '' ?>>
                        Rejected
                    </option>
                    <option value="Completed"
                        <?= $appointment['status'] === 'Completed' ? 'selected' : '' ?>>
                        Completed
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes"
                    placeholder="Enter any additional notes..."></textarea>
            </div>

            <div class="buttons">
                <button type="submit" class="save">Update Status</button>
                <a href="appointments.php" class="cancel">Cancel</a>
            </div>
        </form>
    </section>

</div>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

<script>
    const form = document.getElementById("updateForm");
    const statusSelect = document.getElementById("status");
    const currentStatus = document.getElementById("currentStatus");

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        const newStatus = statusSelect.value;

        if (!confirm("Are you sure you want to change the appointment status to " + newStatus + "?")) {
            return;
        }

        alert("Frontend demonstration only. The status will be saved after MySQL integration.");
    });
</script>

</body>
</html>