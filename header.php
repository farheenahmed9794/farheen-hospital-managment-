<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <style>
        :root {
            --primary: #1976d2;
            --secondary: #0d47a1;
            --bg: #f4f6f9;
            --surface: #ffffff;
            --text: #212529;
            --success: #2e7d32;
            --danger: #d32f2f;
            --warning: #ed6c02;
            --border: #e0e0e0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; flex-direction: column; min-height: 100vh; }
        nav { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.25rem; font-weight: 700; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; padding: 0.4rem 0.8rem; border-radius: 4px; }
        .nav-links a:hover { background: #eef2f6; color: var(--primary); }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; flex: 1; width: 100%; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; text-align: center; }
        .kpi-card h3 { font-size: 0.9rem; color: #666; text-transform: uppercase; margin-bottom: 0.5rem; }
        .kpi-card .number { font-size: 2.2rem; font-weight: 700; color: var(--primary); }

        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; }
        .card h2 { margin-bottom: 1rem; font-size: 1.3rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { font-size: 0.85rem; font-weight: 600; color: #444; }
        input, select, textarea { border: 1px solid var(--border); border-radius: 4px; padding: 0.55rem; font-size: 0.9rem; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); ring: 2px solid #90caf9; }
        
        .btn { border: none; border-radius: 4px; padding: 0.6rem 1.2rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-sm { padding: 0.25rem 0.6rem; font-size: 0.8rem; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: var(--surface); }
        th, td { padding: 0.75rem 0.8rem; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { background: #fafafa; font-weight: 600; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-Scheduled { background: #e3f2fd; color: var(--primary); }
        .badge-Completed { background: #e8f5e9; color: var(--success); }
        .badge-Cancelled { background: #ffebee; color: var(--danger); }

        .alert { padding: 0.8rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .alert-danger { background: #ffebee; color: var(--danger); border: 1px solid #ffcdd2; }
        .alert-success { background: #e8f5e9; color: var(--success); border: 1px solid #c8e6c9; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="logo">🏥 MediCare HMS</a>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="doctors.php">Doctors</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </nav>
    <div class="container">