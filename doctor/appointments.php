
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

$loggedInUserId = (int) $_SESSION['user_id'];

// Get the logged-in doctor's details.
try {
    $doctorStmt = $conn->prepare(
        "SELECT d.doctor_id, d.department, u.full_name
         FROM doctors d
         INNER JOIN users u ON d.user_id = u.user_id
         WHERE d.user_id = ? AND d.status = 'Active'
           AND u.role = 'doctor'"
    );

    $doctorStmt->bind_param("i", $loggedInUserId);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    $doctor = $doctorResult->fetch_assoc();
    $doctorStmt->close();

    if (!$doctor) {
        http_response_code(403);
        exit("Doctor account not found or inactive.");
    }

    $doctorId = (int) $doctor['doctor_id'];

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Unable to load doctor details.");
}

// Escape output.
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Allowed status filters.
$allowedFilters = [
    'All',
    'Pending',
    'Confirmed',
    'Completed',
    'Rejected'
];

$filter = $_GET['status'] ?? 'All';

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'All';
}

// Handle appointment status updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $appointmentId = filter_var(
        $_POST['appointment_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    $newStatus = $_POST['new_status'] ?? '';
    $currentFilter = $_POST['filter'] ?? 'All';

    if (!in_array($currentFilter, $allowedFilters, true)) {
        $currentFilter = 'All';
    }

    $redirectUrl = "appointments.php?status="
        . urlencode($currentFilter);

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        header("Location: $redirectUrl&message=invalid");
        exit;
    }

    if (
        !$appointmentId ||
        !in_array(
            $newStatus,
            ['Confirmed', 'Rejected', 'Completed'],
            true
        )
    ) {
        header("Location: $redirectUrl&message=invalid");
        exit;
    }

    try {
        // Find the appointment assigned to this doctor.
        $checkStmt = $conn->prepare(
            "SELECT status
             FROM appointments
             WHERE appointment_id = ?
               AND doctor_id = ?"
        );

        $checkStmt->bind_param(
            "ii",
            $appointmentId,
            $doctorId
        );
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $appointment = $checkResult->fetch_assoc();
        $checkStmt->close();

        if (!$appointment) {
            header("Location: $redirectUrl&message=notfound");
            exit;
        }

        $oldStatus = $appointment['status'];

        // Only permit valid status transitions.
        $validTransition =
            ($oldStatus === 'Pending' &&
                in_array($newStatus, ['Confirmed', 'Rejected'], true))
            ||
            ($oldStatus === 'Confirmed' &&
                $newStatus === 'Completed');

        if (!$validTransition) {
            header("Location: $redirectUrl&message=invalidstatus");
            exit;
        }

        // Update only this doctor's appointment.
        $updateStmt = $conn->prepare(
            "UPDATE appointments
             SET status = ?
             WHERE appointment_id = ?
               AND doctor_id = ?
               AND status = ?"
        );

        $updateStmt->bind_param(
            "siis",
            $newStatus,
            $appointmentId,
            $doctorId,
            $oldStatus
        );

        $updateStmt->execute();
        $updated = $updateStmt->affected_rows;
        $updateStmt->close();

        if ($updated === 1) {
            header("Location: $redirectUrl&message=success");
        } else {
            header("Location: $redirectUrl&message=invalidstatus");
        }
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        header("Location: $redirectUrl&message=error");
        exit;
    }
}

// Success and error messages.
$message = $_GET['message'] ?? '';

$messages = [
    'success' => ['Appointment status updated successfully!', 'success'],
    'invalid' => ['Invalid request. Please try again.', 'error'],
    'notfound' => ['Appointment not found.', 'error'],
    'invalidstatus' => ['This status change is not allowed.', 'error'],
    'error' => ['Unable to update appointment. Please try again.', 'error']
];

$notice = $messages[$message] ?? null;

// Fetch appointments assigned to the logged-in doctor.
$appointments = [];

