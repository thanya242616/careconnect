
<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$patient_id = (int)$_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'] ?? '';

$message = "";

/* =========================
   UPLOAD NEW REPORT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST['action'] ?? '') === 'upload') {

    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $report_type = trim($_POST['report_type'] ?? '');
    $report_date = $_POST['report_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (
        $hospital_name === '' ||
        $report_type === '' ||
        $report_date === ''
    ) {
        $message = "Please fill all required fields.";

    } elseif (
        !isset($_FILES['report_file']) ||
        $_FILES['report_file']['error'] !== UPLOAD_ERR_OK
    ) {
        $message = "Please select a valid report file.";

    } else {

        $file = $_FILES['report_file'];

        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($extension, $allowed, true)) {

            $message = "Only PDF, JPG, JPEG and PNG files are allowed.";

        } elseif ($file['size'] > 5 * 1024 * 1024) {

            $message = "Maximum file size is 5 MB.";

        } else {

            $upload_dir = __DIR__ . "/uploads/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_filename =
                bin2hex(random_bytes(16)) . "." . $extension;

            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file(
                $file['tmp_name'],
                $destination
            )) {

                $file_path = "uploads/" . $new_filename;

                $sql = "INSERT INTO patient_reports
                (
                    patient_id,
                    patient_name,
                    hospital_name,
                    report_type,
                    report_date,
                    file_path,
                    description,
                    shared_doctor_name,
                    shared_hospital,
                    share_status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, 'Private')";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "issssss",
                    $patient_id,
                    $patient_name,
                    $hospital_name,
                    $report_type,
                    $report_date,
                    $file_path,
                    $description
                );

                if ($stmt->execute()) {

                    $message =
                        "Report uploaded successfully! Your report is Private.";

                } else {

                    if (file_exists($destination)) {
                        unlink($destination);
                    }

                    $message = "Database error: " . $stmt->error;
                }

            } else {

                $message = "File upload failed.";
            }
        }
    }
}


/* =========================
   SHARE SELECTED REPORT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST['action'] ?? '') === 'share') {

    $report_id = (int)($_POST['report_id'] ?? 0);

    $doctor_name = trim($_POST['doctor_name'] ?? '');

    $shared_hospital = trim(
        $_POST['shared_hospital'] ?? ''
    );

    if (
        $report_id <= 0 ||
        $doctor_name === '' ||
        $shared_hospital === ''
    ) {

        $message =
            "Please enter doctor name and hospital name.";

    } else {

        $sql = "UPDATE patient_reports
                SET shared_doctor_name = ?,
                    shared_hospital = ?,
                    share_status = 'Shared'
                WHERE report_id = ?
                AND patient_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssii",
            $doctor_name,
            $shared_hospital,
            $report_id,
            $patient_id
        );

        if ($stmt->execute() && $stmt->affected_rows > 0) {

            $message =
                "Selected report sharing details saved successfully.";

        } else {

            $message =
                "Report not found or no changes made.";
        }
    }
}


/* =========================
   DELETE SELECTED REPORT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST['action'] ?? '') === 'delete') {

    $report_id = (int)($_POST['report_id'] ?? 0);

    if ($report_id > 0) {

        // Verify report belongs to logged-in patient
        $check = $conn->prepare(
            "SELECT file_path
             FROM patient_reports
             WHERE report_id = ?
             AND patient_id = ?"
        );

        $check->bind_param(
            "ii",
            $report_id,
            $patient_id
        );

        $check->execute();

        $report = $check->get_result()->fetch_assoc();

        if ($report) {

            $delete = $conn->prepare(
                "DELETE FROM patient_reports
                 WHERE report_id = ?
                 AND patient_id = ?"
            );

            $delete->bind_param(
                "ii",
                $report_id,
                $patient_id
            );

            if (
                $delete->execute() &&
                $delete->affected_rows > 0
            ) {

                $filename = basename($report['file_path']);

                $file_to_delete =
                    __DIR__ . "/uploads/" . $filename;

                if (is_file($file_to_delete)) {
                    unlink($file_to_delete);
                }

                $message =
                    "Selected report deleted successfully.";

            } else {

                $message = "Unable to delete report.";
            }

        } else {

            $message = "Report not found.";
        }

    } else {

        $message = "Invalid report.";
    }
}


/* =========================
   FETCH PATIENT REPORTS
========================= */

$sql = "SELECT *
        FROM patient_reports
        WHERE patient_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $patient_id);

$stmt->execute();

$reports = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CareConnect - Medical Reports</title>

<style>

body {
    font-family: Arial, sans-serif;
    background-color: #f2f7fb;
    margin: 0;
    padding: 0;
}

header {
    background-color: #176b87;
    color: white;
    padding: 20px;
    text-align: center;
}

.container {
    width: 85%;
    max-width: 850px;
    margin: 30px auto;
}

