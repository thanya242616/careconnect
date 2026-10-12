<?php
session_start();
include "db.php";

/* =========================
   LOGIN CHECK
========================= */

$role = "";

if (isset($_SESSION['doctor_id'])) {
    $role = "doctor";
} elseif (isset($_SESSION['patient_id'])) {
    $role = "patient";
} else {
    die("Please login first.");
}


/* =========================
   CONSULTATION ID
========================= */

$consultation_id = isset($_GET['consultation_id'])
    ? intval($_GET['consultation_id'])
    : 0;

if ($consultation_id <= 0) {
    die("Invalid consultation ID.");
}


/* =========================
   GET CONSULTATION DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT
        consultation_id,
        patient_id,
        patient_name,
        hospital_name,
        doctor_id,
        doctor_name,
        consultation_date,
        consultation_time,
        symptoms,
        status
    FROM consultations
    WHERE consultation_id = ?
");

$stmt->bind_param("i", $consultation_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Consultation not found.");
}

$consultation = $result->fetch_assoc();

$stmt->close();


/* =========================
   AUTHORIZATION
========================= */

if ($role === "doctor") {

    if (
        !isset($_SESSION['doctor_name']) ||
        $_SESSION['doctor_name'] !== $consultation['doctor_name']
    ) {
        die("You are not authorized to access this consultation.");
    }

} elseif ($role === "patient") {

    if (
        !isset($_SESSION['patient_id']) ||
        intval($_SESSION['patient_id']) !== intval($consultation['patient_id'])
    ) {
        die("You are not authorized to access this consultation.");
    }
}


/* =========================
   STATUS CHECK
========================= */

if ($consultation['status'] !== "In Progress") {
    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>
            <h2>Consultation has not started yet.</h2>
            <p>Please wait until the doctor starts the consultation.</p>
        </div>
    ");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CareConnect - Online Consultation</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #eef6fa;
}


/* =========================
   HEADER
========================= */

.header {
    background: #087b9c;
    color: white;
    padding: 20px 32px;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    margin: 0;
    font-size: 26px;
}

.role {
    background: white;
    color: #087b9c;
    padding: 10px 22px;
    border-radius: 25px;
    font-weight: bold;
}


/* =========================
   MAIN
========================= */

.container {
    width: 90%;
    max-width: 1250px;
    margin: 25px auto;
}


/* =========================
   DETAILS
========================= */

.details {
    background: white;
    padding: 22px;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    margin-bottom: 22px;
}

.details h2 {
    color: #087b9c;
    margin-top: 0;
}

.details p {
    margin: 8px 0;
    font-size: 16px;
}


/* =========================
   VIDEO AREA
========================= */

.video-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.video-box {
    background: #111;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    min-height: 430px;
}

video {
    width: 100%;
    height: 430px;
    object-fit: cover;
    display: block;
    background: #111;
}

.video-label {
    position: absolute;
    top: 14px;
    left: 14px;

    background: rgba(0,0,0,0.75);
    color: white;

    padding: 9px 14px;
    border-radius: 7px;

    font-weight: bold;
    z-index: 5;
}


/* =========================
   WAITING MESSAGE
========================= */

.waiting {
    position: absolute;

    top: 50%;
    left: 50%;

    transform: translate(-50%, -50%);

    color: white;
    text-align: center;

    width: 90%;
}

.waiting h2 {
    margin-bottom: 8px;
}


/* =========================
   CONTROLS
========================= */

.controls {
    text-align: center;
    margin-top: 22px;
}

.controls button {
    border: none;
    padding: 13px 22px;
    margin: 5px;

    border-radius: 8px;

    cursor: pointer;

    font-size: 15px;
    font-weight: bold;
}

.mic-btn {
    background: #087b9c;
    color: white;
}

.camera-btn {
    background: #087b9c;
    color: white;
}

.end-btn {
    background: #d93025;
    color: white;
}

.controls button:hover {
    opacity: 0.85;
}


/* =========================
   STATUS
========================= */

