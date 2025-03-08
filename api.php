<?php
// api.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Always use the default session settings.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/');
    session_start();
}
require 'config.php';
function respond($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
// Load .env file if available.
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    if ($env && isset($env['BREVO_API_KEY'])) {
        putenv("BREVO_API_KEY=" . $env['BREVO_API_KEY']);
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        respond(['status' => 'error', 'message' => 'Email and password required']);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_unset();
                session_destroy();
            }
            session_start();
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            respond([
                'status'  => 'success',
                'message' => 'Login successful',
                'user'    => $_SESSION['user']
            ]);
        } else {
            respond(['status' => 'error', 'message' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'check_login') {
    // Start the default session.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user = $_SESSION['user'] ?? null;
    session_write_close();

    if ($user) {
        respond(['status' => 'success', 'user' => $user]);
    } else {
        respond(['status' => 'error', 'message' => 'Not logged in']);
    }
}

// (Other API actions such as uploadAdminPhoto, updateContact, etc. remain unchanged.)



elseif ($action === 'getTenants') {
    // Query to fetch tenant details and gas usage info
    $query = "SELECT 
                u.id AS user_id,
                u.username, 
                tf.tenant_name, 
                tf.block, 
                tf.door_number AS door_no, 
                u.phone, 
                COALESCE(g.gas_consumed, 0) AS gas_consumed, 
                COALESCE(g.amount, 0) AS amount, 
                COALESCE(g.due_date, 'N/A') AS due_date, 
                COALESCE(g.status, 'N/A') AS status
              FROM users u
              LEFT JOIN tenant_fields tf ON u.id = tf.user_id
              LEFT JOIN gas_usage g ON u.id = g.user_id
              WHERE u.role = 'tenant'";
              
    // Use PDO for execution
    $stmt = $pdo->query($query);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond(['status' => 'success', 'tenants' => $tenants]);
}

/*---------------------------------------------------------
  1. Add a New Tenant
     Expects JSON body: { "username": "", "email": "", "phone": "", "password": "" }
---------------------------------------------------------*/
elseif ($action === 'addTenant') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['username']) || empty($input['email']) || empty($input['phone']) || empty($input['password'])) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $username = $input['username'];
    $email    = $input['email'];
    $phone    = $input['phone'];
    $password = password_hash($input['password'], PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, role) VALUES (?,?,?,?, 'tenant')");
    if ($stmt->execute([$username, $email, $phone, $password])) {
        respond(['status' => 'success', 'message' => 'Tenant added successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to add tenant']);
    }
}


elseif ($action === 'uploadPhoto') {
    // Ensure the logged-in user is a tenant.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        respond(['status' => 'error', 'message' => 'File upload failed']);
    }
    
    // Validate file type (allow only jpg, jpeg, png, gif)
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $fileName = $_FILES['photo']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        respond(['status' => 'error', 'message' => 'Invalid file type']);
    }
    
    // Define target directory (ensure this directory exists and is writable)
    $targetDir = "uploads/tenant_photos/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Generate a unique file name
    $newFileName = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $newFileName;
    
    // Move the uploaded file
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        $tenantId = $_SESSION['user']['id'];
        
        // Check if a photo record already exists for this tenant
        $stmt = $pdo->prepare("SELECT id FROM tenant_photos WHERE user_id = ?");
        $stmt->execute([$tenantId]);
        if ($stmt->rowCount() > 0) {
            // Update the existing record
            $stmt = $pdo->prepare("UPDATE tenant_photos SET photo_path = ?, updated_at = NOW() WHERE user_id = ?");
            $success = $stmt->execute([$targetFile, $tenantId]);
        } else {
            // Insert a new record
            $stmt = $pdo->prepare("INSERT INTO tenant_photos (user_id, photo_path) VALUES (?, ?)");
            $success = $stmt->execute([$tenantId, $targetFile]);
        }
        
        if ($success) {
            // Optionally update the session value.
            $_SESSION['user']['profile_photo'] = $targetFile;
            respond([
                'status'    => 'success',
                'message'   => 'Photo updated successfully',
                'photo_url' => $targetFile
            ]);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update photo in database']);
        }
    } else {
        respond(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
}


elseif ($action === 'updatePassword') {
    // Allow both tenant and admin to update password.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['tenant', 'admin'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['new_password'])) {
        respond(['status' => 'error', 'message' => 'Missing parameter: new_password']);
    }
    
    $userId = $_SESSION['user']['id'];
    
    // Hash the new password securely.
    $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$newHash, $userId]);
    
    if ($success) {
        respond(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update password']);
    }
}


