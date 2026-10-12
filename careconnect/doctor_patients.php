
<?php

session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctorlogin.php");
    exit();
}

include "db.php";

$logged_doctor_id = $_SESSION['doctor_id'];

/*
   Display only patients whose health issues
   are assigned to the logged-in doctor.
*/

$sql = "SELECT
            p.patient_id,
            p.full_name,
            p.date_of_birth,
            p.gender,
            p.mobile,
            p.email,
            p.address,

            i.issue_id,
            i.symptoms,
            i.duration,
            i.severity,
            i.issue_description,
            i.hospital_name,
            i.created_at

        FROM patients p

        INNER JOIN patient_issues i
        ON i.issue_id = (
            SELECT MAX(i2.issue_id)
            FROM patient_issues i2
            WHERE i2.patient_id = p.patient_id
            AND i2.doctor_id = ?
        )

        WHERE i.issue_id IS NOT NULL

        ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("s", $logged_doctor_id);

$stmt->execute();

$result = $stmt->get_result();

$patients = [];

while ($row = $result->fetch_assoc()) {

    $row['age'] = '';

    if (!empty($row['date_of_birth'])) {
        $dob = new DateTime($row['date_of_birth']);
        $today = new DateTime();
        $row['age'] = $today->diff($dob)->y;
    }

    $patients[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CareConnect - Patient Details</title>

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
    margin-bottom: 15px;
}

.header h1 {
    color: #123c55;
    font-size: 27px;
}

.back {
    color: #087bb5;
    text-decoration: none;
    font-weight: bold;
}

.description {
    color: #71818d;
    margin-bottom: 25px;
}

.search-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px #0000000a;
}

.search-box label {
    display: block;
    font-weight: bold;
    margin-bottom: 10px;
    color: #34566b;
}

.search-box input {
    width: 100%;
    padding: 13px;
    border: 1px solid #ccd9e2;
    border-radius: 7px;
    outline: none;
}

.search-box input:focus {
    border-color: #1684b5;
}

.patient-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000a;
}

.patient-card h2 {
    color: #123c55;
    margin-bottom: 20px;
    font-size: 20px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    text-align: left;
    background: #eaf3f8;
    color: #244d64;
    padding: 14px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #e7edf1;
}

.view-btn {
    background: #087eae;
    color: white;
    border: none;
    padding: 9px 16px;
    border-radius: 6px;
    cursor: pointer;
}

.view-btn:hover {
    background: #05658e;
}

