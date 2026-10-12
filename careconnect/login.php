<?php

session_start();

include "db.php";

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit();
}

// Get form values
$patient_id = $_POST['patient_id'] ?? '';
$password = $_POST['password'] ?? '';

// Check empty fields
if ($patient_id === '' || $password === '') {
    die("Please enter Patient ID and Password.");
}

// Check Patient ID format
if (!ctype_digit((string)$patient_id)) {
    die("Invalid Patient ID. Please enter a valid ID.");
}

// Fetch patient details from database
$sql = "SELECT patient_id, full_name, password
        FROM patients
        WHERE patient_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("i", $patient_id);

$stmt->execute();

$stmt->store_result();

// Check whether Patient ID exists
if ($stmt->num_rows === 1) {

    $stmt->bind_result(
        $db_patient_id,
        $full_name,
        $hashed_password
    );

    $stmt->fetch();

    // Verify password
    if (password_verify($password, $hashed_password)) {

        // Create secure session
        session_regenerate_id(true);

        $_SESSION['patient_id'] = $db_patient_id;
        $_SESSION['patient_name'] = $full_name;

        // Redirect to patient dashboard
        header("Location: patient.php");
        exit();

    } else {

        echo "<h3>Incorrect Password.</h3>";
        echo '<a href="login.html">Try Again</a>';

    }

} else {

    echo "<h3>Patient ID not found.</h3>";
    echo '<a href="login.html">Try Again</a>';

}

// Close database resources
$stmt->close();
$conn->close();

?>