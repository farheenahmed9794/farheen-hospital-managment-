<?php
require_once 'db.php';
include 'header.php';

// Total Patients
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// Doctors on duty today (e.g. current day of week: Monday, Tuesday, etc.)
$currentDay = date('l');
$docStmt = $pdo->prepare("SELECT COUNT(*) FROM doctors WHERE availableDays LIKE ?");
$docStmt->execute(["%$currentDay%"]);
$doctorsOnDuty = $docStmt->fetchColumn();

// Appointments Today
$today = date('Y-m-d');
$appStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE dateISO = ?");
$appStmt->execute([$today]);
$appointmentsToday = $appStmt->fetchColumn();

// Upcoming 5 Appointments
$recentAppointments = $pdo->query("
    SELECT a.*, CONCAT(p.firstName, ' ', p.lastName) AS patientName, d.name AS doctorName, d.specialty
    FROM appointments a
    JOIN patients p ON a.patientId = p.id
    JOIN doctors d ON a.doctorId = d.id
    ORDER BY a.dateISO DESC, a.timeSlot ASC LIMIT 5
")->fetchAll();
?>

<div class="kpi-grid">
    <div class="kpi-card">
        <h3>Total Registered Patients</h3>
        <div class="number"><?= $totalPatients ?></div>
    </div>
    <div class="kpi-card">
        <h3>Doctors On Duty Today (<?= $currentDay ?>)</h3>
        <div class="number"><?= $doctorsOnDuty ?></div>
    </div>
    <div class="kpi-card">
        <h3>Appointments Today</h3>
        <div class="number"><?= $appointmentsToday ?></div>
    </div>
</div>

<div class="card">
    <h2>Recent Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Specialty</th>
                <th>Date</th>
                <th>Time Slot</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentAppointments)): ?>
                <tr><td colspan="7">No records available.</td></tr>
            <?php else: ?>
                <?php foreach ($recentAppointments as $row): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['patientName']) ?></td>
                        <td><?= htmlspecialchars($row['doctorName']) ?></td>
                        <td><?= htmlspecialchars($row['specialty']) ?></td>
                        <td><?= htmlspecialchars($row['dateISO']) ?></td>
                        <td><?= htmlspecialchars($row['timeSlot']) ?></td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>