
<?php 
session_start(); 
 
if (!isset($_SESSION['doctor_id'])) { 
    header("Location: doctorlogin.html"); 
    exit(); 
} 
 
$doctor_name = htmlspecialchars( 
    $_SESSION['doctor_name'] ?? 'Doctor', 
    ENT_QUOTES, 
    'UTF-8' 
); 
 
$doctor_id = htmlspecialchars( 
    (string)$_SESSION['doctor_id'], 
    ENT_QUOTES, 
    'UTF-8' 
); 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
 
<title>CareConnect - Doctor Dashboard</title> 
 
<style> 
* { 
    box-sizing: border-box; 
} 
 
body { 
    margin: 0; 
    font-family: Arial, sans-serif; 
    background: #f2f7fb; 
    color: #263746; 
} 
 
.sidebar { 
    width: 250px; 
    height: 100vh; 
    background: #075e68; 
    color: white; 
    position: fixed; 
    top: 0; 
    left: 0; 
    padding: 25px 15px; 
    overflow-y: auto; 
} 
 
.logo { 
    text-align: center; 
    font-size: 23px; 
    font-weight: bold; 
    margin-bottom: 8px; 
} 
 
.tagline { 
    text-align: center; 
    font-size: 11px; 
    color: #d1e8eb; 
    margin-bottom: 35px; 
} 
 
.sidebar a { 
    display: block; 
    color: white; 
    text-decoration: none; 
    padding: 14px; 
    margin: 8px 0; 
    border-radius: 8px; 
    font-size: 14px; 
} 
 
.sidebar a:hover, 
.sidebar a.active { 
    background: #087f8c; 
} 
 
.main { 
    margin-left: 250px; 
    padding: 30px; 
} 
 
.topbar { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: white; 
    padding: 20px 25px; 
    border-radius: 12px; 
    box-shadow: 0 3px 10px #00000008; 
    flex-wrap: wrap; 
    gap: 10px; 
} 
 
.topbar h2 { 
    margin: 0; 
    color: #075e68; 
} 
 
.doctor-info { 
    color: #64748b; 
    font-size: 14px; 
    margin-top: 6px; 
} 
 
