<?php

session_start();

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $doctor_id = trim($_POST['doctor_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($doctor_id === '' || $password === '') {

        $message = "Please enter Doctor ID and Password.";

    } else {

        $sql = "SELECT doctor_id, doctor_name, password
                FROM doctors
                WHERE doctor_id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $doctor_id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $doctor = $result->fetch_assoc();

            if (password_verify($password, $doctor['password'])) {

                session_regenerate_id(true);

                $_SESSION['doctor_id'] = $doctor['doctor_id'];
                $_SESSION['doctor_name'] = $doctor['doctor_name'];

                header("Location: doctor_profile.php");
                exit();

            } else {

                $message = "Incorrect Password!";

            }

        } else {

            $message = "Doctor ID not found!";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - CareConnect</title>
</head>

<body>

<h2>Doctor Login</h2>

<p style="color:red;">
    <?php echo htmlspecialchars($message); ?>
</p>

<form method="POST" action="doctorlogin.php">

    <label>Doctor ID</label><br>
    <input type="text" name="doctor_id" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>

</form>

<p>
    New Doctor?
    <a href="doctorsignup.html">Create Account</a>
</p>

</body>
</html>