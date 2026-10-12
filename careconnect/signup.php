<?php

include "db.php";

// Make sure the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

// Get form data
$full_name = $_POST['full_name'] ?? '';
$date_of_birth = $_POST['date_of_birth'] ?? '';
$gender = $_POST['gender'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Check required fields
if (
    empty($full_name) ||
    empty($date_of_birth) ||
    empty($gender) ||
    empty($mobile) ||
    empty($password) ||
    empty($confirm_password)
) {
    die("Please fill all required fields.");
}

// Check passwords
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into patients table
$sql = "INSERT INTO patients
        (full_name, date_of_birth, gender, mobile, email, address, password)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssssss",
    $full_name,
    $date_of_birth,
    $gender,
    $mobile,
    $email,
    $address,
    $hashed_password
);

if ($stmt->execute()) {

    $patient_id = $conn->insert_id;

    echo "<h2>Account created successfully!</h2>";
    echo "<p>Your Patient ID is: <strong>" . $patient_id . "</strong></p>";
    echo "<p>Please remember this Patient ID for login.</p>";
    echo "<a href='login.html'>Go to Login</a>";

} else {

    echo "Error while creating account: " . $stmt->error;

}

$stmt->close();
$conn->close();

?>