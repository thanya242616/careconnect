<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctorlogin.html");
    exit();
}

include "db.php";

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'] ?? '';

$message = "";
$message_type = "";

/* =========================================================
   ACCEPT / REJECT / START CONSULTATION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $consultation_id = intval($_POST['consultation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    /* ---------- ACCEPT ---------- */
    if ($action == "accept") {

        $doctor_response =
            "Your online consultation request has been accepted by Dr. "
            . $doctor_name . ".";

        $sql = "UPDATE consultations
                SET status = 'Accepted',
                    doctor_response = ?
                WHERE consultation_id = ?
                AND doctor_name = ?
                AND status = 'Pending'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sis",
            $doctor_response,
            $consultation_id,
            $doctor_name
        );

        if ($stmt->execute()) {
            $message = "Consultation accepted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to accept consultation.";
            $message_type = "error";
        }

        $stmt->close();
    }


    /* ---------- REJECT ---------- */
    elseif ($action == "reject") {

        $doctor_response =
            "Your online consultation request has been rejected by Dr. "
            . $doctor_name . ".";

        $sql = "UPDATE consultations
                SET status = 'Rejected',
                    doctor_response = ?
                WHERE consultation_id = ?
                AND doctor_name = ?
                AND status = 'Pending'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sis",
            $doctor_response,
            $consultation_id,
            $doctor_name
        );

        if ($stmt->execute()) {
            $message = "Consultation rejected.";
            $message_type = "success";
        } else {
            $message = "Unable to reject consultation.";
            $message_type = "error";
        }

        $stmt->close();
    }


    /* ---------- START CONSULTATION ---------- */
    elseif ($action == "start") {

        /*
         * First get the consultation details
         */
        $sql = "SELECT consultation_date, consultation_time, status
                FROM consultations
                WHERE consultation_id = ?
                AND doctor_name = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "is",
            $consultation_id,
            $doctor_name
        );
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $consultation = $result->fetch_assoc();

            $status = $consultation['status'];

            $date = $consultation['consultation_date'];
            $time = $consultation['consultation_time'];

            /*
             * Convert appointment time
             */
            $appointment_datetime = new DateTime(
                $date . " " . $time,
                new DateTimeZone("Asia/Kolkata")
            );

            $current_datetime = new DateTime(
                "now",
                new DateTimeZone("Asia/Kolkata")
            );

            /*
             * Doctor can start only if:
             * 1. Consultation is Accepted
             * 2. Scheduled time has arrived
             */

            if ($status != "Accepted") {

                $message = "Only accepted consultations can be started.";
                $message_type = "error";

            } elseif ($current_datetime < $appointment_datetime) {

                $message = "Consultation can be started only at the scheduled date and time.";
                $message_type = "error";

            } else {

                $update_sql = "UPDATE consultations
                               SET status = 'In Progress'
                               WHERE consultation_id = ?
                               AND doctor_name = ?
                               AND status = 'Accepted'";

                $update_stmt = $conn->prepare($update_sql);

                $update_stmt->bind_param(
                    "is",
                    $consultation_id,
                    $doctor_name
                );

                if ($update_stmt->execute()) {

                    /*
                     * Open video consultation room
                     */
                    header(
                        "Location: video_consultation.php?consultation_id="
                        . $consultation_id
                    );
                    exit();

                } else {

                    $message = "Unable to start consultation.";
                    $message_type = "error";
                }

                $update_stmt->close();
            }

        } else {

            $message = "Consultation not found.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================================================
   GET DOCTOR CONSULTATIONS
   ========================================================= */

$sql = "SELECT *
        FROM consultations
        WHERE doctor_name = ?
        ORDER BY
        CASE
            WHEN status = 'Pending' THEN 1
            WHEN status = 'Accepted' THEN 2
            WHEN status = 'In Progress' THEN 3
            WHEN status = 'Completed' THEN 4
            WHEN status = 'Rejected' THEN 5
            ELSE 6
        END,
        consultation_date ASC,
        consultation_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctor_name);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CareConnect - Online Consultations</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f2f7fb;
    color: #263238;
}

