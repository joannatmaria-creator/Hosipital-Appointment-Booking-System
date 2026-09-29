
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityCare Hospital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f8fc;
            color: #243447;
        }

        nav {
            background: #12345a;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        nav h2 {
            font-size: 25px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 22px;
            font-size: 15px;
        }

        nav a:hover {
            color: #69d3ff;
        }

        .hero {
            min-height: 380px;
            background:
                linear-gradient(rgba(9, 35, 65, 0.65),
                rgba(9, 35, 65, 0.65)),
                url("images/hospital.jpg") center/cover;
            display: flex;
            align-items: center;
            padding: 50px 8%;
            color: white;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 19px;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #08a6d9;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn:hover {
            background: #087eae;
        }

        .section {
            padding: 55px 7%;
            text-align: center;
        }

        .section h2 {
            color: #12345a;
            font-size: 30px;
            margin-bottom: 12px;
        }

        .section-desc {
            color: #667788;
            margin-bottom: 30px;
        }

        .departments {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 22px;
        }

        .department {
            background: white;
            padding: 30px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-decoration: none;
            color: #243447;
            border: 1px solid #e3ebf3;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .department:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.13);
        }

        .department .symbol {
            font-size: 38px;
            margin-bottom: 15px;
        }

        .department h3 {
            color: #12345a;
            margin-bottom: 10px;
        }

        .department p {
            font-size: 14px;
            color: #687989;
            line-height: 1.6;
        }

        .info {
            background: #e8f3fb;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-card {
            padding: 20px;
            background: white;
            border-radius: 10px;
        }

        .info-card h3 {
            color: #087eae;
            margin-bottom: 10px;
        }

        footer {
            background: #12345a;
            color: white;
            text-align: center;
            padding: 22px;
        }

        @media (max-width: 600px) {
            nav {
                flex-direction: column;
            }

            nav a {
                margin: 0 8px;
            }

            .hero h1 {
                font-size: 34px;
            }
        }
    </style>
</head>
<body>

<nav>
    <h2>CityCare Hospital</h2>
    <div>
        <a href="index.php">Home</a>
        <a href="#departments">Departments</a>
        <a href="login.php?role=patient">Patient Login</a>
        <a href="login.php?role=doctor">Doctor Login</a>
        <a href="login.php?role=admin">Admin Login</a>
    </div>
</nav>

<section class="hero">
    <div>
        <h1>Your Health<br>Our Priority</h1>
        <p>Quality healthcare and compassionate care for everyone.</p>
        <a href="login.php?role=patient" class="btn">
            Book Appointment
        </a>
    </div>
</section>

<section class="section" id="departments">
    <h2>Our Departments</h2>
    <p class="section-desc">
        Select a department to view its doctors and their details.
    </p>

    <div class="departments">

        <a class="department"
           href="patient/doctors.php?department=General%20Medicine">
            <div class="symbol">🩺</div>
            <h3>General Medicine</h3>
            <p>Medical care for common illnesses and health concerns.</p>
        </a>

        <a class="department"
           href="patient/doctors.php?department=Cardiology">
            <div class="symbol">❤️</div>
            <h3>Cardiology</h3>
            <p>Diagnosis and treatment of heart-related conditions.</p>
        </a>

        <a class="department"
           href="patient/doctors.php?department=Dermatology">
            <div class="symbol">🧴</div>
            <h3>Dermatology</h3>
            <p>Care for skin, hair and nail conditions.</p>
        </a>

        <a class="department"
           href="patient/doctors.php?department=Orthopedics">
            <div class="symbol">🦴</div>
            <h3>Orthopedics</h3>
            <p>Care for bones, joints and musculoskeletal conditions.</p>
        </a>

        <a class="department"
           href="patient/doctors.php?department=Pediatrics">
            <div class="symbol">👶</div>
            <h3>Pediatrics</h3>
            <p>Healthcare services for infants and children.</p>
        </a>

        <a class="department"
           href="patient/doctors.php?department=Gynecology">
            <div class="symbol">🌸</div>
            <h3>Gynecology</h3>
            <p>Women's reproductive and general healthcare.</p>
        </a>

    </div>
</section>

<section class="section info">
    <div class="info-card">
        <h3>Expert Doctors</h3>
        <p>Qualified medical professionals across our departments.</p>
    </div>

    <div class="info-card">
        <h3>Patient Care</h3>
        <p>Healthcare focused on patient needs and comfort.</p>
    </div>

    <div class="info-card">
        <h3>Appointment Booking</h3>
        <p>Book and manage your appointments after logging in.</p>
    </div>
</section>

<footer>
    <p>&copy; 2026 CityCare Hospital. All rights reserved.</p>
</footer>

</body>
</html>