<?php

session_start();

/* ==============================
   PATIENT LOGIN CHECK
============================== */

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.html");
    exit();
}

include "db.php";


/* ==============================
   PATIENT DETAILS
============================== */

$patient_id = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'] ?? 'Patient';

$message = "";
$error = "";


/* ==============================
   BOOK CONSULTATION
============================== */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_consultation'])) {

    $hospital_name = trim($_POST['hospital_name']);
    $doctor_id = trim($_POST['doctor_id']);
    $doctor_name = trim($_POST['doctor_name']);
    $consultation_date = $_POST['consultation_date'];
    $consultation_time = $_POST['consultation_time'];
    $symptoms = trim($_POST['symptoms']);


    if (
        empty($hospital_name) ||
        empty($doctor_name) ||
        empty($consultation_date) ||
        empty($consultation_time) ||
        empty($symptoms)
    ) {

        $error = "Please fill all required fields.";

    } else {

        $sql = "INSERT INTO consultations
                (
                    patient_id,
                    patient_name,
                    hospital_name,
                    doctor_id,
                    doctor_name,
                    consultation_date,
                    consultation_time,
                    symptoms,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";


        $stmt = $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param(
                "isssssss",
                $patient_id,
                $patient_name,
                $hospital_name,
                $doctor_id,
                $doctor_name,
                $consultation_date,
                $consultation_time,
                $symptoms
            );


            if ($stmt->execute()) {

                $message =
                    "Your consultation request has been sent successfully.";

            } else {

                $error =
                    "Unable to book consultation. Please try again.";

            }


            $stmt->close();

        } else {

            $error =
                "Database error: " . $conn->error;

        }
    }
}


/* ==============================
   GET PATIENT CONSULTATIONS
============================== */

$sql = "SELECT *
        FROM consultations
        WHERE patient_id = ?
        ORDER BY created_at DESC";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $patient_id
);

$stmt->execute();

$result = $stmt->get_result();


$consultations = [];

$pending_count = 0;
$accepted_count = 0;
$rejected_count = 0;
$completed_count = 0;


while ($row = $result->fetch_assoc()) {

    $consultations[] = $row;


    if ($row['status'] == 'Pending') {
        $pending_count++;
    }

    if ($row['status'] == 'Accepted') {
        $accepted_count++;
    }

    if ($row['status'] == 'Rejected') {
        $rejected_count++;
    }

    if ($row['status'] == 'Completed') {
        $completed_count++;
    }
}


$stmt->close();


/* ==============================
   INDIA TIMEZONE
============================== */

date_default_timezone_set("Asia/Kolkata");

$current_timestamp = time();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CareConnect - Online Consultation</title>


<style>

/* =====================================
   GLOBAL
===================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f3f8fc;

    color: #183b56;
}


/* =====================================
   SIDEBAR
===================================== */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 255px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #0d3b59,
            #092d46
        );

    color: white;

    padding: 25px 15px;

    z-index: 100;
}


/* LOGO */

.logo {

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        5px
        12px
        35px;
}


.logo-icon {

    width: 48px;
    height: 48px;

    border-radius: 50%;

    background: #20aee5;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;
}


.logo h2 {

    margin: 0;

    font-size: 22px;
}


.logo p {

    margin: 3px 0 0;

    font-size: 11px;

    color: #b7d7e8;
}


/* NAV */

.nav-item {

    display: flex;

    align-items: center;

    gap: 14px;

    padding: 14px 16px;

    margin: 5px 0;

    border-radius: 9px;

    color: #d5e7f0;

    text-decoration: none;

    font-size: 14px;

    transition: 0.2s;
}


.nav-item:hover {

    background:
        rgba(255,255,255,0.08);
}


.nav-item.active {

    background:
        linear-gradient(
            90deg,
            #1484bd,
            #166d9b
        );

    color: white;
}


.nav-icon {

    width: 25px;

    text-align: center;

    font-size: 18px;
}


/* SIDEBAR BOTTOM */

.sidebar-bottom {

    position: absolute;

    bottom: 25px;

    left: 20px;
    right: 20px;

    text-align: center;

    color: #a9c9da;

    font-size: 12px;
}


.health-icon {

    font-size: 48px;

    opacity: 0.4;

    margin-bottom: 7px;
}