/* ================= SIDEBAR ================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 240px;
    height: 100vh;

    background: #073b4c;
    color: white;

    padding: 25px 15px;
}

.logo {
    text-align: center;
    font-size: 24px;
    font-weight: bold;

    margin-bottom: 35px;
}

.logo span {
    color: #6ee7b7;
}

.sidebar a {
    display: block;

    text-decoration: none;
    color: white;

    padding: 13px 15px;

    margin: 7px 0;

    border-radius: 8px;

    font-size: 15px;
}

.sidebar a:hover {
    background: #0b5368;
}

.sidebar a.active {
    background: #0b5368;
}

/* ================= MAIN ================= */

.main {
    margin-left: 240px;
    padding: 30px;
}

/* ================= TOPBAR ================= */

.topbar {
    background: white;

    padding: 18px 25px;

    border-radius: 12px;

    display: flex;

    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;

    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
}

.topbar h2 {
    margin: 0;

    color: #073b4c;
}

.doctor-info {
    font-size: 14px;
    color: #607d8b;
}

/* ================= HERO ================= */

.hero {
    background: linear-gradient(
        135deg,
        #dff6ff,
        #eefaff
    );

    padding: 28px;

    border-radius: 15px;

    margin-bottom: 25px;
}

.hero h1 {
    margin: 0 0 8px;

    color: #073b4c;
}

.hero p {
    margin: 0;

    color: #546e7a;
}

/* ================= MESSAGE ================= */

.message {
    padding: 14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-weight: bold;
}

.success {
    background: #d1fae5;
    color: #065f46;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

/* ================= FILTER ================= */

.filters {
    display: flex;
    gap: 10px;

    margin-bottom: 20px;

    flex-wrap: wrap;
}

.filter-btn {
    border: none;

    padding: 10px 18px;

    border-radius: 20px;

    background: white;

    color: #073b4c;

    cursor: pointer;

    font-weight: bold;
}

.filter-btn:hover {
    background: #dff6ff;
}

/* ================= CONSULTATION CARD ================= */

.consultation-card {
    background: white;

    border-radius: 14px;

    padding: 24px;

    margin-bottom: 20px;

    box-shadow: 0 3px 12px rgba(0,0,0,0.06);

    border-left: 5px solid #0b7285;
}

.card-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 18px;

    flex-wrap: wrap;

    gap: 10px;
}

.patient-name {
    font-size: 20px;

    font-weight: bold;

    color: #073b4c;
}

/* ================= STATUS ================= */

