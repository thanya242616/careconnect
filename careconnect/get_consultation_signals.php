<?php
session_start();
include "db.php";

header("Content-Type: application/json");

// Doctor or Patient login check
if (isset($_SESSION['doctor_id'])) {
    $my_role = "doctor";
} elseif (isset($_SESSION['patient_id'])) {
    $my_role = "patient";
} else {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
    exit;
}

$consultation_id = isset($_GET['consultation_id'])
    ? intval($_GET['consultation_id'])
    : 0;

$last_id = isset($_GET['last_id'])
    ? intval($_GET['last_id'])
    : 0;

if ($consultation_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid consultation ID"
    ]);
    exit;
}

/*
   Get signals sent by the OTHER person.
   Doctor receives patient signals.
   Patient receives doctor signals.
*/
$other_role = ($my_role == "doctor") ? "patient" : "doctor";

$stmt = $conn->prepare("
    SELECT signal_id, sender_role, signal_type, signal_data
    FROM consultation_signals
    WHERE consultation_id = ?
      AND sender_role = ?
      AND signal_id > ?
    ORDER BY signal_id ASC
");

$stmt->bind_param(
    "isi",
    $consultation_id,
    $other_role,
    $last_id
);

$stmt->execute();

$result = $stmt->get_result();

$signals = [];

while ($row = $result->fetch_assoc()) {
    $signals[] = [
        "signal_id" => (int)$row["signal_id"],
        "sender_role" => $row["sender_role"],
        "signal_type" => $row["signal_type"],
        "signal_data" => $row["signal_data"]
    ];
}

echo json_encode([
    "success" => true,
    "my_role" => $my_role,
    "signals" => $signals
]);

$stmt->close();
$conn->close();
?>