.card {
    background-color: white;
    padding: 25px;
    margin-bottom: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

h2, h3 {
    color: #176b87;
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
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}

textarea {
    resize: vertical;
}

button {
    background-color: #176b87;
    color: white;
    border: none;
    padding: 11px 20px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 15px;
    font-size: 14px;
}

button:hover {
    opacity: 0.9;
}

.upload-btn {
    background-color: #176b87;
}

.delete-btn {
    background-color: #dc3545;
}

.delete-btn:hover {
    background-color: #b02a37;
}

.message {
    background-color: #e7f5ec;
    color: #176b45;
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 13px;
    background-color: #fff3cd;
    color: #856404;
}

.shared {
    background-color: #d4edda;
    color: #155724;
}

.share-box {
    background-color: #f5f9fc;
    padding: 18px;
    border-radius: 8px;
    margin-top: 18px;
}

.report-card {
    border: 1px solid #e1e8ed;
    border-radius: 10px;
    padding: 20px;
    margin-top: 18px;
}

.report-card p {
    line-height: 1.6;
}

.view-link {
    color: #176b87;
    font-weight: bold;
    text-decoration: none;
}

.view-link:hover {
    text-decoration: underline;
}

.back {
    display: inline-block;
    margin-top: 15px;
    color: #176b87;
    text-decoration: none;
}

.back:hover {
    text-decoration: underline;
}

.note {
    color: #777;
    font-size: 13px;
}

hr {
    border: 0;
    border-top: 1px solid #e5e5e5;
    margin: 25px 0;
}

</style>

</head>

<body>

<header>

    <h1>CareConnect</h1>

    <p>Your Health. Your Records. Your Control.</p>

</header>

<div class="container">

<?php if ($message !== '') { ?>

    <div class="message">

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php } ?>


<!-- UPLOAD SECTION -->

<div class="card">

    <h2>Upload New Medical Report</h2>

    <form method="POST"
          enctype="multipart/form-data">

        <input type="hidden"
               name="action"
               value="upload">

        <label>Patient Name</label>

        <input type="text"
               value="<?php echo htmlspecialchars($patient_name); ?>"
               readonly>

        <label>Patient ID</label>

        <input type="text"
               value="<?php echo $patient_id; ?>"
               readonly>

        <label>Hospital Name (Report From)</label>

        <input type="text"
               name="hospital_name"
               placeholder="Enter hospital name"
               required>

        <label>Report Type</label>

        <select name="report_type" required>

            <option value="">Select report type</option>

            <option>Blood Test</option>
            <option>X-Ray</option>
            <option>CT Scan</option>
            <option>MRI Scan</option>
            <option>Ultrasound</option>
            <option>Prescription</option>
            <option>Other</option>

        </select>

        <label>Report Date</label>

        <input type="date"
               name="report_date"
               required>

        <label>Choose Medical Report</label>

        <input type="file"
               name="report_file"
               accept=".pdf,.jpg,.jpeg,.png"
               required>

        <p class="note">
            Accepted formats: PDF, JPG, JPEG, PNG.
            Maximum size: 5 MB.
        </p>

        <label>Description</label>

        <textarea name="description"
                  rows="3"
                  placeholder="Enter report details"></textarea>

        <button type="submit" class="upload-btn">
            Upload Report
        </button>

    </form>

</div>


<!-- MY REPORTS SECTION -->

<div class="card">

    <h2>My Medical Reports</h2>

    <p class="note">
        All uploaded reports are private by default.
        Share only the reports you choose.
    </p>

    <?php if ($reports->num_rows > 0) { ?>

        <?php while ($report = $reports->fetch_assoc()) { ?>

            <div class="report-card">

                <h3>
                    <?php echo htmlspecialchars($report['report_type']); ?>
                </h3>

                <p>
                    <b>Report ID:</b>
                    <?php echo (int)$report['report_id']; ?>
                </p>

                <p>
                    <b>Hospital:</b>
                    <?php echo htmlspecialchars($report['hospital_name']); ?>
                </p>

                <p>
                    <b>Report Date:</b>
                    <?php echo htmlspecialchars($report['report_date']); ?>
                </p>

                <p>
                    <b>Description:</b>
                    <?php echo htmlspecialchars($report['description'] ?? ''); ?>
                </p>

                <p>
                    <b>Sharing Status:</b>

                    <?php if ($report['share_status'] === 'Shared') { ?>

                        <span class="status shared">
                            Shared
                        </span>

                    <?php } else { ?>

                        <span class="status">
                            Private
                        </span>

                    <?php } ?>

                </p>

                <p>
                    <a class="view-link"
                       href="<?php echo htmlspecialchars($report['file_path']); ?>"
                       target="_blank"
                       rel="noopener">
                        View Report
                    </a>
                </p>


                <?php if ($report['share_status'] === 'Shared') { ?>

                    <p>
                        <b>Shared Doctor:</b>
                        <?php echo htmlspecialchars($report['shared_doctor_name']); ?>
                    </p>

                    <p>
                        <b>Shared Hospital:</b>
                        <?php echo htmlspecialchars($report['shared_hospital']); ?>
                    </p>

                <?php } else { ?>

                    <!-- SHARE FORM -->

                    <div class="share-box">

                        <h3>Share This Report</h3>

                        <form method="POST">

                            <input type="hidden"
                                   name="action"
                                   value="share">

                            <input type="hidden"
                                   name="report_id"
                                   value="<?php echo (int)$report['report_id']; ?>">

                            <label>Doctor Name</label>

                            <input type="text"
                                   name="doctor_name"
                                   placeholder="Enter doctor name"
                                   required>

                            <label>Hospital Name</label>

                            <input type="text"
                                   name="shared_hospital"
                                   placeholder="Enter hospital name"
                                   required>

                            <button type="submit">
                                Share This Report
                            </button>

                        </form>

                    </div>

                <?php } ?>


                <!-- DELETE FORM -->

                <hr>

                <form method="POST"
                      onsubmit="return confirm('Are you sure you want to permanently delete this report?');">

                    <input type="hidden"
                           name="action"
                           value="delete">

                    <input type="hidden"
                           name="report_id"
                           value="<?php echo (int)$report['report_id']; ?>">

                    <button type="submit"
                            class="delete-btn">
                        Delete Report
                    </button>

                </form>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>
            No medical reports uploaded yet.
        </p>

    <?php } ?>

</div>

<a class="back" href="patient.php">
    ← Back to Patient Dashboard
</a>

</div>

</body>
</html>