.connection-status {
    text-align: center;
    margin-top: 15px;

    font-weight: bold;
    font-size: 16px;
}

.status-waiting {
    color: #d88a00;
}

.status-connected {
    color: green;
}

.status-error {
    color: red;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 800px) {

    .video-container {
        grid-template-columns: 1fr;
    }

    video {
        height: 350px;
    }

    .video-box {
        min-height: 350px;
    }

    .header h1 {
        font-size: 20px;
    }

}

</style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<div class="header">

    <h1>CareConnect - Online Consultation</h1>

    <div class="role">
        <?php echo ucfirst($role); ?>
    </div>

</div>


<div class="container">


<!-- =========================
     CONSULTATION DETAILS
========================= -->

<div class="details">

    <h2>Consultation Details</h2>

    <p>
        <strong>Patient:</strong>
        <?php echo htmlspecialchars($consultation['patient_name']); ?>
    </p>

    <p>
        <strong>Doctor:</strong>
        <?php echo htmlspecialchars($consultation['doctor_name']); ?>
    </p>

    <p>
        <strong>Date:</strong>
        <?php echo htmlspecialchars($consultation['consultation_date']); ?>
    </p>

    <p>
        <strong>Time:</strong>
        <?php echo htmlspecialchars($consultation['consultation_time']); ?>
    </p>

    <p>
        <strong>Symptoms:</strong>
        <?php echo htmlspecialchars($consultation['symptoms']); ?>
    </p>

</div>


<!-- =========================
     VIDEO SECTION
========================= -->

<div class="video-container">


<!-- LOCAL VIDEO -->

<div class="video-box">

    <div class="video-label">

        You
        (<?php echo ucfirst($role); ?>)

    </div>

    <video
        id="localVideo"
        autoplay
        muted
        playsinline>
    </video>

</div>


<!-- REMOTE VIDEO -->

<div class="video-box">

    <div class="video-label">

        <?php
        echo ($role === "doctor")
            ? "Patient"
            : "Doctor";
        ?>

    </div>


    <video
        id="remoteVideo"
        autoplay
        playsinline>
    </video>


    <div
        id="waitingMessage"
        class="waiting">

        <h2>
            Waiting for
            <?php
            echo ($role === "doctor")
                ? "Patient"
                : "Doctor";
            ?>
        </h2>

        <p>
            Please keep this page open.
        </p>

    </div>

</div>

</div>


<!-- =========================
     CONNECTION STATUS
========================= -->

<div
    id="connectionStatus"
    class="connection-status status-waiting">

    Connecting...

</div>


<!-- =========================
     CONTROLS
========================= -->

<div class="controls">

    <button
        id="micButton"
        class="mic-btn"
        onclick="toggleMic()">

        🎤 Mute Mic

    </button>


    <button
        id="cameraButton"
        class="camera-btn"
        onclick="toggleCamera()">

        📷 Turn Off Camera

    </button>


    <button
        class="end-btn"
        onclick="endCall()">

        📞 End Call

    </button>

</div>


</div>


<script>

/* =====================================================
   BASIC VARIABLES
===================================================== */

const consultationId =
    <?php echo intval($consultation_id); ?>;

const myRole =
    "<?php echo $role; ?>";


/* =====================================================
   HTML ELEMENTS
===================================================== */

const localVideo =
    document.getElementById("localVideo");

const remoteVideo =
    document.getElementById("remoteVideo");

const waitingMessage =
    document.getElementById("waitingMessage");

const connectionStatus =
    document.getElementById("connectionStatus");

const micButton =
    document.getElementById("micButton");

const cameraButton =
    document.getElementById("cameraButton");


/* =====================================================
   WEBRTC VARIABLES
===================================================== */

let localStream = null;

let peerConnection = null;

let micEnabled = true;

let cameraEnabled = true;

let lastSignalId = 0;

let pendingIceCandidates = [];


/* =====================================================
   STUN SERVER
===================================================== */

