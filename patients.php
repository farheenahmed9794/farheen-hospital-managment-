<?php
require_once 'db.php';

$errors = [];
$success = "";
$editingPatient = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: patients.php?msg=deleted");
    exit;
}

// Handle Form Submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $dobISO    = trim($_POST['dobISO'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    // Server-Side Validations
    if ($firstName === '' || !preg_match("/^[a-zA-Z\s]+$/", $firstName)) {
        $errors[] = "First name must contain only letters.";
    }
    if ($lastName === '' || !preg_match("/^[a-zA-Z\s]+$/", $lastName)) {
        $errors[] = "Last name must contain only letters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }
    if ($dobISO === '' || $dobISO > date('Y-m-d')) {
        $errors[] = "Date of birth cannot be empty or set in the future.";
    }
    if ($address === '') {
        $errors[] = "Address is required.";
    }

    if (empty($errors)) {
        if ($id) {
            // Update Existing
            $stmt = $pdo->prepare("UPDATE patients SET firstName=?, lastName=?, email=?, phone=?, dobISO=?, address=? WHERE id=?");
            $stmt->execute([$firstName, $lastName, $email, $phone, $dobISO, $address, $id]);
            $success = "Patient record updated successfully.";
        } else {
            // Create New
            $stmt = $pdo->prepare("INSERT INTO patients (firstName, lastName, email, phone, dobISO, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $phone, $dobISO, $address]);
            $success = "New patient registered successfully.";
        }
    }
}

// Check Edit Mode
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editingPatient = $stmt->fetch();
}

// Fetch All Patients
$patients = $pdo->query("SELECT * FROM patients ORDER BY id DESC")->fetchAll();
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
        <?= !empty($success) ? $success : ($_GET['msg'] === 'deleted' ? "Patient record removed." : "") ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= $editingPatient ? 'Edit Patient Profile' : 'Register New Patient' ?></h2>
    <form method="POST" action="patients.php">
        <input type="hidden" name="id" value="<?= $editingPatient['id'] ?? '' ?>">
        
        <div class="form-grid">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="firstName" required pattern="[A-Za-z\s]+" value="<?= htmlspecialchars($editingPatient['firstName'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="lastName" required pattern="[A-Za-z\s]+" value="<?= htmlspecialchars($editingPatient['lastName'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editingPatient['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone Number (10 digits) *</label>
                <input type="tel" name="phone" required pattern="[0-9]{10}" placeholder="9876543210" value="<?= htmlspecialchars($editingPatient['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth (dobISO) *</label>
                <input type="date" name="dobISO" max="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($editingPatient['dobISO'] ?? '') ?>">
            </div>
            <div class="form-group full-width">
                <label>Address *</label>
                <textarea name="address" rows="2" required><?= htmlspecialchars($editingPatient['address'] ?? '') ?></textarea>
            </div>
        </div>
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn btn-primary"><?= $editingPatient ? 'Update Patient' : 'Save Patient' ?></button>
            <?php if ($editingPatient): ?>
                <a href="patients.php" class="btn btn-warning">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>All Registered Patients</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Date of Birth</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patients)): ?>
                <tr><td colspan="6">No patients found.</td></tr>
            <?php else: ?>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['firstName'] . ' ' . $p['lastName']) ?></strong></td>
                        <td><?= htmlspecialchars($p['phone']) ?><br><small><?= htmlspecialchars($p['email']) ?></small></td>
                        <td><?= htmlspecialchars($p['dobISO']) ?></td>
                        <td><?= htmlspecialchars($p['address']) ?></td>
                        <td>
                            <a href="patients.php?edit=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="patients.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete patient profile?')" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>