/* =====================================
   MAIN
===================================== */

.main {

    margin-left: 255px;

    min-height: 100vh;
}


/* =====================================
   TOPBAR
===================================== */

.topbar {

    height: 74px;

    background: white;

    border-bottom:
        1px solid #e5edf2;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 32px;
}


.menu {

    font-size: 26px;

    color: #28718f;
}


.top-right {

    display: flex;

    align-items: center;

    gap: 25px;
}


.notification-icon {

    position: relative;

    font-size: 22px;

    color: #356d87;
}


.notification-badge {

    position: absolute;

    top: -8px;

    right: -9px;

    width: 19px;
    height: 19px;

    border-radius: 50%;

    background: #ef4d4d;

    color: white;

    font-size: 10px;

    display: flex;

    align-items: center;

    justify-content: center;
}


.patient-info {

    display: flex;

    align-items: center;

    gap: 11px;
}


.patient-avatar {

    width: 43px;
    height: 43px;

    border-radius: 50%;

    background: #e4f3fa;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}


.patient-name {

    font-size: 14px;

    font-weight: bold;

    color: #254961;
}


.patient-role {

    font-size: 11px;

    color: #8298a6;

    margin-top: 3px;
}


/* =====================================
   CONTENT
===================================== */

.content {

    padding: 25px 32px 40px;
}


/* =====================================
   HERO
===================================== */

.hero {

    background:
        linear-gradient(
            110deg,
            #e8f6ff,
            #d9effb
        );

    border-radius: 15px;

    padding: 28px 35px;

    min-height: 190px;

    position: relative;

    overflow: hidden;

    margin-bottom: 20px;
}


.hero-small {

    font-size: 12px;

    font-weight: bold;

    letter-spacing: 1px;

    color: #42758e;

    margin-bottom: 8px;
}


.hero h1 {

    margin: 0;

    font-size: 30px;

    color: #153e59;
}


.hero p {

    max-width: 560px;

    color: #54758a;

    font-size: 14px;

    line-height: 1.6;
}


.hero-icon {

    position: absolute;

    right: 55px;

    bottom: 10px;

    font-size: 120px;

    opacity: 0.17;
}


/* =====================================
   ALERT
===================================== */

.alert {

    padding: 14px 18px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-size: 14px;
}


.success {

    background: #ddf6e9;

    border: 1px solid #bce9d2;

    color: #157247;
}


.error {

    background: #ffe4e4;

    border: 1px solid #f4c2c2;

    color: #a52c2c;
}


/* =====================================
   TWO COLUMN
===================================== */

.grid {

    display: grid;

    grid-template-columns:
        1fr
        1.35fr;

    gap: 20px;

    align-items: start;
}


/* =====================================
   CARD
===================================== */

.card {

    background: white;

    border-radius: 14px;

    padding: 24px;

    box-shadow:
        0 4px 15px
        rgba(28,70,95,0.07);
}


.card-title {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;
}


.card-title-icon {

    width: 45px;
    height: 45px;

    border-radius: 50%;

    background: #e4f4fb;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}


.card-title h2 {

    margin: 0;

    font-size: 20px;

    color: #183f59;
}


.card-title p {

    margin: 4px 0 0;

    font-size: 12px;

    color: #8196a4;
}


/* =====================================
   FORM
===================================== */

label {

    display: block;

    margin-top: 15px;

    margin-bottom: 6px;

    font-size: 13px;

    font-weight: bold;

    color: #385d72;
}


input,
textarea {

    width: 100%;

    padding: 11px 12px;

    border:
        1px solid #d6e2e8;

    border-radius: 7px;

    font-size: 13px;

    outline: none;

    background: #fbfdfe;
}


input:focus,
textarea:focus {

    border-color: #2196d2;

    background: white;
}


textarea {

    min-height: 95px;

    resize: vertical;
}


.book-button {

    width: 100%;

    margin-top: 20px;

    padding: 13px;

    border: none;

    border-radius: 8px;

    background:
        linear-gradient(
            90deg,
            #168fd7,
            #087abd
        );

    color: white;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;
}


.book-button:hover {

    background:
        linear-gradient(
            90deg,
            #087abd,
            #05669e
        );
}


/* =====================================
   STAT CARDS
===================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 10px;

    margin-bottom: 18px;
}


.stat {

    background: #f5f9fc;

    border-radius: 9px;

    padding: 13px;

    text-align: center;
}


.stat-number {

    font-size: 21px;

    font-weight: bold;

    color: #176f9d;
}


.stat-label {

    font-size: 10px;

    color: #78909e;

    margin-top: 4px;
}


/* =====================================
   NOTIFICATION
===================================== */

.notification-card {

    border-radius: 10px;

    padding: 15px;

    margin-bottom: 15px;

    border-left: 4px solid;
}


.notification-card.accepted {

    background: #edfaf4;

    border-color: #16a875;
}


.notification-card.rejected {

    background: #fff0f0;

    border-color: #e64d4d;
}


.notification-card.pending {

    background: #fff9eb;

    border-color: #edae32;
}


.notification-title {

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 5px;
}


.notification-text {

    font-size: 12px;

    color: #647f8e;

    line-height: 1.5;
}


/* =====================================
   CONSULTATION
===================================== */

.consultation {

    border:
        1px solid #e3ebef;

    border-radius: 11px;

    padding: 17px;

    margin-bottom: 13px;

    background: #fff;
}


.consultation-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 10px;
}


.doctor-details {

    display: flex;

    gap: 11px;

    align-items: center;
}


.doctor-avatar {

    width: 44px;
    height: 44px;

    border-radius: 50%;

    background: #e5f4fc;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;
}


.doctor-name {

    font-weight: bold;

    color: #20465f;

    font-size: 14px;
}


.doctor-hospital {

    color: #8196a4;

    font-size: 11px;

    margin-top: 4px;
}


/* STATUS */

.status {

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}


.status-pending {

    color: #bd7600;

    background: #fff0d2;
}


.status-accepted {

    color: #147a50;

    background: #ddf6e9;
}


.status-rejected {

    color: #c23636;

    background: #ffe1e1;
}


.status-progress {

    color: #116fc2;

    background: #deefff;
}


.status-completed {

    color: #647783;

    background: #edf1f3;
}


/* DETAILS */

.details {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 8px;

    margin-top: 15px;

    padding-top: 14px;

    border-top:
        1px solid #edf2f5;
}


.detail {

    font-size: 12px;

    color: #617b8b;

    line-height: 1.6;
}


.detail strong {

    color: #385b70;
}


/* SYMPTOMS */

.symptoms-box {

    background: #f5f9fc;

    border-radius: 7px;

    padding: 10px;

    margin-top: 12px;

    font-size: 12px;

    color: #607b8c;

    line-height: 1.5;
}


/* =====================================
   ATTEND BUTTON
===================================== */

.attend-button {

    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 12px;

    margin-top: 14px;

    background:
        linear-gradient(
            90deg,
            #14a978,
            #0b9567
        );

    color: white;

    font-weight: bold;

    font-size: 13px;

    cursor: pointer;
}


.attend-button:hover {

    background:
        linear-gradient(
            90deg,
            #0a9565,
            #067c55
        );
}


.wait-button {

    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 11px;

    margin-top: 14px;

    background: #eef3f6;

    color: #8194a0;

    font-size: 12px;

    font-weight: bold;

    cursor: not-allowed;
}


/* =====================================
   RESPONSE
===================================== */

.doctor-response {

    margin-top: 12px;

    background: #f3f8fb;

    border-radius: 7px;

    padding: 10px;

    font-size: 12px;

    color: #527184;
}


/* =====================================
   EMPTY
===================================== */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #8298a5;
}


