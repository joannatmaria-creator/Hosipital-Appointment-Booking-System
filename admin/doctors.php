
<?php
require_once "../config/database.php";

// Escape output to prevent HTML injection.
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Search and department filters
$search = trim($_GET['search'] ?? '');
$department = $_GET['department'] ?? 'All';

// Fetch departments from MySQL
$departments = [];
$departmentResult = $conn->query(
    "SELECT DISTINCT department
     FROM doctors
     ORDER BY department"
);

if ($departmentResult) {
    while ($row = $departmentResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

if ($department !== 'All' &&
    !in_array($department, $departments, true)) {
    $department = 'All';
}

// Fetch doctors from MySQL
$sql = "SELECT
            d.doctor_id,
            u.full_name,
            d.department,
            d.qualification,
            d.specialization,
            d.experience,
            d.status
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        WHERE 1 = ?";

$params = [1];
$types = "i";

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ?
              OR d.specialization LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if ($department !== 'All') {
    $sql .= " AND d.department = ?";
    $params[] = $department;
    $types .= "s";
}

$sql .= " ORDER BY d.doctor_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$doctors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors | CityCare Hospital</title>

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
            background: #e74c3c;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .container {
            max-width: 1250px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .heading h1 {
            color: #12345a;
            margin-bottom: 8px;
        }

        .heading p {
            color: #64748b;
        }

        .add-btn {
            background: #198754;
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
        }

        .add-btn:hover {
            background: #146c43;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .filters input,
        .filters select {
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            min-width: 190px;
            background: white;
        }

        .filters button {
            background: #2878c8;
            color: white;
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .filters button:hover {
            background: #12345a;
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
            min-width: 950px;
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
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .active {
            background: #d9f7e4;
            color: #16733d;
        }

        .inactive {
            background: #ffe0e0;
            color: #b42318;
        }

        .action {
            display: inline-block;
            padding: 8px 12px;
            margin: 2px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 12px;
        }

        .edit {
            background: #2878c8;
        }

        .delete {
            background: #dc3545;
        }

        .empty {
            padding: 35px;
            text-align: center;
            color: #64748b;
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

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select,
            .filters button {
                width: 100%;
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

    <a href="dashboard.php" class="back">
        &larr; Back to Dashboard
    </a>

    <div class="heading">
        <div>
            <h1>Manage Doctors</h1>
            <p>View and manage hospital doctors.</p>
        </div>

        <a href="add_doctor.php" class="add-btn">
            + Add New Doctor
        </a>
    </div>

    <form method="GET" class="filters">
        <input
            type="text"
            name="search"
            placeholder="Search doctor..."
            value="<?= e($search) ?>"
        >

        <select name="department">
            <option value="All">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= e($dept) ?>"
                    <?= $department === $dept ? 'selected' : '' ?>>
                    <?= e($dept) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search</button>
    </form>

    <div class="table-container">
        <?php if (count($doctors) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Doctor Name</th>
                        <th>Department</th>
                        <th>Qualification</th>
                        <th>Specialization</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td>
                                <?= (int)$doctor['doctor_id'] ?>
                            </td>
                            <td>
                                <?= e($doctor['full_name']) ?>
                            </td>
                            <td>
                                <?= e($doctor['department']) ?>
                            </td>
                            <td>
                                <?= e($doctor['qualification']) ?>
                            </td>
                            <td>
                                <?= e($doctor['specialization']) ?>
                            </td>
                            <td>
                                <?= (int)$doctor['experience'] ?> years
                            </td>
                            <td>
                                <span class="status <?= strtolower(e($doctor['status'])) ?>">
                                    <?= e($doctor['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a class="action edit"
                                   href="edit_doctor.php?id=<?= (int)$doctor['doctor_id'] ?>">
                                    Edit
                                </a>

                                <a class="action delete"
                                   href="delete_doctor.php?id=<?= (int)$doctor['doctor_id'] ?>">
                                    Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">
                <h3>No doctors found</h3>
                <p>Try another search or department.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<footer>
    &copy; 2026 CityCare Hospital. All rights reserved.
</footer>

</body>
</html>