const rtcConfig = {

    iceServers: [

        {
            urls: "stun:stun.l.google.com:19302"
        },

        {
            urls: "stun:stun1.l.google.com:19302"
        }

    ]

};


/* =====================================================
   UPDATE STATUS
===================================================== */

function setStatus(message, type = "waiting") {

    connectionStatus.innerText = message;

    connectionStatus.className =
        "connection-status status-" + type;

}


/* =====================================================
   START CAMERA + MICROPHONE
===================================================== */

async function startLocalMedia() {

    try {

        localStream =
            await navigator.mediaDevices.getUserMedia({

                video: true,
                audio: true

            });

        localVideo.srcObject =
            localStream;

        console.log("Camera and microphone started.");

        setStatus(
            "Camera and microphone ready",
            "waiting"
        );

    } catch (error) {

        console.error(
            "Camera/Microphone error:",
            error
        );

        setStatus(
            "Camera or microphone permission denied.",
            "error"
        );

        alert(
            "Please allow camera and microphone permission."
        );

    }

}


/* =====================================================
   CREATE PEER CONNECTION
===================================================== */

function createPeerConnection() {

    if (peerConnection) {
        return;
    }


    console.log(
        "Creating WebRTC connection..."
    );


    peerConnection =
        new RTCPeerConnection(rtcConfig);


    /* =========================
       ADD LOCAL TRACKS
    ========================= */

    if (localStream) {

        localStream
            .getTracks()
            .forEach(track => {

                peerConnection.addTrack(
                    track,
                    localStream
                );

            });

    }


    /* =========================
       RECEIVE REMOTE TRACK
    ========================= */

    peerConnection.ontrack =
        function(event) {

            console.log(
                "Remote track received."
            );

            if (
                event.streams &&
                event.streams[0]
            ) {

                remoteVideo.srcObject =
                    event.streams[0];

                waitingMessage.style.display =
                    "none";

                setStatus(
                    "Connected - Video consultation active",
                    "connected"
                );

            }

        };


    /* =========================
       ICE CANDIDATE
    ========================= */

    peerConnection.onicecandidate =
        async function(event) {

            if (event.candidate) {

                console.log(
                    "Sending ICE candidate..."
                );

                await sendSignal(
                    "ice",
                    JSON.stringify(
                        event.candidate
                    )
                );

            }

        };


    /* =========================
       CONNECTION STATE
    ========================= */

    peerConnection.onconnectionstatechange =
        function() {

            console.log(
                "Connection state:",
                peerConnection.connectionState
            );


            if (
                peerConnection.connectionState ===
                "connected"
            ) {

                setStatus(
                    "Connected - Video consultation active",
                    "connected"
                );

            }


            if (
                peerConnection.connectionState ===
                "connecting"
            ) {

                setStatus(
                    "Connecting to other participant...",
                    "waiting"
                );

            }


            if (
                peerConnection.connectionState ===
                "disconnected"
            ) {

                setStatus(
                    "Participant disconnected.",
                    "error"
                );

            }


            if (
                peerConnection.connectionState ===
                "failed"
            ) {

                setStatus(
                    "Connection failed.",
                    "error"
                );

            }

        };

}


/* =====================================================
   SEND SIGNAL
===================================================== */

async function sendSignal(
    signalType,
    signalData
) {

    const formData =
        new FormData();


    formData.append(
        "consultation_id",
        consultationId
    );


    formData.append(
        "signal_type",
        signalType
    );


    formData.append(
        "signal_data",
        signalData
    );


    try {

        const response =
            await fetch(
                "consultation_signal.php",
                {
                    method: "POST",
                    body: formData
                }
            );


        const text =
            await response.text();


        console.log(
            "SIGNAL RESPONSE:",
            text
        );


        if (!response.ok) {

            console.error(
                "Signal request failed:",
                response.status
            );

            return;

        }


        try {

            const data =
                JSON.parse(text);


            console.log(
                "SIGNAL JSON:",
                data
            );


            if (!data.success) {

                console.error(
                    "Signal error:",
                    data.message
                );

            }

        } catch (error) {

            console.error(
                "Signal response is not valid JSON:",
                text
            );

        }


    } catch (error) {

        console.error(
            "Signal fetch error:",
            error
        );

    }

}


