<?php
// Database connection
$host = 'localhost';
$db   = 'master_clinic';
$user = 'root'; // Change this to your database username
$pass = '';     // Change this to your database password

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to master_clinic database<br>";

// First, let's fetch available branches to assign the admin to
$branchQuery = "SELECT branch_id, branch_code, branch_name FROM branches WHERE status = 'active'";
$branchResult = $conn->query($branchQuery);

$branches = [];
if ($branchResult->num_rows > 0) {
    while($row = $branchResult->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Admin details - MODIFY THESE VALUES AS NEEDED
$username = 'Jeremiah';
$password = 'password'; // plain text - will be hashed
$full_name = 'System Administrator';
$gender = 'other';
$phone = '+265 999 000 111';
$date_of_birth = '1985-01-15';
$address = 'Master Clinic Headquarters, Lilongwe';
$emergency_contact = 'IT Department';
$emergency_phone = '+265 999 000 112';
$notes = 'Master administrator with full system access';
$hire_date = '2020-01-01';
$email = 'jphesele@mchs.mw';
$branch_id = 1; // Change this based on your branch
$role = 'admin';
$status = 'active';
$created_by = null; // NULL for first admin

// Hash the password using PHP's password_hash (more secure than MySQL's PASSWORD())
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare the SQL insert for the users table
$sql = "INSERT INTO users (
            username, 
            password, 
            full_name, 
            gender, 
            phone, 
            date_of_birth, 
            address, 
            emergency_contact, 
            emergency_phone, 
            notes, 
            hire_date, 
            email, 
            branch_id, 
            role, 
            status, 
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Use prepared statement
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssssssissi", 
    $username, 
    $hashedPassword, 
    $full_name, 
    $gender, 
    $phone, 
    $date_of_birth, 
    $address, 
    $emergency_contact, 
    $emergency_phone, 
    $notes, 
    $hire_date, 
    $email, 
    $branch_id, 
    $role, 
    $status, 
    $created_by
);

// Execute and check
if ($stmt->execute()) {
    $admin_id = $conn->insert_id;
    echo "<h3 style='color: green;'>✓ Admin user added successfully!</h3>";
    echo "<p>Admin ID: " . $admin_id . "</p>";
    echo "<p>Username: " . $username . "</p>";
    echo "<p>Email: " . $email . "</p>";
    echo "<p>Role: " . $role . "</p>";
} else {
    echo "<h3 style='color: red;'>✗ Error adding admin user</h3>";
    echo "Error: " . $stmt->error . "<br>";
    
    // Check if username already exists
    if (strpos($stmt->error, 'Duplicate entry') !== false && strpos($stmt->error, 'username') !== false) {
        echo "<p style='color: orange;'>Tip: The username '" . $username . "' already exists. Try a different username.</p>";
    }
}

$stmt->close();

// Display available branches for reference
if (!empty($branches)) {
    echo "<h4>Available Branches:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>branch_id</th><th>branch_code</th><th>branch_name</th></tr>";
    foreach ($branches as $branch) {
        echo "<tr>";
        echo "<td>" . $branch['branch_id'] . "</td>";
        echo "<td>" . $branch['branch_code'] . "</td>";
        echo "<td>" . $branch['branch_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>Warning: No active branches found. Please add branches first.</p>";
}

$conn->close();
?>