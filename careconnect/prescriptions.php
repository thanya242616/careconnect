
<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$patient_id = $_SESSION['patient_id'];

$sql = "SELECT * FROM prescriptions
        WHERE patient_id = ?
        ORDER BY prescription_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Digital Prescription - CareConnect</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f7fb;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 850px;
            margin: auto;
        }

        h2 {
            color: #176b87;
            text-align: center;
        }

        .prescription {
            background: white;
            padding: 25px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000012;
        }

        h3 {
            color: #176b87;
        }

        .info {
            line-height: 1.8;
        }

        .medicine {
            background: #f2f7fb;
            padding: 12px;
            margin: 10px 0;
            border-radius: 7px;
        }

        button {
            background: #176b87;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .back {
            display: inline-block;
            margin-bottom: 15px;
            color: #176b87;
        }

        @media print {
            button, .back {
                display: none;
            }

            body {
                background: white;
            }

            .prescription {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a class="back" href="patient.php">← Back to Dashboard</a>

    <h2>My Digital Prescriptions</h2>

    <?php if ($result->num_rows > 0) { ?>

        <?php while ($row = $result->fetch_assoc()) { ?>

            <div class="prescription">

                <h3>CareConnect - Digital Prescription</h3>

                <div class="info">
                    <b>Prescription ID:</b>
                    <?php echo htmlspecialchars($row['prescription_id']); ?><br>

                    <b>Patient Name:</b>
                    <?php echo htmlspecialchars($row['patient_name']); ?><br>

                    <b>Patient ID:</b>
                    <?php echo htmlspecialchars($row['patient_id']); ?><br>

                    <b>Doctor:</b>
                    <?php echo htmlspecialchars($row['doctor_name']); ?><br>

                    <b>Hospital:</b>
                    <?php echo htmlspecialchars($row['hospital_name'] ?? ''); ?><br>

                    <b>Date:</b>
                    <?php echo htmlspecialchars($row['prescription_date']); ?>
                </div>

                <hr>

                <h3>Diagnosis</h3>
                <p>
                    <?php echo nl2br(htmlspecialchars($row['diagnosis'] ?? '')); ?>
                </p>

                <h3>Medicines</h3>

                <?php
                $medicines = json_decode($row['medicines'], true);

                if (is_array($medicines)) {
                    foreach ($medicines as $medicine) {
                ?>

                    <div class="medicine">
                        <b>Medicine:</b>
                        <?php echo htmlspecialchars($medicine['name'] ?? ''); ?><br>

                        <b>Dosage:</b>
                        <?php echo htmlspecialchars($medicine['dosage'] ?? ''); ?><br>

                        <b>Frequency:</b>
                        <?php echo htmlspecialchars($medicine['frequency'] ?? ''); ?><br>

                        <b>Duration:</b>
                        <?php echo htmlspecialchars($medicine['duration'] ?? ''); ?><br>

                        <b>Instructions:</b>
                        <?php echo htmlspecialchars($medicine['instructions'] ?? ''); ?>
                    </div>

                <?php
                    }
                } else {
                    echo "<p>Medicine details unavailable.</p>";
                }
                ?>

                <h3>Doctor Instructions</h3>
                <p>
                    <?php echo nl2br(htmlspecialchars($row['instructions'] ?? '')); ?>
                </p>

                <button onclick="window.print()">Print Prescription</button>

            </div>

        <?php } ?>

    <?php } else { ?>

        <div class="prescription">
            <h3>No Prescription Available</h3>
            <p>Your doctor has not issued a prescription yet.</p>
        </div>

    <?php } ?>

</div>

</body>
</html>