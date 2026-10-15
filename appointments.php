<?php
require_once 'db.php';

$errors = [];
$success = "";
$reschedulingApp = null;

// Handle Cancellation
if (isset($_GET['cancel'])) {
    $cancelId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
    $stmt->execute([$cancelId]);
    header("Location: appointments.php?msg=cancelled");
    exit;
}

// Handle Mark Completed
if (isset($_GET['complete'])) {
    $completeId = (int)$_GET['complete'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
    $stmt->execute([$completeId]);
    header("Location: appointments.php?msg=completed");
    exit;
}

// Handle Scheduling or Rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $patientId = (int)($_POST['patientId'] ?? 0);
    $doctorId  = (int)($_POST['doctorId'] ?? 0);
    $dateISO   = trim($_POST['dateISO'] ?? '');
    $timeSlot  = trim($_POST['timeSlot'] ?? '');

    // Validation: Required fields
    if ($patientId <= 0) $errors[] = "Please select a valid patient.";
    if ($doctorId <= 0)  $errors[] = "Please select a valid doctor.";
    if ($dateISO === '')  $errors[] = "Appointment date is required.";
    if ($timeSlot === '') $errors[] = "Appointment time slot is required.";

    // Validation: Date/Time must be today or in the future
    if ($dateISO !== '' && $dateISO < date('Y-m-d')) {
        $errors[] = "Appointments cannot be booked for past dates.";
    }

    // Validation: Doctor schedule match
    if ($doctorId > 0 && $dateISO !== '') {
        $dayOfWeek = date('l', strtotime($dateISO));
        $docStmt = $pdo->prepare("SELECT availableDays FROM doctors WHERE id = ?");
        $docStmt->execute([$doctorId]);
        $availableDays = $docStmt->fetchColumn();

        if (stripos($availableDays, $dayOfWeek) === false) {
            $errors[] = "Selected doctor is not available on {$dayOfWeek}s. (Schedule: {$availableDays})";
        }
    }

    // Validation: Prevent Double-booking for Doctor at the same slot
    if (empty($errors)) {
        $conflictQuery = "SELECT id FROM appointments WHERE doctorId = ? AND dateISO = ? AND timeSlot = ? AND status != 'Cancelled'";
        $conflictParams = [$doctorId, $dateISO, $timeSlot];
        
        if ($id) {
            $conflictQuery .= " AND id != ?";
            $conflictParams[] = $id;
        }

        $conflictStmt = $pdo->prepare($conflictQuery);
        $conflictStmt->execute($conflictParams);

        if ($conflictStmt->rowCount() > 0) {
            $errors[] = "The selected doctor is already booked for {$timeSlot} on {$dateISO}. Please choose another time.";
        }
    }

    if (empty($errors)) {
        if ($id) {
            // Reschedule
            $stmt = $pdo->prepare("UPDATE appointments SET patientId=?, doctorId=?, dateISO=?, timeSlot=?, status='Scheduled' WHERE id=?");
            $stmt->execute([$patientId, $doctorId, $dateISO, $timeSlot, $id]);
            $success = "Appointment successfully rescheduled.";
        } else {
            // Schedule New
            $stmt = $pdo->prepare("INSERT INTO appointments (patientId, doctorId, dateISO, timeSlot, status) VALUES (?, ?, ?, ?, 'Scheduled')");
            $stmt->execute([$patientId, $doctorId, $dateISO, $timeSlot]);
            $success = "Appointment successfully booked.";
        }
    }
}

// Edit/Reschedule Mode
if (isset($_GET['reschedule'])) {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([(int)$_GET['reschedule']]);
    $reschedulingApp = $stmt->fetch();
}

$patients = $pdo->query("SELECT id, firstName, lastName FROM patients ORDER BY firstName ASC")->fetchAll();
$doctors = $pdo->query("SELECT id, name, specialty, availableDays FROM doctors ORDER BY name ASC")->fetchAll();

$appointments = $pdo->query("
    SELECT a.*, CONCAT(p.firstName, ' ', p.lastName) AS patientName, d.name AS doctorName, d.specialty
    FROM appointments a
    JOIN patients p ON a.patientId = p.id
    JOIN doctors d ON a.doctorId = d.id
    ORDER BY a.dateISO DESC, a.timeSlot ASC
")->fetchAll();

include 'header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div>• <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success) || isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?= !empty($success) ? $success : ($_GET['msg'] === 'cancelled' ? "Appointment has been cancelled." : "Appointment marked as completed.") ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= $reschedulingApp ? 'Reschedule Appointment' : 'Schedule an Appointment' ?></h2>
    <form method="POST" action="appointments.php">
        <input type="hidden" name="id" value="<?= $reschedulingApp['id'] ?? '' ?>">
        
        <div class="form-grid">
            <div class="form-group">
                <label>Patient *</label>
                <select name="patientId" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= ($reschedulingApp['patientId'] ?? '') == $pt['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pt['firstName'] . ' ' . $pt['lastName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Doctor *</label>
                <select name="doctorId" required>
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= ($reschedulingApp['doctorId'] ?? '') == $doc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialty']) ?>) - <?= htmlspecialchars($doc['availableDays']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Appointment Date (dateISO) *</label>
                <input type="date" name="dateISO" min="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($reschedulingApp['dateISO'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Time Slot *</label>
                <select name="timeSlot" required>
                    <option value="">Select Slot</option>
                    <?php 
                    $slots = ['09:00 AM', '09:45 AM', '10:30 AM', '11:15 AM', '02:00 PM', '02:45 PM', '03:30 PM', '04:15 PM'];
                    foreach ($slots as $sl):
                    ?>
                        <option value="<?= $sl ?>" <?= ($reschedulingApp['timeSlot'] ?? '') === $sl ? 'selected' : '' ?>><?= $sl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <button type="submit" class="btn btn-primary"><?= $reschedulingApp ? 'Confirm Reschedule' : 'Book Appointment' ?></button>
            <?php if ($reschedulingApp): ?>
                <a href="appointments.php" class="btn btn-warning">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>All Scheduled Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>Appt ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time Slot</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="7">No appointments recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td>#<?= $a['id'] ?></td>
                        <td><?= htmlspecialchars($a['patientName']) ?></td>
                        <td><?= htmlspecialchars($a['doctorName']) ?><br><small><?= htmlspecialchars($a['specialty']) ?></small></td>
                        <td><?= htmlspecialchars($a['dateISO']) ?></td>
                        <td><?= htmlspecialchars($a['timeSlot']) ?></td>
                        <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
                        <td>
                            <?php if ($a['status'] === 'Scheduled'): ?>
                                <a href="appointments.php?reschedule=<?= $a['id'] ?>" class="btn btn-primary btn-sm">Reschedule</a>
                                <a href="appointments.php?complete=<?= $a['id'] ?>" class="btn btn-sm" style="background:#2e7d32; color:#fff;">Complete</a>
                                <a href="appointments.php?cancel=<?= $a['id'] ?>" onclick="return confirm('Cancel this appointment?')" class="btn btn-danger btn-sm">Cancel</a>
                            <?php else: ?>
                                <span style="color: #888; font-size: 0.8rem;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>