.empty-icon {

    font-size: 48px;

    opacity: 0.5;

    margin-bottom: 10px;
}


/* =====================================
   FOOTER
===================================== */

.footer {

    display: flex;

    justify-content: space-between;

    color: #8096a4;

    font-size: 11px;

    padding: 20px 5px;
}


/* =====================================
   RESPONSIVE
===================================== */

@media(max-width: 1050px) {

    .grid {

        grid-template-columns: 1fr;
    }

}


@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .sidebar-bottom {

        position: relative;

        left: auto;
        right: auto;
        bottom: auto;

        margin-top: 25px;
    }

    .main {

        margin-left: 0;
    }

    .hero-icon {

        display: none;
    }

    .content {

        padding: 15px;
    }

}


@media(max-width: 500px) {

    .details {

        grid-template-columns: 1fr;
    }

    .stats {

        grid-template-columns: 1fr;
    }

    .topbar {

        padding: 0 15px;
    }

    .patient-role {

        display: none;
    }

}

</style>

</head>


<body>


<!-- =====================================
     SIDEBAR
===================================== -->

<div class="sidebar">


    <div class="logo">

        <div class="logo-icon">
            ♥
        </div>

        <div>

            <h2>CareConnect</h2>

            <p>Your Health. Your Records. Your Control.</p>

        </div>

    </div>


    <a
        href="patient.php"
        class="nav-item">

        <span class="nav-icon">⌂</span>

        Dashboard

    </a>


    <a
        href="upload_report.php"
        class="nav-item">

        <span class="nav-icon">📄</span>

        Medical Reports

    </a>


    <a
        href="appointment.html"
        class="nav-item">

        <span class="nav-icon">📅</span>

        Appointments

    </a>


    <a
        href="online_consultation.php"
        class="nav-item active">

        <span class="nav-icon">📹</span>

        Online Consultation

    </a>


    <a
        href="prescription.php"
        class="nav-item">

        <span class="nav-icon">💊</span>

        My Prescription

    </a>


    <a
        href="logout.php"
        class="nav-item">

        <span class="nav-icon">↪</span>

        Logout

    </a>


    <div class="sidebar-bottom">

        <div class="health-icon">
            🩺
        </div>

        <div>
            Your Health Matters
        </div>

        <div>
            CareConnect
        </div>

    </div>