.welcome { 
    margin-top: 25px; 
    background: linear-gradient(120deg, #087f8c, #075e68); 
    color: white; 
    padding: 28px; 
    border-radius: 12px; 
} 
 
.welcome h2 { 
    margin-top: 0; 
} 
 
.welcome p { 
    color: #e0f4f5; 
    line-height: 1.6; 
} 
 
.stats { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 18px; 
    margin-top: 25px; 
} 
 
.stat-card { 
    background: white; 
    padding: 22px; 
    border-radius: 12px; 
    box-shadow: 0 3px 10px #00000008; 
} 
 
.stat-card h3 { 
    color: #087f8c; 
    font-size: 28px; 
    margin: 10px 0; 
} 
 
.stat-card p { 
    color: #64748b; 
    margin: 0; 
    font-size: 14px; 
} 
 
.section-title { 
    color: #075e68; 
    margin-top: 35px; 
    margin-bottom: 18px; 
} 
 
.modules { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 18px; 
} 
 
.module-card { 
    background: white; 
    padding: 22px; 
    border-radius: 12px; 
    box-shadow: 0 3px 10px #00000008; 
    transition: 0.2s; 
    border: 1px solid #e8eef2; 
} 
 
.module-card:hover { 
    transform: translateY(-4px); 
    box-shadow: 0 6px 15px #00000012; 
} 
 
.module-icon { 
    font-size: 32px; 
} 
 
.module-card h3 { 
    color: #075e68; 
    font-size: 17px; 
    margin: 15px 0 8px; 
} 
 
.module-card p { 
    color: #718096; 
    font-size: 13px; 
    line-height: 1.5; 
    min-height: 40px; 
} 
 
.module-card a { 
    display: inline-block; 
    margin-top: 10px; 
    text-decoration: none; 
    background: #087f8c; 
    color: white; 
    padding: 10px 14px; 
    border-radius: 6px; 
    font-size: 13px; 
} 
 
.module-card a:hover { 
    background: #075e68; 
} 
 
.privacy { 
    background: #e8f5f7; 
    color: #075e68; 
    padding: 18px; 
    border-radius: 10px; 
    margin-top: 30px; 
    font-size: 14px; 
    line-height: 1.6; 
} 
 
.logout { 
    background: #d94b4b !important; 
    margin-top: 25px !important; 
} 
 
.logout:hover { 
    background: #b83232 !important; 
} 
 
@media(max-width: 1000px) { 
    .modules { 
        grid-template-columns: repeat(2, 1fr); 
    } 
 
    .stats { 
        grid-template-columns: repeat(2, 1fr); 
    } 
} 
 
@media(max-width: 700px) { 
    .sidebar { 
        width: 100%; 
        height: auto; 
        position: relative; 
    } 
 
    .main { 
        margin-left: 0; 
        padding: 15px; 
    } 
 
    .modules, 
    .stats { 
        grid-template-columns: 1fr; 
    } 
} 
</style> 
</head> 
 
<body> 
 
<div class="sidebar"> 
 
    <div class="logo">❤️ CareConnect</div> 
 
    <div class="tagline"> 
        Your Health. Your Records. Your Control. 
    </div> 
 
    <a href="doctor_profile.php" class="active"> 
        🏠 Dashboard 
    </a> 
 
    <a href="docprofile.html"> 
        👨‍⚕️ My Profile 
    </a> 
 
    <a href="doctor_appointments.php"> 
        📅 Appointments 
    </a> 
 
    <a href="doctor_patients.php"> 
        🧑‍🤝‍🧑 Patient Details 
    </a> 
 
    <a href="doctor_reports.php"> 
        📂 Medical Reports 
    </a> 
 
    <a href="doctor_online_consultation.php"> 
        💻 Online Consultation 
    </a> 
 
    <a href="doctor_prescription.php"> 
        💊 Digital Prescription 
    </a> 
 
    <a href="doctor_followup.html"> 
        🔔 Follow-ups 
    </a> 
 
    <a href="doctor_logout.php" class="logout"> 
        🚪 Logout 
    </a> 
 
</div> 
 
<div class="main"> 
 
    <div class="topbar"> 
 
        <div> 
            <h2>Doctor Dashboard</h2> 
 
            <div class="doctor-info"> 
                👨‍⚕️ Doctor Portal | CareConnect 
            </div> 
        </div> 
 
    </div> 
 
    <div class="welcome"> 
 
        <h2> 
            Welcome, <?php echo $doctor_name; ?>! 👨‍⚕️ 
        </h2> 
 
        <p> 
            Doctor ID: <?php echo $doctor_id; ?> 
        </p> 
 
        <p> 
            Manage your appointments, patient consultations, 
            medical reports and prescriptions in one place. 
        </p> 
 
    </div> 
 
    <div class="stats"> 
 
        <div class="stat-card"> 
            <div>📅</div> 
            <h3>0</h3> 
            <p>Total Appointments</p> 
        </div> 
 
        <div class="stat-card"> 
            <div>🧑‍🤝‍🧑</div> 
            <h3>0</h3> 
            <p>Patients</p> 
        </div> 
 
        <div class="stat-card"> 
            <div>💻</div> 
            <h3>0</h3> 
            <p>Online Consultations</p> 
        </div> 
 
    </div> 
 
    <h2 class="section-title">Doctor Services</h2> 
 
    <div class="modules"> 
 
        <div class="module-card"> 
            <div class="module-icon">📅</div> 
            <h3>Appointment Management</h3> 
            <p> 
                View and manage hospital and online appointment requests. 
            </p> 
            <a href="doctor_appointments.php"> 
                View Appointments → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">🧑‍🤝‍🧑</div> 
            <h3>Patient Details</h3> 
            <p> 
                Access patient information and current health issues. 
            </p> 
            <a href="doctor_patients.php"> 
                View Patients → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">📂</div> 
            <h3>Medical Reports</h3> 
            <p> 
                View medical reports shared by patients with permission. 
            </p> 
            <a href="doctor_reports.html"> 
                View Reports → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">💻</div> 
            <h3>Online Consultation</h3> 
            <p> 
                Manage scheduled online consultations with patients. 
            </p> 
            <a href="doctor_online_consultation.php"> 
                Consult Now → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">💊</div> 
            <h3>Digital Prescription</h3> 
            <p> 
                Create and provide digital prescriptions to patients. 
            </p> 
            <a href="doctor_prescription.html"> 
                Create Prescription → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">🔔</div> 
            <h3>Follow-up Management</h3> 
            <p> 
                Schedule patient follow-ups and manage upcoming visits. 
            </p> 
            <a href="doctor_followup.html"> 
                Manage Follow-ups → 
            </a> 
        </div> 
 
        <div class="module-card"> 
            <div class="module-icon">👨‍⚕️</div> 
            <h3>Doctor Profile</h3> 
            <p> 
                View and update professional and contact information. 
            </p> 
            <a href="docprofile.html"> 
                View Profile → 
            </a> 
        </div> 
 
    </div> 
 
    <div class="privacy"> 
        🔐 <b>Patient Data Privacy</b><br> 
        Patient medical records should be accessed only 
        with authorized patient consent. 
    </div> 
 
</div> 
 
</body> 
</html>