elseif ($action === 'getTenantFields') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $tenant_id = $_SESSION['user']['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT tf.tenant_name, tf.door_number, tf.block, tf.floor, tf.configuration, tf.maintenance_cost, tp.photo_path
            FROM tenant_fields tf
            LEFT JOIN tenant_photos tp ON tf.user_id = tp.user_id
            WHERE tf.user_id = ?
        ");
        $stmt->execute([$tenant_id]);
        $fields = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fields) {
            respond(['status' => 'success', 'fields' => $fields]);
        } else {
            respond(['status' => 'error', 'message' => 'Tenant fields not found']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}


elseif ($action === 'getTenantProfile') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $userId = $_SESSION['user']['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.username, u.email, u.phone, tf.tenant_name 
            FROM users u 
            LEFT JOIN tenant_fields tf ON u.id = tf.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            respond(['status' => 'success', 'profile' => $profile]);
        } else {
            respond(['status' => 'error', 'message' => 'Profile not found']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
      
/*---------------------------------------------------------
  2. Add Tenant Fields
     Expects JSON body: { "username": "", "door_number": "", "floor": "", "block": "", "tenant_name": "", "configuration": "", "maintenance_cost": "" }
---------------------------------------------------------*/
elseif ($action === 'addTenantFields') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (
        empty($input['username']) || 
        empty($input['door_number']) || 
        empty($input['floor']) || 
        empty($input['block']) || 
        empty($input['tenant_name']) || 
        empty($input['configuration']) ||
        !isset($input['maintenance_cost'])
    ) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $username = $input['username'];
        // Retrieve tenant id from the users table for the given username and role 'tenant'
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'tenant'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            respond(['status' => 'error', 'message' => 'Tenant not found']);
        }
        
        $user_id = $user['id'];
        
        // Update or insert the tenant_fields record
        $stmt = $pdo->prepare("SELECT id FROM tenant_fields WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE tenant_fields 
                                   SET door_number = ?, floor = ?, block = ?, tenant_name = ?, configuration = ?, maintenance_cost = ? 
                                   WHERE user_id = ?");
            $tenantSuccess = $stmt->execute([
                $input['door_number'],
                $input['floor'],
                $input['block'],
                $input['tenant_name'],
                $input['configuration'],
                $input['maintenance_cost'],
                $user_id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tenant_fields (user_id, door_number, floor, block, tenant_name, configuration, maintenance_cost) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $tenantSuccess = $stmt->execute([
                $user_id,
                $input['door_number'],
                $input['floor'],
                $input['block'],
                $input['tenant_name'],
                $input['configuration'],
                $input['maintenance_cost']
            ]);
        }
        
        // Check for an existing maintenance record that is not marked as "Paid"
        $stmt = $pdo->prepare("SELECT * FROM maintenance WHERE user_id = ? AND status != 'Paid' ORDER BY due_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $unpaidRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($unpaidRecord) {
            $stmt = $pdo->prepare("UPDATE maintenance 
                                   SET tenant_name = ?, door_number = ?, block = ?, floor = ?, maintenance_cost = ? 
                                   WHERE id = ?");
            $maintenanceSuccess = $stmt->execute([
                $input['tenant_name'],
                $input['door_number'],
                $input['block'],
                $input['floor'],
                $input['maintenance_cost'],
                $unpaidRecord['id']
            ]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM maintenance WHERE user_id = ? ORDER BY due_date DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastRecord && $lastRecord['status'] === "Paid") {
                $lastDueDate = new DateTime($lastRecord['due_date']);
                $lastDueDate->modify('+1 month');
                $lastDueDate->setDate($lastDueDate->format('Y'), $lastDueDate->format('m'), 10);
                $dueDate = $lastDueDate->format('Y-m-d');
            } else {
                $today = new DateTime();
                if ($today->format('j') > 10) {
                    $dueDate = (new DateTime('first day of next month'))->format('Y-m-10');
                } else {
                    $dueDate = $today->format('Y-m-10');
                }
            }
            
            $dueDateObj = new DateTime($dueDate);
            $currentDate = new DateTime();
            $status = ($currentDate > $dueDateObj) ? "Overdue" : "Unpaid";
            
            $stmt = $pdo->prepare("INSERT INTO maintenance (user_id, tenant_name, door_number, block, floor, due_date, maintenance_cost, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $maintenanceSuccess = $stmt->execute([
                $user_id,
                $input['tenant_name'],
                $input['door_number'],
                $input['block'],
                $input['floor'],
                $dueDate,
                $input['maintenance_cost'],
                $status
            ]);
        }
        
        respond(($tenantSuccess && $maintenanceSuccess) 
            ? ['status' => 'success', 'message' => 'Tenant fields and maintenance updated successfully'] 
            : ['status' => 'error', 'message' => 'Failed to update tenant fields or maintenance']);
            
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}




/*---------------------------------------------------------
  3. Manage Tenants / Search Tenant
     GET parameter: username (search term)
---------------------------------------------------------*/
elseif ($action === 'searchTenant') {
    // Start session if not active.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $search = $_GET['username'] ?? '';
        // Join the users table with tenant_fields to retrieve configuration
        $stmt = $pdo->prepare("
            SELECT 
              users.username, 
              users.email, 
              users.phone, 
              tenant_fields.configuration, 
              users.created_at 
            FROM users 
            LEFT JOIN tenant_fields ON users.id = tenant_fields.user_id 
            WHERE users.role = 'tenant' 
              AND users.username LIKE ?
        ");
        $stmt->execute(['%' . $search . '%']);
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tenants' => $tenants]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'deleteTenant') {
    // Start session if not active.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $username = $_GET['username'] ?? '';
    if (empty($username)) {
        respond(['status' => 'error', 'message' => 'Missing tenant username']);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND role = 'tenant'");
        if ($stmt->execute([$username])) {
            respond(['status' => 'success', 'message' => 'Tenant deleted successfully']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to delete tenant']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
/*---------------------------------------------------------
  4. Dashboard: Get Total Tenants
---------------------------------------------------------*/
elseif ($action === 'getTotalTenants') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'tenant'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'total' => $row['total']]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
  5. Dashboard: Get Latest Tenants
     GET parameters: bhk, period (days)
---------------------------------------------------------*/
elseif ($action === 'getLatestTenants') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $bhk = $_GET['bhk'] ?? '';
    $period = $_GET['period'] ?? '';
    $query = "SELECT u.username, u.email, u.phone, tf.configuration, u.created_at
              FROM users u
              LEFT JOIN tenant_fields tf ON u.id = tf.user_id
              WHERE u.role = 'tenant'";
    $params = [];
    if ($bhk !== '') {
        $query .= " AND tf.configuration = ?";
        $params[] = $bhk;
    }
    if ($period !== '') {
        $query .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $period;
    }
    $query .= " ORDER BY u.created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'tenants' => $tenants]);
}

/*---------------------------------------------------------
  6. Update EB Details
     Expects JSON body: { "username": "", "detail": "" }
---------------------------------------------------------*/
elseif ($action === 'getEBDebts') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        // Fetch records from the eb_bill table. Join with users to get tenant phone if needed.
        $stmt = $pdo->query("
            SELECT eb.user_id, eb.tenant_name, eb.block, eb.door_number, eb.floor, eb.paid_on, eb.status, u.phone
            FROM eb_bill eb
            LEFT JOIN users u ON eb.user_id = u.id
            ORDER BY eb.paid_on DESC, eb.tenant_name ASC
        ");
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tenants' => $tenants]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}


elseif ($action === 'getTenantEBDebt') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $tenantId = $_SESSION['user']['id'];
        // Get the most recent eb_bill record for the tenant (if any)
        $stmt = $pdo->prepare("
            SELECT tf.tenant_name, tf.block, tf.door_number, tf.floor, u.phone, eb.status, eb.paid_on 
            FROM tenant_fields tf 
            JOIN users u ON tf.user_id = u.id 
            LEFT JOIN (
                SELECT *
                FROM eb_bill
                WHERE user_id = ?
                ORDER BY paid_on DESC
                LIMIT 1
            ) eb ON tf.user_id = eb.user_id 
            WHERE tf.user_id = ?
        ");
        $stmt->execute([$tenantId, $tenantId]);
        $eb = $stmt->fetch(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'eb' => $eb]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'markAsPaid') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $tenantId = $_SESSION['user']['id'];
        // Check if a payment for the current month already exists.
        $stmt = $pdo->prepare("
            SELECT id 
            FROM eb_bill 
            WHERE user_id = ? 
              AND YEAR(paid_on) = YEAR(CURDATE()) 
              AND MONTH(paid_on) = MONTH(CURDATE())
        ");
        $stmt->execute([$tenantId]);
        if ($stmt->rowCount() > 0) {
            // A record already exists for this month.
            respond(['status' => 'error', 'message' => 'You have already marked your bill as paid for this month.']);
        }
        
        // Retrieve tenant details from tenant_fields and users
        $stmt = $pdo->prepare("
            SELECT tf.tenant_name, tf.block, tf.door_number, tf.floor, u.phone
            FROM tenant_fields tf
            JOIN users u ON tf.user_id = u.id
            WHERE tf.user_id = ?
        ");
        $stmt->execute([$tenantId]);
        $tenantDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tenantDetails) {
            respond(['status' => 'error', 'message' => 'Tenant details not found']);
        }
        
        // Prepare to insert a new eb_bill record for the current month.
        $stmt = $pdo->prepare("
            INSERT INTO eb_bill (user_id, tenant_name, block, door_number, floor, paid_on, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'paid')
        ");
        $success = $stmt->execute([
            $tenantId,
            $tenantDetails['tenant_name'],
            $tenantDetails['block'],
            $tenantDetails['door_number'],
            $tenantDetails['floor']
        ]);
        
        if ($success) {
            respond(['status' => 'success', 'message' => 'EB bill marked as paid for this month']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update EB bill']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}





elseif ($action === 'getAdminProfile') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Ensure the logged-in user is an admin.
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $userId = $_SESSION['user']['id'];
    try {
        $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            respond(['status' => 'success', 'profile' => $profile]);
        } else {
            respond(['status' => 'error', 'message' => 'Profile not found']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ---------------
// UPLOAD ADMIN PHOTO
// ---------------

elseif ($action === 'uploadAdminPhoto') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Check that the user is an admin.
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    // Verify a file was uploaded without error.
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        respond(['status' => 'error', 'message' => 'File upload failed']);
    }
    
    // Validate file type.
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $fileName = $_FILES['photo']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        respond(['status' => 'error', 'message' => 'Invalid file type']);
    }
    
    // Define target directory for admin photos.
    $targetDir = "uploads/admin_photos/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Generate a unique file name.
    $newFileName = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $newFileName;
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        $adminId = $_SESSION['user']['id'];
        // Check if a photo record exists for this admin.
        $stmt = $pdo->prepare("SELECT id FROM admin_photos WHERE user_id = ?");
        $stmt->execute([$adminId]);
        if ($stmt->rowCount() > 0) {
            // Update the existing record.
            $stmt = $pdo->prepare("UPDATE admin_photos SET photo_path = ?, updated_at = NOW() WHERE user_id = ?");
            $success = $stmt->execute([$targetFile, $adminId]);
        } else {
            // Insert a new record.
            $stmt = $pdo->prepare("INSERT INTO admin_photos (user_id, photo_path) VALUES (?, ?)");
            $success = $stmt->execute([$adminId, $targetFile]);
        }
        if ($success) {
            // Optionally update the session.
            $_SESSION['user']['profile_photo'] = $targetFile;
            respond([
                'status'    => 'success',
                'message'   => 'Photo updated successfully',
                'photo_url' => $targetFile
            ]);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update photo in database']);
        }
    } else {
        respond(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
}

// ---------------
// UPDATE ADMIN CONTACT INFORMATION (Email & Phone)
// ---------------

elseif ($action === 'updateContact') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Ensure that only admin users can update contact info.
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['email']) || !isset($input['phone'])) {
        respond(['status' => 'error', 'message' => 'Missing parameters: email and phone']);
    }
    
    $userId = $_SESSION['user']['id'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
        $success = $stmt->execute([$input['email'], $input['phone'], $userId]);
        if ($success) {
            respond(['status' => 'success', 'message' => 'Contact information updated successfully']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update contact information']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ---------------
// UPDATE GAS BILL (Admin Only)
// ---------------

elseif ($action === 'updateGasBill') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? null;
        $gasConsumed = $data['gasConsumed'] ?? null;
        $amount = $data['amount'] ?? null;
        
        if (!$username || $gasConsumed === null || $amount === null) {
            respond(['status' => 'error', 'message' => 'Missing parameters']);
        }
    
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            respond(['status' => 'error', 'message' => 'User not found']);
        }
    
        $userId = $user['id'];
    
        $dueDate = date('Y-m-d', strtotime('+5 days'));
        $today = date('Y-m-d');
        $status = ($today > $dueDate) ? 'Overdue' : 'unpaid';
    
        $stmt = $pdo->prepare("SELECT * FROM gas_usage WHERE user_id = ?");
        $stmt->execute([$userId]);
    
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE gas_usage SET gas_consumed = ?, amount = ?, due_date = ?, status = ?, tenant_username = ? WHERE user_id = ?");
            $success = $stmt->execute([$gasConsumed, $amount, $dueDate, $status, $username, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO gas_usage (user_id, tenant_username, gas_consumed, amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([$userId, $username, $gasConsumed, $amount, $dueDate, $status]);
        }
    
        if ($success) {
            respond(['status' => 'success', 'message' => 'Gas bill updated successfully!']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update gas bill']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
// ---------------
// GET GAS USAGE (Tenant Only)
// ---------------

elseif ($action === 'getGasUsage') {
    // Ensure tenant session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $userId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("
            SELECT 
                gu.id, 
                tf.tenant_name, 
                tf.block, 
                tf.door_number, 
                tf.floor,
                u.phone, 
                u.email,
                gu.gas_consumed, 
                gu.amount, 
                gu.created_at AS bill_date,  
                gu.due_date, 
                gu.status,
                gu.user_id
            FROM gas_usage gu
            JOIN tenant_fields tf ON gu.user_id = tf.user_id
            JOIN users u ON gu.user_id = u.id
            WHERE gu.user_id = ?
            ORDER BY gu.created_at DESC
        ");
        $stmt->execute([$userId]);
        $gasUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'gas_usage' => $gasUsage]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ---------------
// Create Gas Order API (Razorpay integration)
// (This block remains unchanged.)
// ---------------

elseif ($action === 'createGasOrder') {
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['id'])) {
        respond(['status'=>'error','message'=>'Missing parameters']);
    }
    $recordId = $data['id'];

    $stmt = $pdo->prepare("SELECT amount FROM gas_usage WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $_SESSION['user']['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$record){
         respond(['status'=>'error','message'=>'No unpaid gas usage record found for this record']);
    }
    $amount = $record['amount'];
    $amount_paise = $amount * 100;
    $receipt = 'order_rcptid_' . uniqid();
    $orderData = [
       "amount" => $amount_paise,
       "currency" => "INR",
       "receipt" => $receipt,
       "payment_capture" => 1
    ];
    $orderDataJson = json_encode($orderData);
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $orderDataJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if($err){
       respond(['status'=>'error','message'=>'cURL Error: ' . $err]);
    } else {
       $responseData = json_decode($response, true);
       if(isset($responseData['id'])){
           respond(['status'=>'success', 'order_id'=>$responseData['id'], 'message'=>'Order created successfully']);
       } else {
           respond(['status'=>'error', 'message'=>'Order creation failed: ' . $response]);
       }
    }
}

// ---------------
// Capture Gas Payment API
// (This block remains largely unchanged; it uses session_start() if needed.)
// ---------------

elseif ($action === 'captureGasPayment') {
    if (!isset($_SESSION['user'])) {
       respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(['status'=>'error','message'=>'Invalid JSON input']);
    }
    
    if (
        empty($data['razorpay_payment_id']) || 
        empty($data['razorpay_order_id']) || 
        empty($data['razorpay_signature']) || 
        empty($data['id'])
    ) {
       respond(['status'=>'error','message'=>'Missing parameters']);
    }
    
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    $generated_signature = hash_hmac(
        'sha256',
        $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
        $keySecret
    );
    
    if ($generated_signature !== $data['razorpay_signature']) {
        respond(['status'=>'error','message'=>'Payment signature verification failed']);
    }
    
    $recordId = $data['id'];
    $userId = $_SESSION['user']['id'];
    
    $stmt = $pdo->prepare("SELECT amount, status FROM gas_usage WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $userId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
       respond(['status'=>'error','message'=>'No unpaid gas usage record found for this record']);
    }
    
    $amount = $record['amount'];
    $amount_paise = $amount * 100;
    $paymentId = $data['razorpay_payment_id'];
    
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $captureUrl = "https://api.razorpay.com/v1/payments/{$paymentId}/capture";
    $captureData = [
       "amount"   => $amount_paise,
       "currency" => "INR"
    ];
    $captureDataJson = json_encode($captureData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $captureUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $captureDataJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
       respond(['status'=>'error', 'message'=>'cURL Error during capture: ' . $err]);
    }
    
    $responseData = json_decode($response, true);
    $updateSuccess = false;
    
    if (isset($responseData['status']) && $responseData['status'] === 'captured') {
        $updateSuccess = true;
    }
    else if (isset($responseData['error']) && stripos($responseData['error']['description'], "already been captured") !== false) {
        $updateSuccess = true;
    }
    else {
       respond(['status'=>'error', 'message'=>'Payment capture failed: ' . $response]);
    }
    
    if ($updateSuccess) {
       $stmtInsert = $pdo->prepare("INSERT INTO payments (user_id, payment_id) VALUES (?, ?)");
       $stmtInsert->execute([$userId, $paymentId]);
       
       $stmtUpdate = $pdo->prepare("UPDATE gas_usage SET status = 'paid', paid_on = NOW() WHERE id = :id AND user_id = :uid AND status <> 'paid'");
       $stmtUpdate->execute([':id' => $recordId, ':uid' => $userId]);
       
       if ($stmtUpdate->rowCount() > 0) {
           respond(['status'=>'success', 'message'=>'Gas bill paid successfully']);
       } else {
           $stmtCheck = $pdo->prepare("SELECT status FROM gas_usage WHERE id = :id");
           $stmtCheck->execute([':id' => $recordId]);
           $updatedRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);
           if ($updatedRecord && strtolower($updatedRecord['status']) === 'paid') {
                respond(['status'=>'success', 'message'=>'Gas bill paid successfully']);
           } else {
                respond(['status'=>'error', 'message'=>'Payment captured but failed to update record']);
           }
       }
    }
}

// Fetch Payment Details API remains unchanged.
elseif ($action === 'fetchPaymentDetails') {
    if (!isset($_SESSION['user'])) {
       respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['razorpay_payment_id'])) {
       respond(['status'=>'error','message'=>'Missing razorpay_payment_id parameter']);
    }
    $paymentId = $data['razorpay_payment_id'];
    
    $stmtPayment = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmtPayment->execute([$paymentId]);
    $paymentRecord = $stmtPayment->fetch(PDO::FETCH_ASSOC);
    if (!$paymentRecord) {
       respond(['status' => 'error', 'message' => 'Payment record not found in database']);
    }
    
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    $fetchUrl = "https://api.razorpay.com/v1/payments/{$paymentId}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fetchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    $fetchResponse = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if($err){
       respond(['status'=>'error', 'message'=>'cURL Error during fetch: ' . $err]);
    }
    
    $paymentDetails = json_decode($fetchResponse, true);
    
    if (isset($paymentDetails['status']) && $paymentDetails['status'] === 'captured') {
        $stmtUpdate = $pdo->prepare("UPDATE gas_usage SET status = 'paid', paid_on = NOW() WHERE user_id = ? AND status <> 'paid' ORDER BY created_at DESC LIMIT 1");
        $stmtUpdate->execute([$paymentRecord['user_id']]);
    }
    
    respond(['status'=>'success', 'paymentDetails' => $paymentDetails]);
}


/*---------------------------------------------------------
  8. Notification: Get Notifications
---------------------------------------------------------*/
elseif ($action === 'getNotifications') {
    // Ensure a session is active (both admin and tenant allowed)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'tenant'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->query("SELECT id, message, created_at FROM notifications ORDER BY created_at DESC");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'notifications' => $notifications]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
  9. Notification: Send Notification
     Expects JSON body: { "message": "" }
---------------------------------------------------------*/
elseif ($action === 'sendNotification') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Check for empty message
    if (empty($input['message'])) {
        respond(['status' => 'error', 'message' => 'Message required']);
    }
    
    // Ensure the user is an admin
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        // 1. Insert the notification into the database.
        $stmt = $pdo->prepare("INSERT INTO notifications (message) VALUES (?)");
        $stmt->execute([$input['message']]);
    
        // 2. Fetch all tenant emails and names.
        $query = "SELECT u.email, tf.tenant_name 
                  FROM users u 
                  INNER JOIN tenant_fields tf ON u.id = tf.user_id";
        $stmt = $pdo->query($query);
        $recipients = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['email']) && !empty($row['tenant_name'])) {
                $recipients[] = [
                    "email" => $row['email'],
                    "name"  => $row['tenant_name']
                ];
            }
        }
    
        if (empty($recipients)) {
            respond(['status' => 'error', 'message' => 'No tenant emails found to send notification']);
        }
    
        // 3. Prepare the email data for Brevo.
        $apiKey = getenv('BREVO_API_KEY');
        $emailData = [
            "sender" => [
                "name"  => "Townment Admin Notification",
                "email" => "vinothkrish0803@gmail.com"
            ],
            "to" => $recipients,
            "subject" => "New Notification from Admin",
            "htmlContent" => "<p>" . nl2br(htmlentities($input['message'])) . "</p>"
        ];
    
        // 4. Send the email using Brevo's REST API (using cURL)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json",
            "api-key: $apiKey",
            "content-type: application/json"
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        if ($httpCode >= 200 && $httpCode < 300) {
            respond(['status' => 'success', 'message' => 'Notification sent successfully']);
        } else {
            respond([
                'status' => 'error',
                'message' => 'Notification saved but email sending failed',
                'api_response' => json_decode($response, true)
            ]);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 10. Tickets: Get All Raised Tickets
---------------------------------------------------------*/
elseif ($action === 'getallTickets') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->query("SELECT t.id, u.username, t.raised_date, t.issue, t.status
                             FROM tickets t 
                             JOIN users u ON t.user_id = u.id 
                             ORDER BY t.raised_date DESC");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 11. Tickets: Update Ticket Status
     Expects JSON body: { "ticket_id": number, "status": "opened"|"inprogress"|"closed" }
---------------------------------------------------------*/
elseif ($action === 'updateTicketStatus') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['ticket_id']) || empty($input['status'])) {
        respond(['status' => 'error', 'message' => 'Ticket ID and status required']);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        if ($stmt->execute([$input['status'], $input['ticket_id']])) {
            respond(['status' => 'success', 'message' => 'Ticket status updated']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update ticket status']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'raiseTicket') {
    // Ensure the user is logged in (tenant or admin may raise a ticket)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['issue'])) {
        respond(['status' => 'error', 'message' => 'Issue description is required']);
    }
    
    $user_id = $_SESSION['user']['id'];
    $issue = trim($data['issue']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, issue, status) VALUES (?, ?, 'opened')");
        if ($stmt->execute([$user_id, $issue])) {
            respond(['status' => 'success', 'message' => 'Ticket raised successfully']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to raise ticket']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'getNewTickets') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.user_id, u.username, t.raised_date, t.issue, t.status
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.raised_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY t.raised_date DESC
        ");
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'getTickets') {
    // Ensure the user is logged in
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $user = $_SESSION['user'];
    $params = [];
    $query = "SELECT raised_date, issue, status FROM tickets ";
    
    // Restrict tenants to only their tickets
    if ($user['role'] === 'tenant') {
        $query .= "WHERE user_id = ? ";
        $params[] = $user['id'];
    }
    
    // Apply status filter if provided
    if (!empty($_GET['status'])) {
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "status = ? ";
        $params[] = $_GET['status'];
    }
    
    // Apply period filter (in days) if provided
    if (!empty($_GET['period'])) {
        $period = (int) $_GET['period'];
        $thresholdDate = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "raised_date >= ? ";
        $params[] = $thresholdDate;
    }
    
    $query .= "ORDER BY raised_date DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 12. Maintenance: Get Maintenance Records 
---------------------------------------------------------*/
elseif ($action === 'getMaintenance') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }

    $query = "SELECT tenant_name, door_number, block, floor, status, due_date, maintenance_cost, paid_on FROM maintenance";
    $conditions = [];
    $params = [];

    if (!empty($_GET['search'])) {
        $conditions[] = "tenant_name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
    }
    
    if (!empty($_GET['daterange'])) {
        $days = (int) $_GET['daterange'];
        $conditions[] = "paid_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    if (!empty($_GET['block'])) {
        $conditions[] = "block = ?";
        $params[] = $_GET['block'];
    }
    
    if (!empty($_GET['floor'])) {
        $conditions[] = "floor = ?";
        $params[] = $_GET['floor'];
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query .= " ORDER BY id DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'maintenance' => $maintenance]);
    } catch (Exception $e) {
        respond(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'createMaintenanceOrder') {
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['id'])) {
        respond(['status'=>'error','message'=>'Missing parameters']);
    }
    $recordId = $data['id'];

    $stmt = $pdo->prepare("SELECT maintenance_cost FROM maintenance WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $_SESSION['user']['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
         respond(['status'=>'error','message'=>'No unpaid maintenance record found for this record']);
    }
    $amount = $record['maintenance_cost'];
    $amount_paise = $amount * 100;
    $receipt = 'order_rcptid_' . uniqid();
    $orderData = [
       "amount" => $amount_paise,
       "currency" => "INR",
       "receipt" => $receipt,
       "payment_capture" => 1
    ];
    $orderDataJson = json_encode($orderData);
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $orderDataJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
       respond(['status'=>'error', 'message'=>'cURL Error: ' . $err]);
    } else {
       $responseData = json_decode($response, true);
       if (isset($responseData['id'])) {
           respond(['status'=>'success', 'order_id'=>$responseData['id'], 'message'=>'Order created successfully']);
       } else {
           respond(['status'=>'error', 'message'=>'Order creation failed: ' . $response]);
       }
    }
}


elseif ($action === 'getNotifications') {
    // Ensure a session is active (both admin and tenant allowed)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'tenant'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->query("SELECT id, message, created_at FROM notifications ORDER BY created_at DESC");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'notifications' => $notifications]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
  9. Notification: Send Notification
     Expects JSON body: { "message": "" }
---------------------------------------------------------*/
elseif ($action === 'sendNotification') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Check for empty message
    if (empty($input['message'])) {
        respond(['status' => 'error', 'message' => 'Message required']);
    }
    
    // Ensure the user is an admin
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        // 1. Insert the notification into the database.
        $stmt = $pdo->prepare("INSERT INTO notifications (message) VALUES (?)");
        $stmt->execute([$input['message']]);
    
        // 2. Fetch all tenant emails and names.
        $query = "SELECT u.email, tf.tenant_name 
                  FROM users u 
                  INNER JOIN tenant_fields tf ON u.id = tf.user_id";
        $stmt = $pdo->query($query);
        $recipients = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['email']) && !empty($row['tenant_name'])) {
                $recipients[] = [
                    "email" => $row['email'],
                    "name"  => $row['tenant_name']
                ];
            }
        }
    
        if (empty($recipients)) {
            respond(['status' => 'error', 'message' => 'No tenant emails found to send notification']);
        }
    
        // 3. Prepare the email data for Brevo.
        $apiKey = getenv('BREVO_API_KEY');
        $emailData = [
            "sender" => [
                "name"  => "Townment Admin Notification",
                "email" => "vinothkrish0803@gmail.com"
            ],
            "to" => $recipients,
            "subject" => "New Notification from Admin",
            "htmlContent" => "<p>" . nl2br(htmlentities($input['message'])) . "</p>"
        ];
    
        // 4. Send the email using Brevo's REST API (using cURL)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json",
            "api-key: $apiKey",
            "content-type: application/json"
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        if ($httpCode >= 200 && $httpCode < 300) {
            respond(['status' => 'success', 'message' => 'Notification sent successfully']);
        } else {
            respond([
                'status' => 'error',
                'message' => 'Notification saved but email sending failed',
                'api_response' => json_decode($response, true)
            ]);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 10. Tickets: Get All Raised Tickets
---------------------------------------------------------*/
elseif ($action === 'getallTickets') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->query("SELECT t.id, u.username, t.raised_date, t.issue, t.status
                             FROM tickets t 
                             JOIN users u ON t.user_id = u.id 
                             ORDER BY t.raised_date DESC");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 11. Tickets: Update Ticket Status
     Expects JSON body: { "ticket_id": number, "status": "opened"|"inprogress"|"closed" }
---------------------------------------------------------*/
elseif ($action === 'updateTicketStatus') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['ticket_id']) || empty($input['status'])) {
        respond(['status' => 'error', 'message' => 'Ticket ID and status required']);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        if ($stmt->execute([$input['status'], $input['ticket_id']])) {
            respond(['status' => 'success', 'message' => 'Ticket status updated']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update ticket status']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'raiseTicket') {
    // Ensure the user is logged in (tenant or admin may raise a ticket)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['issue'])) {
        respond(['status' => 'error', 'message' => 'Issue description is required']);
    }
    
    $user_id = $_SESSION['user']['id'];
    $issue = trim($data['issue']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, issue, status) VALUES (?, ?, 'opened')");
        if ($stmt->execute([$user_id, $issue])) {
            respond(['status' => 'success', 'message' => 'Ticket raised successfully']);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to raise ticket']);
        }
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'getNewTickets') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.user_id, u.username, t.raised_date, t.issue, t.status
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.raised_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY t.raised_date DESC
        ");
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

elseif ($action === 'getTickets') {
    // Ensure the user is logged in
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $user = $_SESSION['user'];
    $params = [];
    $query = "SELECT raised_date, issue, status FROM tickets ";
    
    // Restrict tenants to only their tickets
    if ($user['role'] === 'tenant') {
        $query .= "WHERE user_id = ? ";
        $params[] = $user['id'];
    }
    
    // Apply status filter if provided
    if (!empty($_GET['status'])) {
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "status = ? ";
        $params[] = $_GET['status'];
    }
    
    // Apply period filter (in days) if provided
    if (!empty($_GET['period'])) {
        $period = (int) $_GET['period'];
        $thresholdDate = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "raised_date >= ? ";
        $params[] = $thresholdDate;
    }
    
    $query .= "ORDER BY raised_date DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'tickets' => $tickets]);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/*---------------------------------------------------------
 12. Maintenance: Get Maintenance Records 
---------------------------------------------------------*/
elseif ($action === 'getMaintenance') {
    // Ensure admin session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }

    $query = "SELECT tenant_name, door_number, block, floor, status, due_date, maintenance_cost, paid_on FROM maintenance";
    $conditions = [];
    $params = [];

    if (!empty($_GET['search'])) {
        $conditions[] = "tenant_name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
    }
    
    if (!empty($_GET['daterange'])) {
        $days = (int) $_GET['daterange'];
        $conditions[] = "paid_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    if (!empty($_GET['block'])) {
        $conditions[] = "block = ?";
        $params[] = $_GET['block'];
    }
    
    if (!empty($_GET['floor'])) {
        $conditions[] = "floor = ?";
        $params[] = $_GET['floor'];
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query .= " ORDER BY id DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'maintenance' => $maintenance]);
    } catch (Exception $e) {
        respond(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'createMaintenanceOrder') {
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['id'])) {
        respond(['status'=>'error','message'=>'Missing parameters']);
    }
    $recordId = $data['id'];

    $stmt = $pdo->prepare("SELECT maintenance_cost FROM maintenance WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $_SESSION['user']['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
         respond(['status'=>'error','message'=>'No unpaid maintenance record found for this record']);
    }
    $amount = $record['maintenance_cost'];
    $amount_paise = $amount * 100;
    $receipt = 'order_rcptid_' . uniqid();
    $orderData = [
       "amount" => $amount_paise,
       "currency" => "INR",
       "receipt" => $receipt,
       "payment_capture" => 1
    ];
    $orderDataJson = json_encode($orderData);
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $orderDataJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
       respond(['status'=>'error', 'message'=>'cURL Error: ' . $err]);
    } else {
       $responseData = json_decode($response, true);
       if (isset($responseData['id'])) {
           respond(['status'=>'success', 'order_id'=>$responseData['id'], 'message'=>'Order created successfully']);
       } else {
           respond(['status'=>'error', 'message'=>'Order creation failed: ' . $response]);
       }
    }
}

elseif ($action === 'captureMaintenancePayment') {
    if (!isset($_SESSION['user'])) {
       respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(['status'=>'error','message'=>'Invalid JSON input']);
    }
    
    if (
        empty($data['razorpay_payment_id']) || 
        empty($data['razorpay_order_id']) || 
        empty($data['razorpay_signature']) || 
        empty($data['id'])
    ) {
       respond(['status'=>'error','message'=>'Missing parameters']);
    }
    
    // Verify the payment signature manually.
    $keySecret = "iuS4nffZT8HodgJEazNPmAXP";
    $generated_signature = hash_hmac(
        'sha256',
        $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
        $keySecret
    );
    
    if ($generated_signature !== $data['razorpay_signature']) {
        respond(['status'=>'error','message'=>'Payment signature verification failed']);
    }
    
    $recordId = $data['id'];
    $userId = $_SESSION['user']['id'];
    
    // Verify that the record belongs to the logged-in tenant and is unpaid.
    $stmt = $pdo->prepare("SELECT maintenance_cost, status FROM maintenance WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $userId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
       respond(['status'=>'error','message'=>'No unpaid maintenance record found for this record']);
    }
    
    $amount = $record['maintenance_cost'];
    $amount_paise = $amount * 100;
    $paymentId = $data['razorpay_payment_id'];
    
    // Prepare Razorpay capture request.
    $keyId = "rzp_test_QBNbWNS9QSRoaK";
    $captureUrl = "https://api.razorpay.com/v1/payments/{$paymentId}/capture";
    $captureData = [
       "amount"   => $amount_paise,
       "currency" => "INR"
    ];
    $captureDataJson = json_encode($captureData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $captureUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $captureDataJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
       respond(['status'=>'error', 'message'=>'cURL Error during capture: ' . $err]);
    }
    
    $responseData = json_decode($response, true);
    $updateSuccess = false;
    
    // Check if Razorpay confirms a successful capture.
    if (isset($responseData['status']) && $responseData['status'] === 'captured') {
        $updateSuccess = true;
    }
    // If Razorpay returns an error stating the payment "already been captured", treat that as success.
    else if (isset($responseData['error']) && stripos($responseData['error']['description'], "already been captured") !== false) {
        $updateSuccess = true;
    } else {
       respond(['status'=>'error', 'message'=>'Payment capture failed: ' . $response]);
    }
    
    if ($updateSuccess) {
       // Record the payment.
       $stmtInsert = $pdo->prepare("INSERT INTO payments (user_id, payment_id) VALUES (?, ?)");
       $stmtInsert->execute([$userId, $paymentId]);
       
       // Update the maintenance record to mark it as paid.
       $stmtUpdate = $pdo->prepare("UPDATE maintenance SET status = 'paid', paid_on = NOW() WHERE id = :id AND user_id = :uid AND status <> 'paid'");
       $stmtUpdate->execute([':id' => $recordId, ':uid' => $userId]);
       
       if ($stmtUpdate->rowCount() > 0) {
           respond(['status'=>'success', 'message'=>'Maintenance fee paid successfully']);
       } else {
           // Fallback: verify the status.
           $stmtCheck = $pdo->prepare("SELECT status FROM maintenance WHERE id = :id");
           $stmtCheck->execute([':id' => $recordId]);
           $updatedRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);
           if ($updatedRecord && strtolower($updatedRecord['status']) === 'paid') {
                respond(['status'=>'success', 'message'=>'Maintenance fee paid successfully']);
           } else {
                respond(['status'=>'error', 'message'=>'Payment captured but failed to update record']);
           }
       }
    }
}

elseif ($action === 'getTenantMaintenance') {
    // Ensure tenant session is active using the default session.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $userId = $_SESSION['user']['id'];
    $query = "SELECT id, tenant_name, maintenance_cost, due_date, status, paid_on 
              FROM maintenance 
              WHERE user_id = ? 
              ORDER BY id DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
        $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['status' => 'success', 'maintenance' => $maintenance]);
    } catch (Exception $e) {
        respond(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/*--- Forgot Password Endpoints ---*/

elseif ($action === 'sendOTP') {
    $input = json_decode(file_get_contents("php://input"), true);

    // Check for empty email
    if (empty($input['email'])) {
        respond(['status' => 'error', 'message' => 'Email required']);
    }
    
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        respond(['status' => 'error', 'message' => 'Invalid email address']);
    }
    
    // Generate a unique 4-digit OTP
    $otp = rand(1000, 9999);
    
    // Start session if not active.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['reset_otp']   = $otp;
    $_SESSION['reset_email'] = $email;
    
    $apiKey = getenv('BREVO_API_KEY');
    if (!$apiKey) {
        respond(['status' => 'error', 'message' => 'Brevo API key not set']);
    }
    
    $emailData = [
        "sender" => [
            "name"  => "TOWNMENT ADMIN - Password Reset",
            "email" => "vinothkrish0803@gmail.com"
        ],
        "to" => [
            [
                "email" => $email,
                "name"  => $email
            ]
        ],
        "subject"     => "Your OTP for Password Reset",
        "htmlContent" => "<p>Your OTP for password reset is: <strong>$otp</strong></p>"
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "api-key: $apiKey",
        "content-type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        respond(['status' => 'success', 'message' => 'OTP sent successfully']);
    } else {
        respond([
            'status'       => 'error',
            'message'      => 'OTP sending failed',
            'api_response' => json_decode($response, true)
        ]);
    }
}
elseif ($action === 'verifyOTP') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['otp'])) {
        respond(['status' => 'error', 'message' => 'OTP required']);
    }
    
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_email'])) {
        respond(['status' => 'error', 'message' => 'Session expired, please request a new OTP']);
    }
    
    if (trim($input['otp']) == $_SESSION['reset_otp']) {
        respond(['status' => 'success', 'message' => 'OTP verified successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Invalid OTP']);
    }
}
elseif ($action === 'resetPassword') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['new_password']) || empty($input['confirm_password'])) {
        respond(['status' => 'error', 'message' => 'Both password fields are required']);
    }
    
    if ($input['new_password'] !== $input['confirm_password']) {
        respond(['status' => 'error', 'message' => 'Passwords do not match']);
    }
    
    if (!isset($_SESSION['reset_email'])) {
        respond(['status' => 'error', 'message' => 'Session expired, please request a new OTP']);
    }
    
    $email = $_SESSION['reset_email'];
    $hashed_password = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_otp']);
        
        respond(['status' => 'success', 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        respond(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

else {
    respond(['status' => 'error', 'message' => 'Invalid action']);
}

?>