</div>



<!-- =====================================
     MAIN
===================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">


        <div class="menu">
            ☰
        </div>


        <div class="top-right">


            <div class="notification-icon">

                🔔

                <?php

                $notification_count =
                    $accepted_count +
                    $rejected_count;

                if ($notification_count > 0) {

                ?>

                    <span class="notification-badge">

                        <?php
                        echo $notification_count;
                        ?>

                    </span>

                <?php } ?>

            </div>


            <div class="patient-info">


                <div class="patient-avatar">
                    👤
                </div>


                <div>

                    <div class="patient-name">

                        <?php
                        echo htmlspecialchars(
                            $patient_name
                        );
                        ?>

                    </div>

                    <div class="patient-role">
                        Patient
                    </div>

                </div>


            </div>


        </div>


    </div>



    <!-- CONTENT -->

    <div class="content">


        <!-- HERO -->

        <div class="hero">


            <div class="hero-small">
                CARECONNECT
            </div>


            <h1>
                Online Consultation
            </h1>


            <p>
                Connect with your doctor from anywhere.
                Book your consultation, receive your doctor's
                response and attend your appointment securely
                online.
            </p>


            <div class="hero-icon">
                🩺
            </div>


        </div>



        <!-- ALERTS -->

        <?php if ($message != "") { ?>

            <div class="alert success">

                ✓
                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php } ?>


        <?php if ($error != "") { ?>

            <div class="alert error">

                ✕
                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php } ?>



        <!-- MAIN GRID -->

        <div class="grid">


            <!-- =================================
                 BOOK CONSULTATION
            ================================= -->

            <div class="card">


                <div class="card-title">

                    <div class="card-title-icon">
                        📅
                    </div>

                    <div>

                        <h2>
                            Book Consultation
                        </h2>

                        <p>
                            Request an online appointment
                        </p>

                    </div>

                </div>


                <form method="POST">


                    <label>
                        Patient Name
                    </label>

                    <input
                        type="text"
                        value="<?php
                        echo htmlspecialchars(
                            $patient_name
                        );
                        ?>"
                        readonly
                    >


                    <label>
                        Hospital Name *
                    </label>

                    <input
                        type="text"
                        name="hospital_name"
                        placeholder="Enter hospital name"
                        required
                    >


                    <label>
                        Doctor ID
                    </label>

                    <input
                        type="text"
                        name="doctor_id"
                        placeholder="Enter doctor ID"
                    >


                    <label>
                        Doctor Name *
                    </label>

                    <input
                        type="text"
                        name="doctor_name"
                        placeholder="Enter doctor name"
                        required
                    >


                    <label>
                        Consultation Date *
                    </label>

                    <input
                        type="date"
                        name="consultation_date"
                        min="<?php
                        echo date('Y-m-d');
                        ?>"
                        required
                    >


                    <label>
                        Consultation Time *
                    </label>

                    <input
                        type="time"
                        name="consultation_time"
                        required
                    >


                    <label>
                        Symptoms / Reason *
                    </label>

                    <textarea
                        name="symptoms"
                        placeholder="Describe your health problem..."
                        required
                    ></textarea>


                    <button
                        type="submit"
                        name="book_consultation"
                        class="book-button">

                        📹
                        Request Online Consultation

                    </button>


                </form>


            </div>



            <!-- =================================
                 RIGHT SIDE
            ================================= -->

            <div>


                <!-- STATS -->

                <div class="stats">


                    <div class="stat">

                        <div class="stat-number">

                            <?php
                            echo count($consultations);
                            ?>

                        </div>

                        <div class="stat-label">
                            Total Requests
                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-number">

                            <?php
                            echo $accepted_count;
                            ?>

                        </div>

                        <div class="stat-label">
                            Accepted
                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-number">

                            <?php
                            echo $pending_count;
                            ?>

                        </div>

                        <div class="stat-label">
                            Pending
                        </div>

                    </div>


                </div>



                <!-- NOTIFICATIONS -->

                <?php

                foreach ($consultations as $notification) {

                    if (
                        $notification['status'] == 'Accepted' ||
                        $notification['status'] == 'Rejected'
                    ) {

                ?>


                    <div class="notification-card
                        <?php
                        echo strtolower(
                            $notification['status']
                        );
                        ?>">


                        <div class="notification-title">


                            <?php

                            if (
                                $notification['status']
                                == 'Accepted'
                            ) {

                                echo "✓ Consultation Accepted";

                            } else {

                                echo "✕ Consultation Rejected";

                            }

                            ?>


                        </div>


                        <div class="notification-text">

                            Dr.
                            <?php
                            echo htmlspecialchars(
                                $notification['doctor_name']
                            );
                            ?>

                            has
                            <?php
                            echo strtolower(
                                $notification['status']
                            );
                            ?>

                            your online consultation request.

                            <br>

                            📅
                            <?php
                            echo htmlspecialchars(
                                $notification['consultation_date']
                            );
                            ?>

                            &nbsp;

                            🕐
                            <?php
                            echo htmlspecialchars(
                                $notification['consultation_time']
                            );
                            ?>


                        </div>


                    </div>


                <?php

                    }

                }

                ?>



                <!-- =================================
                     MY CONSULTATIONS
                ================================= -->

                <div class="card">


                    <div class="card-title">

                        <div class="card-title-icon">
                            📋
                        </div>

                        <div>

                            <h2>
                                My Consultations
                            </h2>

                            <p>
                                Track your online appointments
                            </p>

                        </div>

                    </div>



                    <?php if (count($consultations) > 0) { ?>


                        <?php foreach ($consultations as $row) { ?>


                            <div class="consultation">


                                <!-- TOP -->

                                <div class="consultation-top">


                                    <div class="doctor-details">


                                        <div class="doctor-avatar">
                                            👨‍⚕️
                                        </div>


                                        <div>

                                            <div class="doctor-name">

                                                Dr.
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['doctor_name']
                                                );
                                                ?>

                                            </div>


                                            <div class="doctor-hospital">

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['hospital_name']
                                                );
                                                ?>

                                            </div>

                                        </div>


                                    </div>



                                    <!-- STATUS -->

                                    <div>

                                        <?php

                                        if (
                                            $row['status']
                                            == 'Pending'
                                        ) {

                                            echo '
                                            <span class="
                                            status
                                            status-pending">
                                            Pending
                                            </span>';

                                        }

                                        elseif (
                                            $row['status']
                                            == 'Accepted'
                                        ) {

                                            echo '
                                            <span class="
                                            status
                                            status-accepted">
                                            Accepted
                                            </span>';

                                        }

                                        elseif (
                                            $row['status']
                                            == 'Rejected'
                                        ) {

                                            echo '
                                            <span class="
                                            status
                                            status-rejected">
                                            Rejected
                                            </span>';

                                        }

                                        elseif (
                                            $row['status']
                                            == 'In Progress'
                                        ) {

                                            echo '
                                            <span class="
                                            status
                                            status-progress">
                                            In Progress
                                            </span>';

                                        }

                                        elseif (
                                            $row['status']
                                            == 'Completed'
                                        ) {

                                            echo '
                                            <span class="
                                            status
                                            status-completed">
                                            Completed
                                            </span>';

                                        }

                                        ?>

                                    </div>


                                </div>



                                <!-- DETAILS -->

                                <div class="details">


                                    <div class="detail">

                                        <strong>
                                            📅 Date
                                        </strong>

                                        <br>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['consultation_date']
                                        );
                                        ?>

                                    </div>


                                    <div class="detail">

                                        <strong>
                                            🕐 Time
                                        </strong>

                                        <br>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['consultation_time']
                                        );
                                        ?>

                                    </div>


                                </div>



                                <!-- SYMPTOMS -->

                                <div class="symptoms-box">

                                    <strong>
                                        Symptoms:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['symptoms']
                                    );
                                    ?>

                                </div>



                                <!-- DOCTOR RESPONSE -->

                                <?php

                                if (
                                    !empty(
                                        $row['doctor_response']
                                    )
                                ) {

                                ?>

                                    <div class="doctor-response">

                                        <strong>
                                            Doctor Response:
                                        </strong>

                                        <br>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['doctor_response']
                                        );
                                        ?>

                                    </div>

                                <?php } ?>



                                <!-- =================================
                                     CONSULTATION ACTION
                                ================================= -->

                                <?php if ($row['status'] == 'In Progress') { ?>

                                    <!-- DOCTOR HAS STARTED THE CALL -->

                                    <form
                                        method="GET"
                                        action="video_consultation.php">

                                        <input
                                            type="hidden"
                                            name="consultation_id"
                                            value="<?php
                                            echo htmlspecialchars(
                                                $row['consultation_id']
                                            );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="attend-button">

                                            📹
                                            Attend Consultation

                                        </button>

                                    </form>

                                <?php } elseif ($row['status'] == 'Accepted') { ?>

                                    <!-- DOCTOR ACCEPTED BUT HAS NOT STARTED -->

                                    <button
                                        type="button"
                                        class="wait-button"
                                        disabled>

                                        ⏰
                                        Waiting for doctor to start the consultation
                                        <br>
                                        Scheduled:

                                        <?php
                                        $appointment_datetime =
                                            $row['consultation_date']
                                            . " "
                                            . $row['consultation_time'];

                                        $appointment_timestamp =
                                            strtotime(
                                                $appointment_datetime
                                            );

                                        if ($appointment_timestamp !== false) {
                                            echo date(
                                                "d M Y, h:i A",
                                                $appointment_timestamp
                                            );
                                        } else {
                                            echo htmlspecialchars(
                                                $row['consultation_date']
                                                . ' '
                                                . $row['consultation_time']
                                            );
                                        }
                                        ?>

                                    </button>

                                <?php } elseif ($row['status'] == 'Pending') { ?>

                                    <button
                                        type="button"
                                        class="wait-button"
                                        disabled>

                                        ⏳
                                        Waiting for doctor approval

                                    </button>

                                <?php } elseif ($row['status'] == 'Rejected') { ?>

                                    <button
                                        type="button"
                                        class="wait-button"
                                        disabled>

                                        ❌
                                        Consultation request rejected

                                    </button>

                                <?php } elseif ($row['status'] == 'Completed') { ?>

                                    <button
                                        type="button"
                                        class="wait-button"
                                        disabled>

                                        ✅
                                        Consultation completed

                                    </button>

                                <?php } ?>



                            </div>


                        <?php } ?>


                    <?php } else { ?>


                        <div class="empty">

                            <div class="empty-icon">
                                📹
                            </div>

                            <h3>
                                No Consultations Yet
                            </h3>

                            <p>
                                Book your first online consultation
                                with a doctor.
                            </p>

                        </div>


                    <?php } ?>


                </div>


            </div>


        </div>



        <!-- FOOTER -->

        <div class="footer">

            <div>
                CareConnect
                &nbsp; | &nbsp;
                Online Consultation
            </div>

            <div>
                Your Health. Your Records. Your Control.
            </div>

        </div>


    </div>


</div>


</body>

</html>