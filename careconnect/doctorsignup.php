
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $doctor_id = trim($_POST['doctor_id'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $hospital = trim($_POST['hospital'] ?? '');
    $registration_no = trim($_POST['registration_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $doctor_id === '' || $doctor_name === '' ||
        $email === '' || $mobile === '' ||
        $specialization === '' || $hospital === '' ||
        $registration_no === '' || $password === ''
    ) {
        $message = "All fields are required!";
    }
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    }
    else {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO doctors
        (doctor_id, doctor_name, email, mobile,
        specialization, hospital, registration_no, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssss",
            $doctor_id,
            $doctor_name,
            $email,
            $mobile,
            $specialization,
            $hospital,
            $registration_no,
            $hashed_password
        );

        if ($stmt->execute()) {

            echo "<script>
                alert('Doctor Account Created Successfully!');
                window.location.href='doctorlogin.html';
            </script>";

            exit();

        } else {

            if ($stmt->errno == 1062) {
                $message = "Doctor ID, Email or Registration Number already exists!";
            } else {
                $message = "Signup Failed: " . $stmt->error;
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Signup - CareConnect</title>
</head>
<body>

<h2>Doctor Registration</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST" action="doctorsignup.php">

    <label>Doctor ID</label><br>
    <input type="text" name="doctor_id" required><br><br>

    <label>Doctor Name</label><br>
    <input type="text" name="doctor_name" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Mobile</label><br>
    <input type="tel" name="mobile" required><br><br>

    <label>Specialization</label><br>
    <input type="text" name="specialization" required><br><br>

    <label>Hospital</label><br>
    <input type="text" name="hospital" required><br><br>

    <label>Registration Number</label><br>
    <input type="text" name="registration_no" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Create Account</button>

</form>

</body>
</html>