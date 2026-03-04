<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffcf9; }
        .reception-header { color: #854d0e; }
        .quick-action-card { transition: transform 0.2s; cursor: pointer; border: 1px solid #fef3c7; }
        .quick-action-card:hover { transform: translateY(-5px); background-color: #fef3c7; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h1 class="reception-header fw-bold">Hello, Front Desk</h1>
                <p class="text-secondary">3 check-ins currently waiting in the lobby.</p>
            </div>
            <div class="col-md-4 text-end">
                <div id="clock" class="h4 fw-light text-muted"></div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card p-3 quick-action-card" onclick="alert('Loading Calendar...')">
                    <h5 class="mb-0">📅 Book Appointment</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 quick-action-card" onclick="alert('Loading Billing...')">
                    <h5 class="mb-0">💳 Process Payment</h5>
                </div>
            </div>
        </div>
    </div>
    <script>
        setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }, 1000);
    </script>
</body>
</html>