/* =====================================================
   GET SIGNALS
===================================================== */

async function getSignals() {

    try {

        const response =
            await fetch(
                "get_consultation_signals.php" +
                "?consultation_id=" +
                consultationId +
                "&last_id=" +
                lastSignalId
            );


        const text =
            await response.text();


        if (!response.ok) {

            console.error(
                "Polling HTTP error:",
                response.status
            );

            return;

        }


        let data;


        try {

            data =
                JSON.parse(text);

        } catch (error) {

            console.error(
                "Polling response is not valid JSON:",
                text
            );

            return;

        }


        if (
            !data.success ||
            !data.signals
        ) {

            return;

        }


        for (
            const signal of data.signals
        ) {

            lastSignalId =
                signal.signal_id;


            await handleSignal(
                signal
            );

        }


    } catch (error) {

        console.error(
            "Polling error:",
            error
        );

    }

}


/* =====================================================
   HANDLE SIGNAL
===================================================== */

async function handleSignal(signal) {

    console.log(
        "Received signal:",
        signal.signal_type
    );


    /* =========================
       OFFER
    ========================= */

    if (
        signal.signal_type ===
        "offer"
    ) {

        await handleOffer(
            signal.signal_data
        );

        return;

    }


    /* =========================
       ANSWER
    ========================= */

    if (
        signal.signal_type ===
        "answer"
    ) {

        await handleAnswer(
            signal.signal_data
        );

        return;

    }


    /* =========================
       ICE
    ========================= */

    if (
        signal.signal_type ===
        "ice"
    ) {

        await handleIceCandidate(
            signal.signal_data
        );

        return;

    }

}


/* =====================================================
   DOCTOR CREATE OFFER
===================================================== */

async function createOffer() {

    if (myRole !== "doctor") {
        return;
    }


    if (!peerConnection) {
        createPeerConnection();
    }


    try {

        console.log(
            "Doctor creating offer..."
        );


        const offer =
            await peerConnection.createOffer();


        await peerConnection.setLocalDescription(
            offer
        );


        await sendSignal(
            "offer",
            JSON.stringify(
                offer
            )
        );


        console.log(
            "Doctor offer sent."
        );


        setStatus(
            "Waiting for patient to join...",
            "waiting"
        );


    } catch (error) {

        console.error(
            "Offer creation error:",
            error
        );

        setStatus(
            "Could not create video connection.",
            "error"
        );

    }

}


/* =====================================================
   PATIENT HANDLE OFFER
===================================================== */

async function handleOffer(
    signalData
) {

    if (myRole !== "patient") {
        return;
    }


    try {

        console.log(
            "Patient received offer."
        );


        if (!peerConnection) {
            createPeerConnection();
        }


        const offer =
            JSON.parse(signalData);


        await peerConnection.setRemoteDescription(
            new RTCSessionDescription(
                offer
            )
        );


        /* =========================
           ADD BUFFERED ICE
        ========================= */

        for (
            const candidate
            of pendingIceCandidates
        ) {

            try {

                await peerConnection.addIceCandidate(
                    candidate
                );

            } catch (error) {

                console.error(
                    "Buffered ICE error:",
                    error
                );

            }

        }


        pendingIceCandidates = [];


        /* =========================
           CREATE ANSWER
        ========================= */

        const answer =
            await peerConnection.createAnswer();


        await peerConnection.setLocalDescription(
            answer
        );


        await sendSignal(
            "answer",
            JSON.stringify(
                answer
            )
        );


        console.log(
            "Patient answer sent."
        );


        setStatus(
            "Connecting to doctor...",
            "waiting"
        );


    } catch (error) {

        console.error(
            "Offer handling error:",
            error
        );

    }

}


