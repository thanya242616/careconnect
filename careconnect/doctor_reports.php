
<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctorlogin.html");
    exit();
}

include "db.php";

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'] ?? '';

$sql = "SELECT *
        FROM patient_reports
        WHERE shared_doctor_name = ?
        AND share_status = 'Shared'
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctor_name);
$stmt->execute();

$reports = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CareConnect - Shared Medical Reports</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f2f7fb;
    margin: 0;
}

header {
    background: #176b87;
    color: white;
    text-align: center;
    padding: 20px;
}

.container {
    width: 85%;
    max-width: 900px;
    margin: 30px auto;
}

.card {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

h2, h3 {
    color: #176b87;
}

.report {
    border: 1px solid #dce7ed;
    border-radius: 10px;
    padding: 20px;
    margin-top: 18px;
    background: #ffffff;
}

.report p {
    line-height: 1.7;
}

.status {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 13px;
}

.view-btn {
    display: inline-block;
    background: #176b87;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 6px;
    margin-top: 10px;
}

.view-btn:hover {
    background: #10536b;
}

.back {
    display: inline-block;
    color: #176b87;
    text-decoration: none;
    margin-top: 15px;
}

.empty {
    text-align: center;
    padding: 30px;
    background: #f8fafc;
    border: 1px dashed #b8cbd5;
    border-radius: 8px;
    color: #777;
}

</style>
</head>

<body>

<header>
    <h1>CareConnect</h1>
    <p>Doctor Medical Reports</p>
</header>

<div class="container">

<div class="card">

    <h2>Shared Medical Reports</h2>

    <p>
        <b>Doctor:</b>
        <?php echo htmlspecialchars($doctor_name); ?>
    </p>

    <p>
        Reports shared with your doctor account are displayed here.
    </p>

    <?php if ($reports->num_rows > 0) { ?>

        <?php while ($row = $reports->fetch_assoc()) { ?>

            <div class="report">

                <h3>
                    <?php echo htmlspecialchars($row['report_type']); ?>
                </h3>

                <span class="status">Shared with You</span>

                <p>
                    <b>Patient Name:</b>
                    <?php echo htmlspecialchars($row['patient_name']); ?>
                </p>

                <p>
                    <b>Patient ID:</b>
                    <?php echo (int)$row['patient_id']; ?>
                </p>

                <p>
                    <b>Report ID:</b>
                    <?php echo (int)$row['report_id']; ?>
                </p>

                <p>
                    <b>Report Date:</b>
                    <?php echo htmlspecialchars($row['report_date']); ?>
                </p>

                <p>
                    <b>Report From Hospital:</b>
                    <?php echo htmlspecialchars($row['hospital_name']); ?>
                </p>

                <p>
                    <b>Shared Hospital:</b>
                    <?php echo htmlspecialchars($row['shared_hospital']); ?>
                </p>

                <p>
                    <b>Description:</b>
                    <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                </p>

                <a class="view-btn"
                   href="<?php echo htmlspecialchars($row['file_path']); ?>"
                   target="_blank"
                   rel="noopener">
                    View Report
                </a>

                <a class="view-btn"
                   href="<?php echo htmlspecialchars($row['file_path']); ?>"
                   download>
                    Download Report
                </a>

            </div>

        <?php } ?>

    <?php } else { ?>

        <div class="empty">
            <h3>No Shared Reports Found</h3>
            <p>
                Patients have not shared any reports with your account yet.
            </p>
        </div>

    <?php } ?>

</div>

<a class="back" href="doctor_profile.php">
    ← Back to Doctor Dashboard
</a>

</div>

</body>
</html>