.status {
    padding: 7px 14px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-accepted {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-progress {
    background: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-completed {
    background: #e5e7eb;
    color: #374151;
}

/* ================= DETAILS ================= */

.details {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(200px, 1fr));

    gap: 15px;

    margin-bottom: 18px;
}

.detail-box {
    background: #f8fbfd;

    padding: 13px;

    border-radius: 8px;
}

.detail-label {
    font-size: 12px;

    color: #78909c;

    margin-bottom: 5px;
}

.detail-value {
    font-weight: bold;

    color: #37474f;
}

/* ================= SYMPTOMS ================= */

.symptoms {
    background: #f8fbfd;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 18px;
}

.symptoms strong {
    display: block;

    margin-bottom: 7px;

    color: #073b4c;
}

/* ================= RESPONSE ================= */

.response {
    background: #eefaf5;

    padding: 14px;

    border-radius: 8px;

    margin-bottom: 18px;

    color: #065f46;
}

/* ================= BUTTONS ================= */

.actions {
    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.btn {
    border: none;

    padding: 11px 18px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: bold;

    font-size: 14px;
}

.btn-accept {
    background: #198754;
    color: white;
}

.btn-reject {
    background: #dc3545;
    color: white;
}

.btn-start {
    background: #0b7285;
    color: white;
}

.btn-continue {
    background: #087f5b;
    color: white;
}

.btn-disabled {
    background: #e0e0e0;
    color: #757575;

    cursor: not-allowed;
}

.btn:hover {
    opacity: 0.9;
}

/* ================= START TIME ================= */

.start-info {
    background: #fff8e1;

    color: #795548;

    padding: 12px 15px;

    border-radius: 8px;

    margin-bottom: 15px;

    font-size: 14px;
}

/* ================= EMPTY ================= */

.empty {
    background: white;

    padding: 50px;

    border-radius: 14px;

    text-align: center;

    color: #78909c;
}

/* ================= RESPONSIVE ================= */

@media (max-width: 800px) {

    .sidebar {
        width: 100%;

        height: auto;

        position: relative;
    }

    .main {
        margin-left: 0;
    }

    .sidebar a {
        display: inline-block;
    }

}

</style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="logo">
        ❤️ Care<span>Connect</span>
    </div>

    <a href="doctor_profile.php">
        🏠 Dashboard
    </a>

    <a href="doctor_appointments.php">
        📅 Appointments
    </a>

    <a href="doctor_consultation.php"
       class="active">
        💻 Online Consultations
    </a>

    <a href="doctor_reports.php">
        📄 Medical Reports
    </a>

    <a href="doctor_prescription.php">
        💊 Prescriptions
    </a>

    <a href="doctor_logout.php">
        🚪 Logout
    </a>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h2>
            Online Consultations
        </h2>

        <div class="doctor-info">

            👨‍⚕️ Dr.
            <?php echo htmlspecialchars($doctor_name); ?>

        </div>

    </div>


    <!-- HERO -->

    <div class="hero">

        <h1>
            Online Consultation Requests
        </h1>

        <p>
            Review patient requests, accept or reject them,
            and start the consultation at the scheduled time.
        </p>

    </div>


    <!-- MESSAGE -->

    <?php if ($message != "") { ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>


    <!-- FILTERS -->

    <div class="filters">

        <button class="filter-btn"
                onclick="filterConsultations('all')">
            All
        </button>

        <button class="filter-btn"
                onclick="filterConsultations('Pending')">
            Pending
        </button>

        <button class="filter-btn"
                onclick="filterConsultations('Accepted')">
            Accepted
        </button>

        <button class="filter-btn"
                onclick="filterConsultations('In Progress')">
            In Progress
        </button>

        <button class="filter-btn"
                onclick="filterConsultations('Completed')">
            Completed
        </button>

    </div>


<?php

if ($result->num_rows == 0) {

?>

    <div class="empty">

        <h3>
            No consultation requests
        </h3>

        <p>
            Patient online consultation requests will appear here.
        </p>

    </div>

<?php

} else {

    while ($row = $result->fetch_assoc()) {

        $status = $row['status'];

        $status_class = "";

        if ($status == "Pending") {
            $status_class = "status-pending";
        } elseif ($status == "Accepted") {
            $status_class = "status-accepted";
        } elseif ($status == "In Progress") {
            $status_class = "status-progress";
        } elseif ($status == "Rejected") {
            $status_class = "status-rejected";
        } elseif ($status == "Completed") {
            $status_class = "status-completed";
        }


        /*
         * Check scheduled time
         */

        $appointment_datetime = new DateTime(
            $row['consultation_date'] . " " .
            $row['consultation_time'],
            new DateTimeZone("Asia/Kolkata")
        );

        $current_datetime = new DateTime(
            "now",
            new DateTimeZone("Asia/Kolkata")
        );

        $appointment_reached =
            ($current_datetime >= $appointment_datetime);

?>

<div class="consultation-card"
     data-status="<?php echo htmlspecialchars($status); ?>">


    <!-- HEADER -->

    <div class="card-header">

        <div class="patient-name">

            👤
            <?php
            echo htmlspecialchars(
                $row['patient_name']
            );
            ?>

        </div>

        <div class="status <?php echo $status_class; ?>">

            <?php
            echo htmlspecialchars($status);
            ?>

        </div>

    </div>


    <!-- DETAILS -->

    <div class="details">


        <div class="detail-box">

            <div class="detail-label">
                Patient ID
            </div>

            <div class="detail-value">

                <?php
                echo htmlspecialchars(
                    $row['patient_id']
                );
                ?>

            </div>

        </div>


        <div class="detail-box">

            <div class="detail-label">
                Hospital
            </div>

            <div class="detail-value">

                <?php
                echo htmlspecialchars(
                    $row['hospital_name']
                );
                ?>

            </div>

        </div>


        <div class="detail-box">

            <div class="detail-label">
                Consultation Date
            </div>

            <div class="detail-value">

                <?php
                echo htmlspecialchars(
                    $row['consultation_date']
                );
                ?>

            </div>

        </div>


        <div class="detail-box">

            <div class="detail-label">
                Consultation Time
            </div>

            <div class="detail-value">

                <?php
                echo htmlspecialchars(
                    $row['consultation_time']
                );
                ?>

            </div>

        </div>

    </div>


    <!-- SYMPTOMS -->

    <div class="symptoms">

        <strong>
            Patient Symptoms
        </strong>

        <?php
        echo nl2br(
            htmlspecialchars(
                $row['symptoms']
            )
        );
        ?>

    </div>


    <!-- DOCTOR RESPONSE -->

    <?php

    if (!empty($row['doctor_response'])) {

    ?>

        <div class="response">

            <strong>
                Doctor Response:
            </strong>

            <br>

            <?php
            echo nl2br(
                htmlspecialchars(
                    $row['doctor_response']
                )
            );
            ?>

        </div>

    <?php

    }

    ?>


    <!-- ACTIONS -->

    <div class="actions">


    <?php

    /* =====================================================
       PENDING
    ===================================================== */

    if ($status == "Pending") {

    ?>

        <form method="POST">

            <input type="hidden"
                   name="consultation_id"
                   value="<?php
                   echo $row['consultation_id'];
                   ?>">

            <input type="hidden"
                   name="action"
                   value="accept">

            <button type="submit"
                    class="btn btn-accept">

                ✓ Accept

            </button>

        </form>


        <form method="POST">

            <input type="hidden"
                   name="consultation_id"
                   value="<?php
                   echo $row['consultation_id'];
                   ?>">

            <input type="hidden"
                   name="action"
                   value="reject">

            <button type="submit"
                    class="btn btn-reject">

                ✕ Reject

            </button>

        </form>

    <?php

    }


    /* =====================================================
       ACCEPTED
    ===================================================== */

    elseif ($status == "Accepted") {

        if ($appointment_reached) {

    ?>

            <form method="POST">

                <input type="hidden"
                       name="consultation_id"
                       value="<?php
                       echo $row['consultation_id'];
                       ?>">

                <input type="hidden"
                       name="action"
                       value="start">

                <button type="submit"
                        class="btn btn-start">

                    📹 Start Consultation

                </button>

            </form>

    <?php

        } else {

    ?>

            <div class="start-info">

                🕐 Consultation starts at:

                <strong>

                    <?php
                    echo date(
                        "d M Y, h:i A",
                        $appointment_datetime->getTimestamp()
                    );
                    ?>

                </strong>

            </div>

    <?php

        }

    }


    /* =====================================================
       IN PROGRESS
    ===================================================== */

    elseif ($status == "In Progress") {

    ?>

        <a href="video_consultation.php?consultation_id=<?php
            echo $row['consultation_id'];
        ?>"
           class="btn btn-continue">

            📹 Continue Consultation

        </a>

    <?php

    }


    /* =====================================================
       COMPLETED
    ===================================================== */

    elseif ($status == "Completed") {

    ?>

        <div class="start-info">

            ✓ This consultation has been completed.

        </div>

    <?php

    }


    /* =====================================================
       REJECTED
    ===================================================== */

    elseif ($status == "Rejected") {

    ?>

        <div class="start-info">

            ✕ This consultation request was rejected.

        </div>

    <?php

    }

    ?>

    </div>

</div>

<?php

    }

}

$stmt->close();

?>

</div>


<script>

function filterConsultations(status) {

    const cards =
        document.querySelectorAll(
            ".consultation-card"
        );

    cards.forEach(function(card) {

        const cardStatus =
            card.getAttribute(
                "data-status"
            );

        if (
            status === "all" ||
            cardStatus === status
        ) {

            card.style.display = "block";

        } else {

            card.style.display = "none";

        }

    });

}

</script>

</body>

</html>