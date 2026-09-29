# 🏥 Hospital Appointment Booking System

A web-based Hospital Appointment Booking System developed using PHP, MySQL, HTML, CSS and JavaScript. The application helps patients book appointments with doctors and provides separate dashboards for patients, doctors and administrators.

## 📌 Project Overview

The Hospital Appointment Booking System is designed to simplify hospital appointment management. It provides an online platform where patients can explore doctors, book appointments and manage their bookings. Doctors can view and update appointments, while administrators can manage doctors and monitor appointments.

## ✨ Features

### 👤 Patient Module

* Patient registration and login
* Patient dashboard
* Browse available doctors
* View doctor details
* Book appointments
* View appointment history
* Cancel appointments

### 🩺 Doctor Module

* Doctor dashboard
* View appointments
* Access patient details
* Update appointment information

### 🛡️ Admin Module

* Admin dashboard
* Add new doctors
* View doctor details
* Edit doctor information
* Delete doctors
* View hospital appointments

## 🛠️ Technologies Used

| Technology | Purpose                  |
| ---------- | ------------------------ |
| PHP        | Backend development      |
| MySQL      | Database management      |
| HTML5      | Web page structure       |
| CSS3       | Styling and layout       |
| JavaScript | Client-side interactions |
| XAMPP      | Local development server |
| Git        | Version control          |
| GitHub     | Source code hosting      |

## 📂 Project Structure

```text
Hospital-Appointment-Booking-System/
│
├── admin/
│   ├── add_doctor.php
│   ├── appointments.php
│   ├── dashboard.php
│   ├── delete_doctor.php
│   ├── doctors.php
│   └── edit_doctor.php
│
├── config/
│   └── database.php
│
├── database/
│   └── hospital.sql
│
├── doctor/
│   ├── appointments.php
│   ├── dashboard.php
│   ├── patient_details.php
│   └── update_appointment.php
│
├── images/
│   └── hospital.jpg
│
├── patient/
│   ├── book_appointment.php
│   ├── cancel_appointment.php
│   ├── dashboard.php
│   ├── doctor_details.php
│   ├── doctors.php
│   └── my_appointments.php
│
├── .gitignore
├── index.php
├── login.php
├── logout.php
└── register.php
```

## ⚙️ Installation and Setup

### Prerequisites

* XAMPP
* PHP
* MySQL
* Web browser
* Git (optional)

### Step 1: Clone the repository

```bash
git clone https://github.com/YOUR-USERNAME/Hospital-Appointment-Booking-System.git
```

### Step 2: Move the project

Place the cloned project inside the XAMPP `htdocs` directory.

```text
C:\xampp\htdocs\Hospital-Appointment-Booking-System
```

### Step 3: Start XAMPP

1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.

### Step 4: Create the database

1. Open `http://localhost/phpmyadmin/`.
2. Select the **Import** tab.
3. Choose the `database/hospital.sql` file.
4. Click **Import** or **Go** to execute the SQL file.

### Step 5: Configure the database

Open `config/database.php` and configure the database connection to match your local MySQL settings.

### Step 6: Run the application

Open the following URL in your browser:

```text
http://localhost/Hospital-Appointment-Booking-System/
```

## 🗄️ Database

The application uses MySQL for storing hospital-related information.

The SQL setup file is available at:

`database/hospital.sql`

Import this file before running the application.

## 🖥️ Local Development

This project is configured for local development using XAMPP. Apache serves the PHP pages, while MySQL stores the application's data.

## 🚀 Future Enhancements

* Email notifications for appointments
* Appointment reminders
* Online payment integration
* Doctor availability calendar
* Improved mobile responsiveness
* Enhanced security and access controls

## 🎯 Project Objective

To develop a web-based application that simplifies hospital appointment booking and management through dedicated patient, doctor and administrator interfaces.

## 👩‍💻 Author

**Joanna T Maria**

CSE Student | Full Stack Development Enthusiast

## 📄 License

This project is available for learning and educational purposes. No formal open-source license has been specified yet.
