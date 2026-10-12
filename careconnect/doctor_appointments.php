
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

// Update appointment status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {

    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    $allowed_statuses = [
        'Pending',
        'Confirmed',
        'Completed',
        'Cancelled'
    ];

    if ($appointment_id > 0 && in_array($new_status, $allowed_statuses, true)) {

        $sql = "UPDATE appointments
                SET status = ?
                WHERE appointment_id = ?
                AND doctor_name = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $new_status, $appointment_id, $doctor_name);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $message = "Appointment status updated.";
        } else {
            $message = "Unable to update appointment.";
        }

        $stmt->close();
    } else {
        $message = "Invalid status.";
    }
}

// Get appointments assigned by matching doctor name
$sql = "SELECT appointment_id, patient_id, patient_name,
               hospital_name, specialization, doctor_name,
               appointment_date, appointment_time, reason, status
        FROM appointments
        WHERE doctor_name = ?
        ORDER BY appointment_date DESC, appointment_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctor_name);
$stmt->execute();

$result = $stmt->get_result();

$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$stmt->close();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CareConnect - Appointment Management</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f2f7fb;
    color: #263746;
}

.sidebar {
    width: 240px;
    height: 100vh;
    background: #123c55;
    color: white;
    position: fixed;
    padding: 25px 15px;
    overflow-y: auto;
}

.logo {
    font-size: 25px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 35px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px;
    margin: 7px 0;
    border-radius: 7px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #1e617d;
}

.main {
    margin-left: 240px;
    padding: 30px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.header h1 {
    color: #123c55;
    font-size: 27px;
}

.back {
    text-decoration: none;
    color: #087bb5;
    font-weight: bold;
}

.description {
    color: #687987;
    margin-bottom: 25px;
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px #0000000a;
}

select, input {
    padding: 11px;
    border: 1px solid #ccd9e2;
    border-radius: 7px;
    background: white;
    outline: none;
}

.table-box {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000a;
    overflow-x: auto;
}

.table-box h2 {
    color: #123c55;
    margin-bottom: 20px;
    font-size: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #eaf3f8;
    color: #244d64;
    text-align: left;
    padding: 14px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #e7edf1;
}

.status {
    padding: 7px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.pending {
    background: #fff3d6;
    color: #916300;
}

.confirmed {
    background: #e2f5e9;
    color: #237744;
}

.completed {
    background: #e5efff;
    color: #285baf;
}

.cancelled {
    background: #ffe8e8;
    color: #b33b3b;
}

.action {
    padding: 8px 10px;
    border: none;
    border-radius: 6px;
    background: #087eae;
    color: white;
    cursor: pointer;
    margin-top: 8px;
}

.empty {
    text-align: center;
    padding: 45px 10px;
    color: #71818d;
}

.empty-icon {
    font-size: 38px;
    margin-bottom: 12px;
}

.note {
    margin-top: 20px;
    font-size: 13px;
    color: #71818d;
}

.message {
    background: #e2f5e9;
    color: #237744;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

@media (max-width: 700px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .main {
        margin-left: 0;
        padding: 18px;
    }

    .header {
        align-items: flex-start;
        gap: 12px;
        flex-direction: column;
    }
}
</style>
</head>

<body>

<div class="sidebar">
    <div class="logo">CareConnect</div>

    <a href="doctor_profile.php">Dashboard</a>
    <a href="docprofile.html">My Profile</a>
    <a href="doctor_appointments.php" class="active">Appointments</a>
    <a href="doctor_patients.html">Patients</a>
    <a href="doctor_reports.html">Medical Reports</a>
    <a href="doctor_consultation.html">Online Consultation</a>
    <a href="doctor_prescription.html">Prescriptions</a>
    <a href="doctor_followup.html">Follow-ups</a>
    <a href="doctor_logout.php">Logout</a>
</div>

<div class="main">

    <div class="header">
        <h1>Appointment Management</h1>
        <a href="doctor_profile.php" class="back">← Back to Dashboard</a>
    </div>

    <p class="description">
        Welcome, <?php echo e($doctor_name); ?>.
        View and manage your patient appointments.
    </p>

    <?php if ($message !== ''): ?>
        <div class="message"><?php echo e($message); ?></div>
    <?php endif; ?>

    <div class="filters">

        <input type="date" id="dateFilter">

        <select id="typeFilter">
            <option value="All">All Appointment Types</option>
            <option value="Hospital">Hospital</option>
        </select>

        <select id="statusFilter">
            <option value="All">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>

    </div>

    <div class="table-box">

        <h2>Patient Appointments</h2>

        <table>
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Hospital</th>
                    <th>Specialization</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="appointmentTable">

            <?php foreach ($appointments as $appointment): ?>

                <tr
                    data-date="<?php echo e($appointment['appointment_date']); ?>"
                    data-type="Hospital"
                    data-status="<?php echo e($appointment['status']); ?>"
                >

                    <td><?php echo e($appointment['patient_id']); ?></td>

                    <td><?php echo e($appointment['patient_name']); ?></td>

                    <td><?php echo e($appointment['hospital_name']); ?></td>

                    <td><?php echo e($appointment['specialization']); ?></td>

                    <td>Hospital</td>

                    <td><?php echo e($appointment['appointment_date']); ?></td>

                    <td><?php echo e($appointment['appointment_time']); ?></td>

                    <td><?php echo e($appointment['reason']); ?></td>

                    <td>
                        <span class="status <?php echo strtolower(e($appointment['status'])); ?>">
                            <?php echo e($appointment['status']); ?>
                        </span>
                    </td>

                    <td>
                        <form method="POST" action="doctor_appointments.php">

                            <input type="hidden"
                                   name="appointment_id"
                                   value="<?php echo e($appointment['appointment_id']); ?>">

                            <select name="new_status" required>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>

                            <button type="submit"
                                    name="update_status"
                                    class="action">
                                Update
                            </button>

                        </form>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

        <div class="empty" id="emptyMessage"
             style="<?php echo count($appointments) > 0 ? 'display:none;' : 'display:block;'; ?>">

            <div class="empty-icon">📅</div>
            <h3>No Appointments Found</h3>
            <p>Appointments assigned to you will appear here.</p>

        </div>

    </div>

    <p class="note">
        Appointment records are shown based on the logged-in doctor's name.
    </p>

</div>

<script>
const rows = document.querySelectorAll("#appointmentTable tr");

function displayAppointments() {

    const selectedDate = document.getElementById("dateFilter").value;
    const selectedType = document.getElementById("typeFilter").value;
    const selectedStatus = document.getElementById("statusFilter").value;

    let visibleCount = 0;

    rows.forEach(row => {

        const dateMatch =
            selectedDate === "" || row.dataset.date === selectedDate;

        const typeMatch =
            selectedType === "All" || row.dataset.type === selectedType;

        const statusMatch =
            selectedStatus === "All" || row.dataset.status === selectedStatus;

        if (dateMatch && typeMatch && statusMatch) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    document.getElementById("emptyMessage").style.display =
        visibleCount === 0 ? "block" : "none";
}

document.getElementById("dateFilter")
    .addEventListener("change", displayAppointments);

document.getElementById("typeFilter")
    .addEventListener("change", displayAppointments);

document.getElementById("statusFilter")
    .addEventListener("change", displayAppointments);

</script>

</body>
</html>