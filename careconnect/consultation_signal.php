<?php
session_start();
include "db.php";

header("Content-Type: application/json");

// Doctor or Patient login check
if (isset($_SESSION['doctor_id'])) {
    $sender_role = "doctor";
} elseif (isset($_SESSION['patient_id'])) {
    $sender_role = "patient";
} else {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
    exit;
}

// Get data from JavaScript
$consultation_id = isset($_POST['consultation_id']) ? intval($_POST['consultation_id']) : 0;
$signal_type = isset($_POST['signal_type']) ? $_POST['signal_type'] : '';
$signal_data = isset($_POST['signal_data']) ? $_POST['signal_data'] : '';

if ($consultation_id <= 0 || empty($signal_type) || empty($signal_data)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing data"
    ]);
    exit;
}

// Check consultation exists
$check = $conn->prepare("
    SELECT consultation_id
    FROM consultations
    WHERE consultation_id = ?
");
$check->bind_param("i", $consultation_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Consultation not found"
    ]);
    exit;
}

// Insert signal
$stmt = $conn->prepare("
    INSERT INTO consultation_signals
    (consultation_id, sender_role, signal_type, signal_data)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "isss",
    $consultation_id,
    $sender_role,
    $signal_type,
    $signal_data
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Signal saved"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to save signal"
    ]);
}

$stmt->close();
$conn->close();
?>