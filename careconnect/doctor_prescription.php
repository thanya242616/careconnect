
<?php
session_start();
include "db.php";

// Doctor login verification
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctorlogin.html");
    exit();
}

$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'] ?? "";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $patientId = filter_input(INPUT_POST, "patientId", FILTER_VALIDATE_INT);
    $age = filter_input(INPUT_POST, "age", FILTER_VALIDATE_INT);

    $gender = trim($_POST["gender"] ?? "");
    $date = $_POST["prescriptionDate"] ?? "";
    $consultationId = !empty($_POST["consultationId"])
        ? filter_var($_POST["consultationId"], FILTER_VALIDATE_INT)
        : null;

    $diagnosis = trim($_POST["diagnosis"] ?? "");
    $notes = trim($_POST["notes"] ?? "");
    $advice = trim($_POST["advice"] ?? "");
    $hospital = trim($_POST["hospital"] ?? "");

    $followupDate = !empty($_POST["followupDate"])
        ? $_POST["followupDate"]
        : null;

    $medicinesJson = $_POST["medicines_json"] ?? "";
    $medicines = json_decode($medicinesJson, true);

    if (
        !$patientId ||
        !$age ||
        !$gender ||
        !$diagnosis ||
        !is_array($medicines) ||
        count($medicines) === 0
    ) {

        $message = "Please enter valid prescription details.";

    } else {

        // Verify patient
        $patientQuery = $conn->prepare(
            "SELECT full_name FROM patients WHERE patient_id = ?"
        );

        $patientQuery->bind_param("i", $patientId);
        $patientQuery->execute();

        $patientResult = $patientQuery->get_result();
        $patient = $patientResult->fetch_assoc();

        if (!$patient) {

            $message = "Patient ID not found. Please check the ID.";

        } else {

            // Use registered patient name
            $patientName = $patient["full_name"];

            // Store medicine details as JSON
            $medicinesJson = json_encode(
                $medicines,
                JSON_UNESCAPED_UNICODE
            );

            $instructions = $advice;

            $stmt = $conn->prepare(
                "INSERT INTO prescriptions
                (
                    patient_id,
                    patient_name,
                    doctor_id,
                    doctor_name,
                    hospital_name,
                    diagnosis,
                    medicines,
                    instructions,
                    age,
                    gender,
                    consultation_id,
                    doctor_notes,
                    followup_date
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            // Correct: 13 type characters for 13 variables
            $stmt->bind_param(
                "isssssssisiss",
                $patientId,
                $patientName,
                $doctorId,
                $doctorName,
                $hospital,
                $diagnosis,
                $medicinesJson,
                $instructions,
                $age,
                $gender,
                $consultationId,
                $notes,
                $followupDate
            );

            if ($stmt->execute()) {

                $success = true;

                $message = "Prescription saved successfully for Patient ID: "
                    . $patientId;

            } else {

                $message = "Unable to save prescription. Please check database table structure.";

            }

            $stmt->close();
        }

        $patientQuery->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CareConnect - Digital Prescription</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f2f7fb;
    color: #263746;
}

header {
    background: #087f8c;
    color: white;
    padding: 20px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h2 {
    margin: 0;
}

header a {
    color: white;
    text-decoration: none;
}

.container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

h3 {
    color: #087f8c;
    border-bottom: 1px solid #e2e8ee;
    padding-bottom: 10px;
    margin-top: 25px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
    font-size: 14px;
}

input,
textarea,
select {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccd7df;
    border-radius: 6px;
    font-size: 14px;
    font-family: Arial, sans-serif;
}

textarea {
    resize: vertical;
    min-height: 75px;
}

.full {
    grid-column: 1 / -1;
}

.medicine {
    border: 1px solid #dce5eb;
    padding: 18px;
    border-radius: 8px;
    margin-bottom: 15px;
    background: #fbfdfe;
}

.medicine h4 {
    margin-top: 0;
    color: #087f8c;
}

button {
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin: 5px 5px 5px 0;
}

.add-btn {
    background: #e4f4f5;
    color: #087f8c;
}

.remove-btn {
    background: #ffe9e9;
    color: #c62828;
}

.save-btn {
    background: #087f8c;
    color: white;
}

.print-btn {
    background: #263746;
    color: white;
}

.back-btn {
    background: #e8eef2;
    color: #263746;
}

.actions {
    text-align: center;
    margin-top: 25px;
}

.preview {
    display: none;
    border: 1px solid #b9c8d1;
    padding: 30px;
    margin-top: 30px;
    background: white;
}

.preview h2 {
    color: #087f8c;
    text-align: center;
}

.preview table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.preview th,
.preview td {
    border: 1px solid #ccd7df;
    padding: 10px;
    text-align: left;
}

.message {
    padding: 12px;
    background: #e8f7ed;
    color: #176b36;
    border-radius: 6px;
    margin-bottom: 15px;
}

.error {
    padding: 12px;
    background: #ffe9e9;
    color: #b42318;
    border-radius: 6px;
    margin-bottom: 15px;
}

@media(max-width:650px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .container {
        margin: 15px;
        padding: 18px;
    }
}

@media print {

    body {
        background: white;
    }

    header,
    .container,
    .no-print {
        display: none !important;
    }

    .preview {
        display: block !important;
        border: none;
        margin: 0;
    }
}

</style>

</head>

<body>

<header>

<h2>CareConnect</h2>

<a href="doctor_profile.php">
← Doctor Dashboard
</a>

</header>

<div class="container">

<h2>Digital Prescription</h2>

<p>Create a prescription for a patient consultation.</p>

<?php if ($message): ?>

<div class="<?php echo $success ? 'message' : 'error'; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>

<form id="prescriptionForm" method="POST">

<h3>Patient Details</h3>

<div class="form-grid">

<div>

<label>Patient ID</label>

<input
type="number"
name="patientId"
id="patientId"
required
>

</div>

<div>

<label>Patient Name</label>

<input
type="text"
id="patientName"
placeholder="Enter registered patient name"
required
>

</div>

<div>

<label>Age</label>

<input
type="number"
name="age"
id="age"
min="1"
max="120"
required
>

</div>

<div>

<label>Gender</label>

<select name="gender" id="gender" required>

<option value="">Select Gender</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>

</select>

</div>

<div>

<label>Prescription Date</label>

<input
type="date"
name="prescriptionDate"
id="prescriptionDate"
required
>

</div>

<div>

<label>Consultation ID</label>

<input
type="number"
name="consultationId"
id="consultationId"
placeholder="Enter consultation ID"
>

</div>

</div>

<h3>Diagnosis</h3>

<div class="form-grid">

<div class="full">

<label>Diagnosis / Health Condition</label>

<textarea
name="diagnosis"
id="diagnosis"
required
placeholder="Enter diagnosis"
></textarea>

</div>

<div class="full">

<label>Doctor's Notes</label>

<textarea
name="notes"
id="notes"
placeholder="Additional clinical notes"
></textarea>

</div>

</div>

<h3>Medicine Details</h3>

<div id="medicineContainer">

<div class="medicine">

<h4>Medicine 1</h4>

<div class="form-grid">

<div>

<label>Medicine Name</label>

<input
type="text"
class="medicineName"
required
>

</div>

<div>

<label>Dosage</label>

<input
type="text"
class="dosage"
placeholder="As prescribed"
required
>

</div>

<div>

<label>Frequency</label>

<select class="frequency" required>

<option value="">Select Frequency</option>
<option>Once daily</option>
<option>Twice daily</option>
<option>Three times daily</option>
<option>As needed</option>
<option>As prescribed</option>

</select>

</div>

<div>

<label>Duration</label>

<input
type="text"
class="duration"
placeholder="e.g. 5 days"
required
>

</div>

<div class="full">

<label>Medicine Instructions</label>

<input
type="text"
class="medicineInstructions"
placeholder="e.g. After food"
>

</div>

</div>

</div>

</div>

<button
type="button"
class="add-btn no-print"
onclick="addMedicine()"
>
+ Add Medicine
</button>

<h3>Follow-up & Advice</h3>

<div class="form-grid">

<div>

<label>Follow-up Date</label>

<input
type="date"
name="followupDate"
id="followupDate"
>

</div>

<div class="full">

<label>General Instructions</label>

<textarea
name="advice"
id="advice"
placeholder="Enter doctor's advice"
></textarea>

</div>

<div>

<label>Doctor Name</label>

<input
type="text"
id="doctorName"
value="<?php echo htmlspecialchars($doctorName); ?>"
readonly
>

</div>

<div>

<label>Hospital / Clinic</label>

<input
type="text"
name="hospital"
id="hospital"
placeholder="Enter hospital name"
>

</div>

</div>

<input
type="hidden"
name="medicines_json"
id="medicinesJson"
>

<div class="actions no-print">

<button
type="button"
class="back-btn"
onclick="generatePrescription()"
>
Generate Preview
</button>

<button
type="submit"
class="save-btn"
onclick="return prepareSave()"
>
Save Prescription for Patient
</button>

<button
type="reset"
class="back-btn"
onclick="resetPage()"
>
Clear Form
</button>

<a href="doctor_profile.php">
<button type="button" class="back-btn">
Back
</button>
</a>

</div>

</form>

<div class="preview" id="preview">

<h2>CareConnect</h2>

<p style="text-align:center;">
DIGITAL MEDICAL PRESCRIPTION
</p>

<hr>

<div id="patientPreview"></div>

<h3>Diagnosis</h3>

<p id="diagnosisPreview"></p>

<h3>Prescribed Medicines</h3>

<table>

<thead>

<tr>
<th>Medicine</th>
<th>Dosage</th>
<th>Frequency</th>
<th>Duration</th>
<th>Instructions</th>
</tr>

</thead>

<tbody id="medicinePreview"></tbody>

</table>

<h3>Doctor's Notes</h3>

<p id="notesPreview"></p>

<h3>Advice</h3>

<p id="advicePreview"></p>

<p id="followupPreview"></p>

<br>

<p>
<b>Doctor:</b>
<span id="doctorPreview"></span>
</p>

<p>
<b>Hospital:</b>
<span id="hospitalPreview"></span>
</p>

<p style="text-align:right; margin-top:50px;">

Doctor's Signature
<br>
____________________

</p>

<p style="font-size:12px;color:#667;">

This is a generated prescription preview. It requires review and authorization by the treating doctor.

</p>

</div>

<div
class="actions no-print"
id="printActions"
style="display:none;"
>

<button
class="print-btn"
onclick="window.print()"
>
Print Prescription
</button>

</div>

</div>

<script>

document.getElementById("prescriptionDate").value =
    new Date().toISOString().split("T")[0];

function addMedicine() {

    const container =
        document.getElementById("medicineContainer");

    const count =
        container.querySelectorAll(".medicine").length + 1;

    const div = document.createElement("div");

    div.className = "medicine";

    div.innerHTML = `
    <h4>Medicine ${count}</h4>

    <div class="form-grid">

    <div>
    <label>Medicine Name</label>
    <input type="text" class="medicineName" required>
    </div>

    <div>
    <label>Dosage</label>
    <input type="text" class="dosage" required>
    </div>

    <div>
    <label>Frequency</label>
    <select class="frequency" required>
    <option value="">Select Frequency</option>
    <option>Once daily</option>
    <option>Twice daily</option>
    <option>Three times daily</option>
    <option>As needed</option>
    <option>As prescribed</option>
    </select>
    </div>

    <div>
    <label>Duration</label>
    <input type="text" class="duration" required>
    </div>

    <div class="full">
    <label>Medicine Instructions</label>
    <input type="text" class="medicineInstructions">
    </div>

    </div>

    <button
    type="button"
    class="remove-btn no-print"
    onclick="this.parentElement.remove(); renumberMedicines();"
    >
    Remove Medicine
    </button>
    `;

    container.appendChild(div);
}

function renumberMedicines() {

    document.querySelectorAll(".medicine h4").forEach(
        (heading, index) => {
            heading.textContent = "Medicine " + (index + 1);
        }
    );
}

function collectMedicines() {

    const medicines = [];

    document.querySelectorAll(".medicine").forEach(medicine => {

        medicines.push({

            name: medicine.querySelector(".medicineName").value.trim(),

            dosage: medicine.querySelector(".dosage").value.trim(),

            frequency: medicine.querySelector(".frequency").value,

            duration: medicine.querySelector(".duration").value.trim(),

            instructions:
                medicine.querySelector(".medicineInstructions").value.trim()

        });

    });

    return medicines;
}

function prepareSave() {

    const form =
        document.getElementById("prescriptionForm");

    if (!form.reportValidity()) {
        return false;
    }

    document.getElementById("medicinesJson").value =
        JSON.stringify(collectMedicines());

    return true;
}

function generatePrescription() {

    const form =
        document.getElementById("prescriptionForm");

    if (!form.reportValidity()) {
        return;
    }

    const patientId =
        document.getElementById("patientId").value;

    const patientName =
        document.getElementById("patientName").value;

    const age =
        document.getElementById("age").value;

    const gender =
        document.getElementById("gender").value;

    const date =
        document.getElementById("prescriptionDate").value;

    const consultationId =
        document.getElementById("consultationId").value;

    document.getElementById("patientPreview").textContent =
        "Patient ID: " + patientId +
        " | Patient Name: " + patientName +
        " | Age: " + age +
        " | Gender: " + gender +
        " | Date: " + date +
        " | Consultation ID: " +
        (consultationId || "Not provided");

    document.getElementById("diagnosisPreview").textContent =
        document.getElementById("diagnosis").value;

    document.getElementById("notesPreview").textContent =
        document.getElementById("notes").value ||
        "No additional notes";

    document.getElementById("advicePreview").textContent =
        document.getElementById("advice").value ||
        "No additional advice";

    const followup =
        document.getElementById("followupDate").value;

    document.getElementById("followupPreview").textContent =
        followup
        ? "Follow-up Date: " + followup
        : "Follow-up date not specified";

    document.getElementById("doctorPreview").textContent =
        document.getElementById("doctorName").value;

    document.getElementById("hospitalPreview").textContent =
        document.getElementById("hospital").value ||
        "Not specified";

    const tbody =
        document.getElementById("medicinePreview");

    tbody.innerHTML = "";

    collectMedicines().forEach(medicine => {

        const row = document.createElement("tr");

        [
            medicine.name,
            medicine.dosage,
            medicine.frequency,
            medicine.duration,
            medicine.instructions || "-"
        ].forEach(value => {

            const cell = document.createElement("td");

            cell.textContent = value;

            row.appendChild(cell);

        });

        tbody.appendChild(row);

    });

    document.getElementById("preview").style.display =
        "block";

    document.getElementById("printActions").style.display =
        "block";

    document.getElementById("preview").scrollIntoView({
        behavior: "smooth"
    });
}

function resetPage() {

    document.getElementById("preview").style.display =
        "none";

    document.getElementById("printActions").style.display =
        "none";
}

</script>

</body>
</html>