try {
    if ($filter === 'All') {
        $stmt = $conn->prepare(
            "SELECT
                a.appointment_id,
                a.patient_id,
                p.full_name AS patient_name,
                p.phone,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status
             FROM appointments a
             INNER JOIN users p ON a.patient_id = p.user_id
             WHERE a.doctor_id = ?
             ORDER BY a.appointment_date DESC,
                      a.appointment_time DESC"
        );

        $stmt->bind_param("i", $doctorId);
    } else {
        $stmt = $conn->prepare(
            "SELECT
                a.appointment_id,
                a.patient_id,
                p.full_name AS patient_name,
                p.phone,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status
             FROM appointments a
             INNER JOIN users p ON a.patient_id = p.user_id
             WHERE a.doctor_id = ?
               AND a.status = ?
             ORDER BY a.appointment_date DESC,
                      a.appointment_time DESC"
        );

        $stmt->bind_param("is", $doctorId, $filter);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $loadError = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | CityCare Hospital</title>

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
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
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

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2878c8;
            text-decoration: none;
            font-weight: bold;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .filters a {
            text-decoration: none;
            padding: 10px 18px;
            border: 1px solid #2878c8;
            border-radius: 20px;
            color: #2878c8;
            background: white;
            font-size: 14px;
        }

        .filters a.active,
        .filters a:hover {
            background: #2878c8;
            color: white;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5eaf1;
            font-size: 14px;
        }

        th {
            background: #eaf1fa;
            color: #12345a;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
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

        .action {
            display: inline-block;
            padding: 7px 10px;
            margin: 3px 2px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
        }

        .view {
            background: #2878c8;
            color: white;
        }

        .confirm {
            background: #198754;
            color: white;
        }

        .reject {
            background: #dc3545;
            color: white;
        }

        .complete {
            background: #526b8a;
            color: white;
        }

        .empty {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .notice.success {
            background: #d9f7e4;
            color: #16733d;
        }

        .notice.error {
            background: #ffe0e0;
            color: #b42318;
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

            .container {
                margin-top: 25px;
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

    <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>

    <div class="heading">
        <h1>My Appointments</h1>
        <p>
            <?= e($doctor['full_name']) ?>
            | <?= e($doctor['department']) ?>
        </p>
    </div>

    <?php if ($notice): ?>
        <div class="notice <?= e($notice[1]) ?>">
            <?= e($notice[0]) ?>
        </div>
    <?php endif; ?>

    <div class="filters">
        <?php foreach ($allowedFilters as $status): ?>
            <a
                href="?status=<?= urlencode($status) ?>"
                class="<?= $filter === $status ? 'active' : '' ?>"
            >
                <?= e($status) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-container">
        <?php if (!empty($loadError)): ?>
            <div class="empty">
                <h3>Unable to load appointments</h3>
                <p>Please refresh the page and try again.</p>
            </div>

        <?php elseif (count($appointments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <?= e($appointment['patient_name']) ?>
                            </td>

                            <td>
                                <?= e($appointment['phone'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?= e(
                                    date(
                                        'd M Y',
                                        strtotime($appointment['appointment_date'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    date(
                                        'h:i A',
                                        strtotime($appointment['appointment_time'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= e($appointment['reason'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <span class="status <?= strtolower(e($appointment['status'])) ?>">
                                    <?= e($appointment['status']) ?>
                                </span>
                            </td>

                            <td>
                                <a class="action view"
                                href="patient_details.php?appointment_id=<?= (int)$appointment['appointment_id'] ?>">
                                View
                                </a>
                                <?php if ($appointment['status'] === 'Pending'): ?>

                                    <form method="POST" style="display:inline;">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="appointment_id"
                                            value="<?= (int)$appointment['appointment_id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="Confirmed"
                                        >
                                        <input
                                            type="hidden"
                                            name="filter"
                                            value="<?= e($filter) ?>"
                                        >
                                        <button
                                            type="submit"
                                            class="action confirm"
                                        >
                                            Confirm
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to reject this appointment?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="appointment_id"
                                            value="<?= (int)$appointment['appointment_id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="Rejected"
                                        >
                                        <input
                                            type="hidden"
                                            name="filter"
                                            value="<?= e($filter) ?>"
                                        >
                                        <button
                                            type="submit"
                                            class="action reject"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                <?php elseif ($appointment['status'] === 'Confirmed'): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Mark this appointment as completed?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="appointment_id"
                                            value="<?= (int)$appointment['appointment_id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="Completed"
                                        >
                                        <input
                                            type="hidden"
                                            name="filter"
                                            value="<?= e($filter) ?>"
                                        >
                                        <button
                                            type="submit"
                                            class="action complete"
                                        >
                                            Complete
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <div class="empty">
                <h3>No appointments found</h3>
                <p>There are no appointments with this status.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>