/* =====================================================
   DOCTOR HANDLE ANSWER
===================================================== */

async function handleAnswer(
    signalData
) {

    if (myRole !== "doctor") {
        return;
    }


    try {

        console.log(
            "Doctor received answer."
        );


        const answer =
            JSON.parse(signalData);


        await peerConnection.setRemoteDescription(
            new RTCSessionDescription(
                answer
            )
        );


        /* =========================
           ADD BUFFERED ICE
        ========================= */

        for (
            const candidate
            of pendingIceCandidates
        ) {

            try {

                await peerConnection.addIceCandidate(
                    candidate
                );

            } catch (error) {

                console.error(
                    "Buffered ICE error:",
                    error
                );

            }

        }


        pendingIceCandidates = [];


        console.log(
            "Doctor remote description set."
        );


    } catch (error) {

        console.error(
            "Answer handling error:",
            error
        );

    }

}


/* =====================================================
   HANDLE ICE CANDIDATE
===================================================== */

async function handleIceCandidate(
    signalData
) {

    try {

        const candidateData =
            JSON.parse(signalData);


        const candidate =
            new RTCIceCandidate(
                candidateData
            );


        /* =========================
           REMOTE DESCRIPTION READY?
        ========================= */

        if (
            peerConnection &&
            peerConnection.remoteDescription
        ) {

            await peerConnection.addIceCandidate(
                candidate
            );


            console.log(
                "ICE candidate added."
            );

        } else {

            /* =========================
               BUFFER ICE
            ========================= */

            pendingIceCandidates.push(
                candidate
            );


            console.log(
                "ICE candidate buffered."
            );

        }


    } catch (error) {

        console.error(
            "ICE candidate error:",
            error
        );

    }

}


/* =====================================================
   START CONSULTATION
===================================================== */

async function startConsultation() {

    await startLocalMedia();

    createPeerConnection();


    /*
       Doctor creates the offer.

       Patient waits for the doctor's offer.
    */

    if (myRole === "doctor") {

        setTimeout(
            async function() {

                await createOffer();

            },
            1000
        );

    } else {

        setStatus(
            "Waiting for doctor connection...",
            "waiting"
        );

    }

}


/* =====================================================
   MICROPHONE
===================================================== */

function toggleMic() {

    if (!localStream) {
        return;
    }


    const audioTracks =
        localStream.getAudioTracks();


    if (audioTracks.length === 0) {
        return;
    }


    micEnabled =
        !micEnabled;


    audioTracks.forEach(
        track => {

            track.enabled =
                micEnabled;

        }
    );


    if (micEnabled) {

        micButton.innerText =
            "🎤 Mute Mic";

    } else {

        micButton.innerText =
            "🔇 Unmute Mic";

    }

}


/* =====================================================
   CAMERA
===================================================== */

function toggleCamera() {

    if (!localStream) {
        return;
    }


    const videoTracks =
        localStream.getVideoTracks();


    if (videoTracks.length === 0) {
        return;
    }


    cameraEnabled =
        !cameraEnabled;


    videoTracks.forEach(
        track => {

            track.enabled =
                cameraEnabled;

        }
    );


    if (cameraEnabled) {

        cameraButton.innerText =
            "📷 Turn Off Camera";

    } else {

        cameraButton.innerText =
            "📷 Turn On Camera";

    }

}


/* =====================================================
   END CALL
===================================================== */

function endCall() {

    if (localStream) {

        localStream
            .getTracks()
            .forEach(
                track => track.stop()
            );

    }


    if (peerConnection) {

        peerConnection.close();

    }


    if (myRole === "doctor") {

        window.location.href =
            "doctor_consultation.php";

    } else {

        window.location.href =
            "online_consultation.php";

    }

}


/* =====================================================
   START POLLING
===================================================== */

setInterval(
    getSignals,
    1000
);


/* =====================================================
   PAGE START
===================================================== */

window.addEventListener(
    "load",
    function() {

        startConsultation();

    }
);

</script>

</body>
</html>