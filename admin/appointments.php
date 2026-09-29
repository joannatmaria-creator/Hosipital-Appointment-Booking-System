
<?php
session_start();
require_once "../config/database.php";

// Admin access protection.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$allowedStatuses = [
    'All', 'Pending', 'Confirmed', 'Completed', 'Rejected', 'Cancelled'
];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'All';

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}

// CSRF protection.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle admin status changes.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $appointmentId = filter_var(
        $_POST['appointment_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    $newStatus = $_POST['new_status'] ?? '';
    $currentFilter = $_POST['filter'] ?? 'All';
    $currentSearch = trim($_POST['search'] ?? '');

    if (!in_array($currentFilter, $allowedStatuses, true)) {
        $currentFilter = 'All';
    }

    $redirectUrl = 'appointments.php?' . http_build_query([
        'search' => $currentSearch,
        'status' => $currentFilter
    ]);

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
            ['Confirmed', 'Rejected', 'Completed', 'Cancelled'],
            true
        )
    ) {
        header("Location: $redirectUrl&message=invalid");
        exit;
    }

    try {
        $checkStmt = $conn->prepare(
            "SELECT status
             FROM appointments
             WHERE appointment_id = ?"
        );
        $checkStmt->bind_param("i", $appointmentId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $appointment = $result->fetch_assoc();
        $checkStmt->close();

        if (!$appointment) {
            header("Location: $redirectUrl&message=notfound");
            exit;
        }

        $oldStatus = $appointment['status'];

        // Allow only defined status transitions.
        $validTransitions = [
            'Pending' => ['Confirmed', 'Rejected', 'Cancelled'],
            'Confirmed' => ['Completed', 'Rejected', 'Cancelled']
        ];

        $allowedChanges = $validTransitions[$oldStatus] ?? [];

        if (!in_array($newStatus, $allowedChanges, true)) {
            header("Location: $redirectUrl&message=invalidstatus");
            exit;
        }

        $updateStmt = $conn->prepare(
            "UPDATE appointments
             SET status = ?
             WHERE appointment_id = ? AND status = ?"
        );
        $updateStmt->bind_param(
            "sis",
            $newStatus,
            $appointmentId,
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

// Notifications.
$message = $_GET['message'] ?? '';

$messages = [
    'success' => ['Appointment status updated successfully!', 'success'],
    'invalid' => ['Invalid request. Please try again.', 'error'],
    'notfound' => ['Appointment not found.', 'error'],
    'invalidstatus' => ['This status change is not allowed.', 'error'],
    'error' => ['Unable to update appointment.', 'error']
];

$notice = $messages[$message] ?? null;

// Fetch actual appointment records from MySQL.
$appointments = [];
$loadError = false;

try {
    $sql = "SELECT
                a.appointment_id,
                p.full_name AS patient_name,
                d_user.full_name AS doctor_name,
                d.department,
                a.appointment_date,
                a.appointment_time,
                a.status
            FROM appointments a
            INNER JOIN users p
                ON a.patient_id = p.user_id
            INNER JOIN doctors d
                ON a.doctor_id = d.doctor_id
            INNER JOIN users d_user
                ON d.user_id = d_user.user_id
            WHERE 1 = 1";

    $types = '';
    $params = [];

    if ($search !== '') {
        $sql .= " AND (p.full_name LIKE ?
                    OR CAST(a.appointment_id AS CHAR) LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $types .= 'ss';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($statusFilter !== 'All') {
        $sql .= " AND a.status = ?";
        $types .= 's';
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY a.appointment_date DESC,
                        a.appointment_time DESC";

    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>All Appointments | CityCare Hospital</title>

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
        }

        header {
            background: #12345a;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            font-size: 24px;
        }

        header a {
            background: #2878c8;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
        }

        header a:hover {
            background: #1b5fa5;
        }

        .container {
            width: 92%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #64748b;
        }

        .filter-box {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-form input,
        .filter-form select {
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
            flex: 1;
            min-width: 180px;
        }

        .filter-form button {
            padding: 12px 22px;
            border: none;
            border-radius: 6px;
            background: #2878c8;
            color: white;
            font-size: 15px;
            cursor: pointer;
        }

        .filter-form button:hover {
            background: #1b5fa5;
        }

        .reset-btn {
            padding: 12px 20px;
            background: #e2e8f0;
            color: #12345a;
            text-decoration: none;
            border-radius: 6px;
        }

        .table-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            overflow-x: auto;
        }

        .table-card h3 {
            font-size: 21px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        th {
            background: #edf4fb;
            color: #12345a;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .confirmed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .completed {
            background: #dcfce7;
            color: #166534;
        }

        .rejected {
            background: #ffe4e6;
            color: #be123c;
        }

        .cancelled {
            background: #e5e7eb;
            color: #475569;
        }

        .action {
            display: inline-block;
            padding: 7px 10px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 12px;
            cursor: pointer;
        }

        .confirm {
            background: #198754;
        }

        .reject {
            background: #dc3545;
        }

        .complete {
            background: #526b8a;
        }

        .cancel {
            background: #64748b;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .notice.success {
            background: #dcfce7;
            color: #166534;
        }

        .notice.error {
            background: #ffe4e6;
            color: #be123c;
        }

        footer {
            text-align: center;
            padding: 25px 10px;
            color: #64748b;
            margin-top: 30px;
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                width: 94%;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form input,
            .filter-form select {
                width: 100%;
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

    <div class="page-title">
        <h1>All Appointments</h1>
        <p>View and monitor hospital appointments.</p>
    </div>

    <?php if ($notice): ?>
        <div class="notice <?= e($notice[1]) ?>">
            <?= e($notice[0]) ?>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <section class="filter-box">
        <form method="GET" class="filter-form">

            <input
                type="text"
                name="search"
                placeholder="Search patient name or ID"
                value="<?= e($search) ?>"
            >

            <select name="status">
                <?php foreach ($allowedStatuses as $status): ?>
                    <option
                        value="<?= e($status) ?>"
                        <?= $statusFilter === $status ? 'selected' : '' ?>
                    >
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Search</button>
            <a href="appointments.php" class="reset-btn">Reset</a>

        </form>
    </section>

    <!-- Appointments Table -->
    <section class="table-card">
        <h3>Appointment Records</h3>

        <?php if ($loadError): ?>
            <div class="empty">
                <h3>Unable to load appointments.</h3>
                <p>Please refresh the page and try again.</p>
            </div>

        <?php elseif (empty($appointments)): ?>
            <div class="empty">
                <h3>No appointments found.</h3>
                <p>Try changing your search or status filter.</p>
            </div>

        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <?= (int)$appointment['appointment_id'] ?>
                            </td>

                            <td>
                                <?= e($appointment['patient_name']) ?>
                            </td>

                            <td>
                                <?= e($appointment['doctor_name']) ?>
                            </td>

                            <td>
                                <?= e($appointment['department']) ?>
                            </td>

                            <td>
                                <?= e(date(
                                    'd M Y',
                                    strtotime($appointment['appointment_date'])
                                )) ?>
                            </td>

                            <td>
                                <?= e(date(
                                    'h:i A',
                                    strtotime($appointment['appointment_time'])
                                )) ?>
                            </td>

                            <td>
                                <span class="status <?= e(strtolower($appointment['status'])) ?>">
                                    <?= e($appointment['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($appointment['status'] === 'Pending'): ?>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Confirmed">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action confirm">
                                            Confirm
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Reject this appointment?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Rejected">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action reject">
                                            Reject
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Cancel this appointment?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Cancelled">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action cancel">
                                            Cancel
                                        </button>
                                    </form>

                                <?php elseif ($appointment['status'] === 'Confirmed'): ?>

                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Mark this appointment as completed?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Completed">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action complete">
                                            Complete
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Reject this appointment?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Rejected">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action reject">
                                            Reject
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Cancel this appointment?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="appointment_id"
                                               value="<?= (int)$appointment['appointment_id'] ?>">
                                        <input type="hidden" name="new_status"
                                               value="Cancelled">
                                        <input type="hidden" name="filter"
                                               value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="search"
                                               value="<?= e($search) ?>">
                                        <button type="submit" class="action cancel">
                                            Cancel
                                        </button>
                                    </form>

                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>