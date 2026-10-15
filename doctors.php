<?php
require_once 'db.php';

$errors = [];
$success = "";
$editingDoctor = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: doctors.php?msg=deleted");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $name            = trim($_POST['name'] ?? '');
    $specialty       = trim($_POST['specialty'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $daysArray       = $_POST['availableDays'] ?? [];
    $consultationFee = trim($_POST['consultationFee'] ?? '');

    if ($name === '') {
        $errors[] = "Doctor's name is required.";
    }
    if ($specialty === '') {
        $errors[] = "Please specify a medical specialty.";
    }
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Valid 10-digit phone number is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($daysArray)) {
        $errors[] = "Select at least one available working day.";
    }
    if (!is_numeric($consultationFee) || (float)$consultationFee <= 0) {
        $errors[] = "Consultation fee must be a valid positive number.";
    }

    if (empty($errors)) {
        $availableDaysStr = implode(", ", $daysArray);
        if ($id) {
            $stmt = $pdo->prepare("UPDATE doctors SET name=?, specialty=?, phone=?, email=?, availableDays=?, consultationFee=? WHERE id=?");
            $stmt->execute([$name, $specialty, $phone, $email, $availableDaysStr, $consultationFee, $id]);
            $success = "Doctor profile updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO doctors (name, specialty, phone, email, availableDays, consultationFee) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $specialty, $phone, $email, $availableDaysStr, $consultationFee]);
            $success = "New doctor added successfully.";
        }
    }
}

// Edit Mode
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editingDoctor = $stmt->fetch();
}

$doctors = $pdo->query("SELECT * FROM doctors ORDER BY id DESC")->fetchAll();
$allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$currentDays = $editingDoctor ? array_map('trim', explode(',', $editingDoctor['availableDays'])) : [];

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
        <?= !empty($success) ? $success : ($_GET['msg'] === 'deleted' ? "Doctor profile deleted." : "") ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= $editingDoctor ? 'Edit Doctor Profile' : 'Add New Doctor' ?></h2>
    <form method="POST" action="doctors.php">
        <input type="hidden" name="id" value="<?= $editingDoctor['id'] ?? '' ?>">
        
        <div class="form-grid">
            <div class="form-group">
                <label>Doctor Name *</label>
                <input type="text" name="name" required placeholder="Dr. John Doe" value="<?= htmlspecialchars($editingDoctor['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Specialty *</label>
                <select name="specialty" required>
                    <option value="">Select Specialty</option>
                    <?php 
                    $specs = ['Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics', 'General Medicine', 'Dermatology'];
                    foreach ($specs as $sp):
                    ?>
                        <option value="<?= $sp ?>" <?= ($editingDoctor['specialty'] ?? '') === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" required pattern="[0-9]{10}" value="<?= htmlspecialchars($editingDoctor['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editingDoctor['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Consultation Fee ($) *</label>
                <input type="number" step="0.01" name="consultationFee" required value="<?= htmlspecialchars($editingDoctor['consultationFee'] ?? '') ?>">
            </div>
            <div class="form-group full-width">
                <label>Available Days *</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.3rem;">
                    <?php foreach ($allDays as $day): ?>
                        <label style="font-weight: normal;">
                            <input type="checkbox" name="availableDays[]" value="<?= $day ?>" <?= in_array($day, $currentDays) ? 'checked' : '' ?>>
                            <?= $day ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <button type="submit" class="btn btn-primary"><?= $editingDoctor ? 'Update Doctor' : 'Save Doctor' ?></button>
            <?php if ($editingDoctor): ?>
                <a href="doctors.php" class="btn btn-warning">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Doctor Directory</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Specialty</th>
                <th>Contact</th>
                <th>Available Schedule</th>
                <th>Fee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($doctors)): ?>
                <tr><td colspan="7">No doctors registered yet.</td></tr>
            <?php else: ?>
                <?php foreach ($doctors as $d): ?>
                    <tr>
                        <td>#<?= $d['id'] ?></td>
                        <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                        <td><?= htmlspecialchars($d['specialty']) ?></td>
                        <td><?= htmlspecialchars($d['phone']) ?><br><small><?= htmlspecialchars($d['email']) ?></small></td>
                        <td><?= htmlspecialchars($d['availableDays']) ?></td>
                        <td>$<?= number_format($d['consultationFee'], 2) ?></td>
                        <td>
                            <a href="doctors.php?edit=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="doctors.php?delete=<?= $d['id'] ?>" onclick="return confirm('Remove doctor?')" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>