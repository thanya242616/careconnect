
<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.html");
    exit();
}

include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: healthissue.php");
    exit();
}

$patient_id = (int)$_SESSION['patient_id'];

$hospital_name = trim($_POST['hospital_name'] ?? '');
$symptoms = trim($_POST['symptoms'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$severity = trim($_POST['severity'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (
    $hospital_name === '' ||
    $symptoms === '' ||
    $duration === '' ||
    $severity === ''
) {
    exit("Please fill all required fields.");
}

$sql = "INSERT INTO patient_issues
(patient_id, hospital_name, symptoms, duration, severity, issue_description)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit("Database query error: " . $conn->error);
}

$stmt->bind_param(
    "isssss",
    $patient_id,
    $hospital_name,
    $symptoms,
    $duration,
    $severity,
    $notes
);

if ($stmt->execute()) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Health Issue Submitted</title>
        <style>
            body {
                font-family: Arial;
                background: #f2f7f8;
                text-align: center;
                padding: 80px 20px;
            }
            .box {
                background: white;
                padding: 35px;
                max-width: 450px;
                margin: auto;
                border-radius: 12px;
                box-shadow: 0 4px 15px #00000010;
            }
            h2 { color: #087f8c; }
            a {
                display: inline-block;
                margin-top: 20px;
                background: #087f8c;
                color: white;
                padding: 12px 20px;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <div class='box'>
            <h2>Health Details Submitted Successfully!</h2>
            <p>Your current health issue has been saved.</p>
            <a href='patient.php'>Back to Patient Dashboard</a>
        </div>
    </body>
    </html>
    ";
} else {
    echo "Error saving health details: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>