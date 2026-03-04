<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0fdfa; }
        .hero-section { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; border-radius: 20px; }
        .btn-light-outline { border: 2px solid white; color: white; background: transparent; transition: 0.3s; }
        .btn-light-outline:hover { background: white; color: #0d9488; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="hero-section p-5 text-center shadow-lg">
            <h1 class="fw-light">Good Morning, Dental Team</h1>
            <h2 class="fw-bold mb-4">Ready for a bright day?</h2>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-light-outline px-4 py-2" onclick="showTime()">Sterilization Log</button>
                <button class="btn btn-light px-4 py-2 fw-bold" onclick="alert('Opening Chairside Assistant...')">Next Procedure</button>
            </div>
        </div>
    </div>
    <script>function showTime(){ alert('Current Shift: ' + new Date().toLocaleTimeString()); }</script>
</body>
=======
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0fdfa; }
        .hero-section { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; border-radius: 20px; }
        .btn-light-outline { border: 2px solid white; color: white; background: transparent; transition: 0.3s; }
        .btn-light-outline:hover { background: white; color: #0d9488; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="hero-section p-5 text-center shadow-lg">
            <h1 class="fw-light">Good Morning, Dental Team</h1>
            <h2 class="fw-bold mb-4">Ready for a bright day?</h2>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-light-outline px-4 py-2" onclick="showTime()">Sterilization Log</button>
                <button class="btn btn-light px-4 py-2 fw-bold" onclick="alert('Opening Chairside Assistant...')">Next Procedure</button>
            </div>
        </div>
    </div>
    <script>function showTime(){ alert('Current Shift: ' + new Date().toLocaleTimeString()); }</script>
</body>
>>>>>>> ebf5f55ccd0a1b48a75b40abdbae6c5de9fe43f4
</html>