.details-btn {
    display: block;
    margin-top: 6px;
    background: #eaf3f8;
    color: #087eae;
    border: 1px solid #087eae;
    padding: 6px 13px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.details-btn:hover {
    background: #d8edf7;
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

.details {
    display: none;
    margin-top: 25px;
    padding: 28px;
    background: #f7fafc;
    border: 1px solid #dce8ef;
    border-radius: 10px;
}

.details h2 {
    color: #123c55;
    margin-bottom: 20px;
}

.section-title {
    grid-column: 1 / -1;
    color: #087eae;
    font-size: 17px;
    margin: 20px 0 5px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dce8ef;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    color: #526c7e;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
}

.detail-item input,
.detail-item textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd9e2;
    border-radius: 6px;
    font-size: 14px;
    color: #263746;
    background: white;
}

.detail-item input[readonly],
.detail-item textarea[readonly] {
    background: #edf4f8;
    color: #40596b;
}

.detail-item textarea {
    min-height: 90px;
    resize: vertical;
}

.full {
    grid-column: 1 / -1;
}

.close-btn {
    margin-top: 22px;
    padding: 11px 20px;
    background: #e8edf1;
    color: #344b5a;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.note {
    margin-top: 20px;
    color: #71818d;
    font-size: 13px;
}

@media(max-width: 700px) {

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
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .section-title,
    .full {
        grid-column: 1;
    }
}

</style>
</head>

<body>

<div class="sidebar">

    <div class="logo">CareConnect</div>

    <a href="doctor_profile.php">Dashboard</a>
    <a href="docprofile.html">My Profile</a>
    <a href="doctor_appointments.php">Appointments</a>
    <a href="doctor_patients.php" class="active">Patients</a>
    <a href="doctor_reports.html">Medical Reports</a>
    <a href="doctor_consultation.html">Online Consultation</a>
    <a href="doctor_prescription.html">Prescriptions</a>
    <a href="doctor_followup.html">Follow-ups</a>
    <a href="doctorlogout.php">Logout</a>

</div>

<div class="main">

    <div class="header">

        <h1>Patient Details</h1>

        <a href="doctor_profile.php" class="back">
            ← Back to Dashboard
        </a>

    </div>

    <p class="description">
        View patient information, current health issues and appointment details.
    </p>

    <div class="search-box">

        <label for="searchInput">Search Patient</label>

        <input
            type="text"
            id="searchInput"
            placeholder="Enter Patient ID or Name"
            oninput="searchPatient()"
        >

    </div>

    <div class="patient-card">

        <h2>Registered Patients</h2>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Mobile</th>
                        <th>View</th>
                    </tr>
                </thead>

                <tbody id="patientTable"></tbody>

            </table>

        </div>

        <div class="empty" id="emptyMessage">

            <div class="empty-icon">👤</div>

            <h3>No Patient Records Found</h3>

            <p>Patients assigned to you will appear here.</p>

        </div>

        <div class="details" id="patientDetails">

            <h2>Patient Personal Details</h2>

            <div class="details-grid">

                <div class="detail-item">
                    <label>Patient ID</label>
                    <input id="dId" readonly>
                </div>

                <div class="detail-item">
                    <label>Patient Name</label>
                    <input id="dName" readonly>
                </div>

                <div class="detail-item">
                    <label>Age</label>
                    <input id="dAge" readonly>
                </div>

                <div class="detail-item">
                    <label>Gender</label>
                    <input id="dGender" readonly>
                </div>

                <div class="detail-item">
                    <label>Mobile Number</label>
                    <input id="dMobile" readonly>
                </div>

                <div class="detail-item">
                    <label>Email</label>
                    <input id="dEmail" readonly>
                </div>

                <div class="detail-item full">
                    <label>Address</label>
                    <textarea id="dAddress" readonly></textarea>
                </div>

            </div>

            <button class="close-btn"
                    onclick="closeDetails('patientDetails')">
                Close
            </button>

        </div>

        <div class="details" id="issueDetails">

            <h2>Patient Current Health Issue</h2>

            <div class="details-grid">

                <h3 class="section-title">Patient Information</h3>

                <div class="detail-item">
                    <label>Patient ID</label>
                    <input id="iPatientId" readonly>
                </div>

                <div class="detail-item">
                    <label>Patient Name</label>
                    <input id="iPatientName" readonly>
                </div>

                <h3 class="section-title">Submitted Health Issue</h3>

                <div class="detail-item full">
                    <label>Hospital Name</label>
                    <input id="iHospital" readonly>
                </div>

                <div class="detail-item full">
                    <label>Current Symptoms</label>
                    <textarea id="iSymptoms" readonly></textarea>
                </div>

                <div class="detail-item">
                    <label>Duration of Symptoms</label>
                    <input id="iDuration" readonly>
                </div>

                <div class="detail-item">
                    <label>Severity of Problem</label>
                    <input id="iSeverity" readonly>
                </div>

                <div class="detail-item full">
                    <label>Additional Notes</label>
                    <textarea id="iDescription" readonly></textarea>
                </div>

                <div class="detail-item full">
                    <label>Submitted Date</label>
                    <input id="iDate" readonly>
                </div>

            </div>

            <button class="close-btn"
                    onclick="closeDetails('issueDetails')">
                Close
            </button>

        </div>

    </div>

    <p class="note">
        Patient information must be accessed only by authorized doctors
        with appropriate patient permission.
    </p>

</div>

<script>

const patients = <?php
echo json_encode(
    $patients,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);
?>;

function searchPatient() {

    const searchValue = document
        .getElementById("searchInput")
        .value.toLowerCase()
        .trim();

    const table = document.getElementById("patientTable");

    const emptyMessage = document.getElementById("emptyMessage");

    table.innerHTML = "";

    document.getElementById("patientDetails").style.display = "none";
    document.getElementById("issueDetails").style.display = "none";

    const filteredPatients = patients.filter(patient => {

        const id = String(patient.patient_id || "").toLowerCase();

        const name = String(patient.full_name || "").toLowerCase();

        return id.includes(searchValue) ||
               name.includes(searchValue);

    });

    if (filteredPatients.length === 0) {

        emptyMessage.style.display = "block";
        return;

    }

    emptyMessage.style.display = "none";

    filteredPatients.forEach(patient => {

        const row = document.createElement("tr");

        const values = [
            patient.patient_id,
            patient.full_name,
            patient.age,
            patient.gender,
            patient.mobile
        ];

        values.forEach(value => {

            const cell = document.createElement("td");

            cell.textContent = value || "-";

            row.appendChild(cell);

        });

        const actionCell = document.createElement("td");

        const viewButton = document.createElement("button");

        viewButton.textContent = "View";
        viewButton.className = "view-btn";

        viewButton.onclick = function() {
            viewPatient(patient);
        };

        const detailsButton = document.createElement("button");

        detailsButton.textContent = "Details";
        detailsButton.className = "details-btn";

        detailsButton.onclick = function() {
            viewIssue(patient);
        };

        actionCell.appendChild(viewButton);
        actionCell.appendChild(detailsButton);

        row.appendChild(actionCell);

        table.appendChild(row);

    });

}

function viewPatient(patient) {

    document.getElementById("dId").value =
        patient.patient_id || "";

    document.getElementById("dName").value =
        patient.full_name || "";

    document.getElementById("dAge").value =
        patient.age || "";

    document.getElementById("dGender").value =
        patient.gender || "";

    document.getElementById("dMobile").value =
        patient.mobile || "";

    document.getElementById("dEmail").value =
        patient.email || "";

    document.getElementById("dAddress").value =
        patient.address || "";

    document.getElementById("issueDetails").style.display = "none";

    document.getElementById("patientDetails").style.display = "block";

    document.getElementById("patientDetails").scrollIntoView({
        behavior: "smooth"
    });

}

function viewIssue(patient) {

    if (!patient.issue_id) {

        alert("This patient has not submitted a health issue yet.");

        return;

    }

    document.getElementById("iPatientId").value =
        patient.patient_id || "";

    document.getElementById("iPatientName").value =
        patient.full_name || "";

    document.getElementById("iHospital").value =
        patient.hospital_name || "";

    document.getElementById("iSymptoms").value =
        patient.symptoms || "";

    document.getElementById("iDuration").value =
        patient.duration || "";

    document.getElementById("iSeverity").value =
        patient.severity || "";

    document.getElementById("iDescription").value =
        patient.issue_description || "";

    document.getElementById("iDate").value =
        patient.created_at || "";

    document.getElementById("patientDetails").style.display = "none";

    document.getElementById("issueDetails").style.display = "block";

    document.getElementById("issueDetails").scrollIntoView({
        behavior: "smooth"
    });

}

function closeDetails(id) {

    document.getElementById(id).style.display = "none";

}

searchPatient();

</script>

</body>
</html>