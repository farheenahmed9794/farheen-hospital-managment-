<?php
require_once 'db.php';

$startDate  = trim($_GET['startDate'] ?? '');
$endDate    = trim($_GET['endDate'] ?? '');
$doctorId   = trim($_GET['doctorId'] ?? '');
$department = trim($_GET['department'] ?? '');

$sql = "
    SELECT a.*, 
           CONCAT(p.firstName, ' ', p.lastName) AS patientName, 
           p.phone AS patientPhone,
           d.name AS doctorName, 
           d.specialty,
           d.consultationFee
    FROM appointments a
    JOIN patients p ON a.patientId = p.id
    JOIN doctors d ON a.doctorId = d.id
    WHERE 1=1
";
$params = [];

if ($startDate !== '') {
    $sql .= " AND a.dateISO >= ?";
    $params[] = $startDate;
}
if ($endDate !== '') {
    $sql .= " AND a.dateISO <= ?";
    $params[] = $endDate;
}
if ($doctorId !== '') {
    $sql .= " AND a.doctorId = ?";
    $params[] = $doctorId;
}
if ($department !== '') {
    $sql .= " AND d.specialty = ?";
    $params[] = $department;
}

$sql .= " ORDER BY a.dateISO ASC, a.timeSlot ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

$allDoctors = $pdo->query("SELECT id, name FROM doctors ORDER BY name ASC")->fetchAll();
$allDepartments = $pdo->query("SELECT DISTINCT specialty FROM doctors ORDER BY specialty ASC")->fetchAll(PDO::FETCH_COLUMN);

include 'header.php';
?>

<div class="card">
    <h2>Filter Appointment Reports</h2>
    <form method="GET" action="reports.php">
        <div class="form-grid">
            <div class="form-group">
                <label>From Date</label>
                <input type="date" name="startDate" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="form-group">
                <label>To Date</label>
                <input type="date" name="endDate" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="form-group">
                <label>Doctor</label>
                <select name="doctorId">
                    <option value="">All Doctors</option>
                    <?php foreach ($allDoctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= $doctorId == $doc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($doc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Department / Specialty</label>
                <select name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($allDepartments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <a href="reports.php" class="btn btn-warning">Reset Filters</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Report Results (Total Found: <?= count($reportData) ?>)</h2>
    <table>
        <thead>
            <tr>
                <th>Appt ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Patient Name</th>
                <th>Contact</th>
                <th>Doctor</th>
                <th>Department</th>
                <th>Fee</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData)): ?>
                <tr><td colspan="9">No records match the selected filter criteria.</td></tr>
            <?php else: ?>
                <?php 
                $totalRevenue = 0;
                foreach ($reportData as $row): 
                    if ($row['status'] !== 'Cancelled') {
                        $totalRevenue += $row['consultationFee'];
                    }
                ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['dateISO']) ?></td>
                        <td><?= htmlspecialchars($row['timeSlot']) ?></td>
                        <td><?= htmlspecialchars($row['patientName']) ?></td>
                        <td><?= htmlspecialchars($row['patientPhone']) ?></td>
                        <td><?= htmlspecialchars($row['doctorName']) ?></td>
                        <td><?= htmlspecialchars($row['specialty']) ?></td>
                        <td>$<?= number_format($row['consultationFee'], 2) ?></td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background: #fdfdfd;">
                    <td colspan="7" align="right">Total Potential Revenue (Excl. Cancelled):</td>
                    <td colspan="2">$<?= number_format($totalRevenue, 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>