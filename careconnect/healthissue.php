
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (int)$_SESSION['patient_id'];

$message = "";
$message_type = "";

$hospital_name = "";
$doctor_id = "";
$symptoms = "";
$duration = "";
$severity = "";
$notes = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $doctor_id = strtoupper(trim($_POST['doctor_id'] ?? ''));
    $symptoms = trim($_POST['symptoms'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $severity = trim($_POST['severity'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (
        $hospital_name === '' ||
        $doctor_id === '' ||
        $symptoms === '' ||
        $duration === '' ||
        $severity === ''
    ) {
        $message = "Please fill all required fields.";
        $message_type = "error";
    } else {

        // Check whether the entered doctor ID exists
        $doctor_sql = "SELECT doctor_id, doctor_name
                       FROM doctors
                       WHERE doctor_id = ?";

        $doctor_stmt = $conn->prepare($doctor_sql);
        $doctor_stmt->bind_param("s", $doctor_id);
        $doctor_stmt->execute();

        $doctor_result = $doctor_stmt->get_result();

        if ($doctor_result->num_rows === 0) {

            $message = "Invalid Doctor ID. Please enter a registered Doctor ID.";
            $message_type = "error";

        } else {

            $doctor = $doctor_result->fetch_assoc();

            // Use the registered doctor ID
            $selected_doctor_id = $doctor['doctor_id'];

            // Insert patient issue with doctor assignment
            $insert_sql = "INSERT INTO patient_issues
                (
                    patient_id,
                    doctor_id,
                    symptoms,
                    duration,
                    severity,
                    issue_description,
                    hospital_name
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $insert_stmt = $conn->prepare($insert_sql);

            $insert_stmt->bind_param(
                "issssss",
                $patient_id,
                $selected_doctor_id,
                $symptoms,
                $duration,
                $severity,
                $notes,
                $hospital_name
            );

            if ($insert_stmt->execute()) {

                $new_issue_id = $conn->insert_id;

                // Verify the saved doctor ID
                $verify_sql = "SELECT doctor_id
                               FROM patient_issues
                               WHERE issue_id = ?
                               AND patient_id = ?";

                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param(
                    "ii",
                    $new_issue_id,
                    $patient_id
                );

                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $saved_record = $verify_result->fetch_assoc();

                if (
                    $saved_record &&
                    $saved_record['doctor_id'] === $selected_doctor_id
                ) {
                    $message = "Health issue submitted successfully! Assigned Doctor ID: "
                             . $selected_doctor_id;

                    $message_type = "success";

                    // Clear form after successful submission
                    $hospital_name = "";
                    $doctor_id = "";
                    $symptoms = "";
                    $duration = "";
                    $severity = "";
                    $notes = "";

                } else {
                    $message = "Issue was inserted, but doctor assignment verification failed.";
                    $message_type = "error";
                }

                $verify_stmt->close();

            } else {
                $message = "Database Error: " . $insert_stmt->error;
                $message_type = "error";
            }

            $insert_stmt->close();
        }

        $doctor_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CareConnect - Health Issue</title>

<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f2f7fb;
    margin: 0;
    padding: 30px 15px;
}

.container {
    max-width: 650px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

h2 {
    text-align: center;
    color: #176b87;
    margin-bottom: 25px;
}

label {
    display: block;
    margin-top: 15px;
    margin-bottom: 7px;
    font-weight: bold;
    color: #333;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 15px;
}

textarea {
    resize: vertical;
    min-height: 90px;
}

button {
    width: 100%;
    margin-top: 22px;
    padding: 13px;
    background: #087f8c;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #066875;
}

.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}

.success {
    background: #e5f7eb;
    color: #176534;
}

.error {
    background: #fde8e8;
    color: #a32121;
}

.back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #087fbd;
    text-decoration: none;
}

.back-link:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="container">

    <h2>Patient Health Issue Form</h2>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="health_issue.php" method="POST">

        <label>Hospital Name *</label>
        <input
            type="text"
            name="hospital_name"
            value="<?php echo htmlspecialchars($hospital_name); ?>"
            placeholder="Enter hospital name"
            required
        >

        <label>Doctor ID *</label>
        <input
            type="text"
            name="doctor_id"
            value="<?php echo htmlspecialchars($doctor_id); ?>"
            placeholder="Example: DOC01"
            required
        >

        <label>Symptoms *</label>
        <input
            type="text"
            name="symptoms"
            value="<?php echo htmlspecialchars($symptoms); ?>"
            placeholder="Enter your symptoms"
            required
        >

        <label>Duration *</label>
        <select name="duration" required>
            <option value="">Select duration</option>
            <?php
            $durations = [
                "1-3 days",
                "4-7 days",
                "1-2 weeks",
                "More than 2 weeks",
                "More than 1 month"
            ];

            foreach ($durations as $item) {
                $selected = ($duration === $item) ? "selected" : "";
                echo '<option value="' . htmlspecialchars($item) . '" '
                   . $selected . '>'
                   . htmlspecialchars($item)
                   . '</option>';
            }
            ?>
        </select>

        <label>Severity *</label>
        <select name="severity" required>
            <option value="">Select severity</option>
            <?php
            $severities = ["Mild", "Moderate", "Severe"];

            foreach ($severities as $item) {
                $selected = ($severity === $item) ? "selected" : "";
                echo '<option value="' . htmlspecialchars($item) . '" '
                   . $selected . '>'
                   . htmlspecialchars($item)
                   . '</option>';
            }
            ?>
        </select>

        <label>Additional Medical Details</label>
        <textarea
            name="notes"
            placeholder="Enter previous medical history or additional details"
        ><?php echo htmlspecialchars($notes); ?></textarea>

        <button type="submit">Submit Health Issue</button>

    </form>

    <a class="back-link" href="patient_dashboard.php">
        Back to Dashboard
    </a>

</div>

</body>
</html>