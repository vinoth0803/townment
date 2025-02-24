<?php
// api.php
require 'config.php';
header("Content-Type: application/json");

function respond($data) {
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? '';
// LOGIN API
if ($action === 'login') {
    $input = json_decode(file_get_contents("php://input"), true);

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        respond(['status' => 'error', 'message' => 'Email and password required']);
    }

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        respond([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $_SESSION['user']
        ]);
    } else {
        respond(['status' => 'error', 'message' => 'Invalid credentials']);
    }
}

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
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $username = $input['username'];
    $email = $input['email'];
    $phone = $input['phone'];
    $password = password_hash($input['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, role) VALUES (?,?,?,?, 'tenant')");
    if ($stmt->execute([$username, $email, $phone, $password])) {
        respond(['status' => 'success', 'message' => 'Tenant added successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to add tenant']);
    }
}
elseif ($action === 'uploadPhoto') {
    // Ensure the logged-in user is a tenant
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    // Check if a file was uploaded without errors
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
            // Update the session value (optional)
            $_SESSION['user']['profile_photo'] = $targetFile;
            respond([
                'status' => 'success',
                'message' => 'Photo updated successfully',
                'photo_url' => $targetFile
            ]);
        } else {
            respond(['status' => 'error', 'message' => 'Failed to update photo in database']);
        }
    } else {
        respond(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
}


//update password

elseif ($action === 'updatePassword') {
    // Allow both tenant and admin to update password, if needed.
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['tenant', 'admin'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['old_password'], $input['new_password'])) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    
    $userId = $_SESSION['user']['id'];
    
    // Retrieve the current hashed password from the database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($input['old_password'], $user['password'])) {
        respond(['status' => 'error', 'message' => 'Old password is incorrect']);
    }
    
    // Hash the new password securely
    $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$newHash, $userId]);
    
    if ($success) {
        respond(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update password']);
    }
}

// In your api.php, add the following case for "getTenantFields":
    elseif ($action === 'getTenantFields') {
        // Only allow access for tenant users
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
            respond(['status' => 'error', 'message' => 'Unauthorized']);
        }
        
        // Get the tenant's user ID from session
        $tenant_id = $_SESSION['user']['id'];
        
        // Prepare a query to fetch tenant fields and profile photo (if exists)
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
    }
    

elseif ($action === 'getTenantProfile') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $userId = $_SESSION['user']['id'];
    // Adjust the query if you have a separate tenant_fields table.
    $stmt = $pdo->prepare("SELECT u.username, u.email, u.phone, tf.tenant_name 
                           FROM users u 
                           LEFT JOIN tenant_fields tf ON u.id = tf.user_id 
                           WHERE u.id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile) {
         respond(['status' => 'success', 'profile' => $profile]);
    } else {
         respond(['status' => 'error', 'message' => 'Profile not found']);
    }
}

/*---------------------------------------------------------
  2. Add Tenant Fields
     Expects JSON body: { "username": "", "door_number": "", "floor": "", "block": "", "tenant_name": "", "configuration": "" }
---------------------------------------------------------*/
elseif ($action === 'addTenantFields') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields, including maintenance_cost
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
    
    $username = $input['username'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'tenant'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respond(['status' => 'error', 'message' => 'Tenant not found']);
    }
    
    $user_id = $user['id'];
    
    // Check if tenant fields record exists
    $stmt = $pdo->prepare("SELECT id FROM tenant_fields WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing record including maintenance_cost
        $stmt = $pdo->prepare("UPDATE tenant_fields 
                               SET door_number = ?, floor = ?, block = ?, tenant_name = ?, configuration = ?, maintenance_cost = ? 
                               WHERE user_id = ?");
        $success = $stmt->execute([
            $input['door_number'],
            $input['floor'],
            $input['block'],
            $input['tenant_name'],
            $input['configuration'],
            $input['maintenance_cost'],
            $user_id
        ]);
    } else {
        // Insert new record including maintenance_cost
        $stmt = $pdo->prepare("INSERT INTO tenant_fields (user_id, door_number, floor, block, tenant_name, configuration, maintenance_cost) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $user_id,
            $input['door_number'],
            $input['floor'],
            $input['block'],
            $input['tenant_name'],
            $input['configuration'],
            $input['maintenance_cost']
        ]);
    }
    
    respond($success ? ['status' => 'success', 'message' => 'Tenant fields updated successfully'] : ['status' => 'error', 'message' => 'Failed to update tenant fields']);
}

/*---------------------------------------------------------
  3. Manage Tenants / Search Tenant
     GET parameter: username (search term)
---------------------------------------------------------*/
elseif ($action === 'searchTenant') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $search = $_GET['username'] ?? '';
    $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE role='tenant' AND username LIKE ?");
    $stmt->execute(['%' . $search . '%']);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'tenants' => $tenants]);
}
elseif ($action === 'deleteTenant') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $username = $_GET['username'] ?? '';
    if (empty($username)) {
        respond(['status' => 'error', 'message' => 'Missing tenant username']);
    }
    // Delete the tenant from the users table.
    // Ensure that foreign keys are configured with ON DELETE CASCADE if needed.
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND role = 'tenant'");
    if ($stmt->execute([$username])) {
        respond(['status' => 'success', 'message' => 'Tenant deleted successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to delete tenant']);
    }
}

/*---------------------------------------------------------
  4. Dashboard: Get Total Tenants
---------------------------------------------------------*/
elseif ($action === 'getTotalTenants') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role='tenant'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'total' => $row['total']]);
}




/*---------------------------------------------------------
  5. Dashboard: Get Latest Tenants
     GET parameters: bhk, period (days)
---------------------------------------------------------*/
elseif ($action === 'getLatestTenants') {
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
/*Eb update------------------------------------------------------- */
elseif ($action === 'getEBDebts') {
    // (Ensure admin authentication here)
    // Join tenant_fields and eb_bill (using a LEFT JOIN so that if no eb_bill record exists, we assume "unpaid")
    $stmt = $pdo->query("
        SELECT tf.tenant_name, tf.block, tf.door_number, tf.floor, eb.paid_on, 
               COALESCE(eb.status, 'unpaid') as status 
        FROM tenant_fields tf 
        LEFT JOIN eb_bill eb ON tf.user_id = eb.user_id
        ORDER BY tf.tenant_name ASC
    ");
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'tenants' => $tenants]);
}
elseif ($action === 'getTenantEBDebt') {
    // Ensure the logged-in user is a tenant
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $tenantId = $_SESSION['user']['id'];
    // Join tenant_fields and eb_bill for the logged-in tenant.
    $stmt = $pdo->prepare("
        SELECT tf.tenant_name, tf.block, tf.door_number, tf.floor, u.phone, 
               COALESCE(eb.status, 'unpaid') as status, eb.paid_on 
        FROM tenant_fields tf 
        JOIN users u ON tf.user_id = u.id 
        LEFT JOIN eb_bill eb ON tf.user_id = eb.user_id 
        WHERE tf.user_id = ?
    ");
    $stmt->execute([$tenantId]);
    $eb = $stmt->fetch(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'eb' => $eb]);
}
/*Marks as paid---------------------------- */
elseif ($action === 'markAsPaid') {
    // Ensure the logged-in user is a tenant
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $tenantId = $_SESSION['user']['id'];

    // Check if an EB bill record already exists for this tenant
    $stmt = $pdo->prepare("SELECT id FROM eb_bill WHERE user_id = ?");
    $stmt->execute([$tenantId]);
    
    if ($stmt->rowCount() > 0) {
        // Record exists, so update it
        $updateStmt = $pdo->prepare("UPDATE eb_bill SET status = 'paid', paid_on = NOW() WHERE user_id = ?");
        $success = $updateStmt->execute([$tenantId]);
    } else {
        // No record exists, so insert a new one
        $insertStmt = $pdo->prepare("INSERT INTO eb_bill (user_id, status, paid_on) VALUES (?, 'paid', NOW())");
        $success = $insertStmt->execute([$tenantId]);
    }

    if ($success) {
        respond(['status' => 'success', 'message' => 'EB bill marked as paid']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update EB bill']);
    }
}



/*---------------------------------------------------------
  7. Add Gas Usage
     Expects JSON body: { "username": "", "usage_date": "YYYY-MM-DD", "usage_amount": number }
---------------------------------------------------------*/
elseif ($action === 'updateGasBill') {
    // Ensure the logged-in user is an admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    // Read the POST JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? null;
    $gasConsumed = $data['gasConsumed'] ?? null;
    $amount = $data['amount'] ?? null;
    
    if (!$username || $gasConsumed === null || $amount === null) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }

    // Fetch user_id from the users table using username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respond(['status' => 'error', 'message' => 'User not found']);
    }
    
    $userId = $user['id'];
    
    // Set due date 10 days ahead of today
    $dueDate = date('Y-m-d', strtotime('+5 days'));
    $today = date('Y-m-d');
    
    // If the current date is greater than the due date, set status as 'Overdue', otherwise 'unpaid'
    $status = ($today > $dueDate) ? 'Overdue' : 'unpaid';
    
    // Check if a gas_usage record exists for the given user_id
    $stmt = $pdo->prepare("SELECT * FROM gas_usage WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        // Record exists; update it
        $stmt = $pdo->prepare("UPDATE gas_usage SET gas_consumed = ?, amount = ?, due_date = ?, status = ?, tenant_username = ? WHERE user_id = ?");
        $success = $stmt->execute([$gasConsumed, $amount, $dueDate, $status, $username, $userId]);
    } else {
        // No record found; insert a new one
        $stmt = $pdo->prepare("INSERT INTO gas_usage (user_id, tenant_username, gas_consumed, amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$userId, $username, $gasConsumed, $amount, $dueDate, $status]);
    }
    
    if ($success) {
        respond(['status' => 'success', 'message' => 'Gas bill updated successfully!']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update gas bill']);
    }
}


elseif ($action === 'getGasUsage') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
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
}



/**
 * Create Gas Order API (for Razorpay integration)
 */
elseif ($action === 'createGasOrder') {
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['id'])) {
        respond(['status'=>'error','message'=>'Missing parameters']);
    }
    $recordId = $data['id'];

    // Verify the record belongs to the logged-in tenant and is unpaid.
    $stmt = $pdo->prepare("SELECT amount FROM gas_usage WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $_SESSION['user']['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$record) {
         respond(['status'=>'error','message'=>'No unpaid gas usage record found for this record']);
    }
    $amount = $record['amount']; // in rupees
    $amount_paise = $amount * 100; // convert to paise
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
       respond(['status'=>'error', 'message'=>'cURL Error: ' . $err]);
    } else {
       $responseData = json_decode($response, true);
       if(isset($responseData['id'])){
           respond(['status'=>'success', 'order_id'=>$responseData['id'], 'message'=>'Order created successfully']);
       } else {
           respond(['status'=>'error', 'message'=>'Order creation failed: ' . $response]);
       }
    }
}

/**
 * Capture Gas Payment API
 */
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
    $stmt = $pdo->prepare("SELECT amount, status FROM gas_usage WHERE id = ? AND user_id = ? AND status <> 'paid'");
    $stmt->execute([$recordId, $userId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
       respond(['status'=>'error','message'=>'No unpaid gas usage record found for this record']);
    }
    
    $amount = $record['amount'];
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
    
    // Check if Razorpay confirms a successful capture.
    if (isset($responseData['status']) && $responseData['status'] === 'captured') {
        $updateSuccess = true;
    }
    // If Razorpay returns an error stating the payment "already been captured", treat that as success.
    else if (isset($responseData['error']) && stripos($responseData['error']['description'], "already been captured") !== false) {
        $updateSuccess = true;
    }
    else {
       respond(['status'=>'error', 'message'=>'Payment capture failed: ' . $response]);
    }
    
    if ($updateSuccess) {
       // Insert the payment record into the "payments" table.
       $stmtInsert = $pdo->prepare("INSERT INTO payments (user_id, payment_id) VALUES (?, ?)");
       $stmtInsert->execute([$userId, $paymentId]);
       
       // Update the gas_usage record to mark it as paid.
       $stmtUpdate = $pdo->prepare("UPDATE gas_usage SET status = 'paid', paid_on = NOW() WHERE id = :id AND user_id = :uid AND status <> 'paid'");
       $stmtUpdate->execute([':id' => $recordId, ':uid' => $userId]);
       
       if ($stmtUpdate->rowCount() > 0) {
           respond(['status'=>'success', 'message'=>'Gas bill paid successfully']);
       } else {
           // Fallback: re-check the record status.
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


/**
 * Fetch Payment Details API
 */
elseif ($action === 'fetchPaymentDetails') {
    if (!isset($_SESSION['user'])) {
       respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['razorpay_payment_id'])) {
       respond(['status'=>'error','message'=>'Missing razorpay_payment_id parameter']);
    }
    $paymentId = $data['razorpay_payment_id'];
    
    // Check that the payment exists in the "payments" table.
    $stmtPayment = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmtPayment->execute([$paymentId]);
    $paymentRecord = $stmtPayment->fetch(PDO::FETCH_ASSOC);
    if (!$paymentRecord) {
       respond(['status' => 'error', 'message' => 'Payment record not found in database']);
    }
    
    // Fetch payment details from Razorpay.
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
    
    // If Razorpay confirms the payment is captured, update the latest unpaid gas_usage record for this user.
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
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'notifications' => $notifications]);
}

/*---------------------------------------------------------
  9. Notification: Send Notification
     Expects JSON body: { "message": "" }
---------------------------------------------------------*/
elseif ($action === 'sendNotification') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['message'])) {
        respond(['status' => 'error', 'message' => 'Message required']);
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    // For demonstration, insert two notifications (simulate sent and received)
    $stmt = $pdo->prepare("INSERT INTO notifications (message, notification_type) VALUES (?,?)");
    $stmt->execute([$input['message'], 'sent']);
    $stmt->execute([$input['message'], 'received']);
    respond(['status' => 'success', 'message' => 'Notification sent successfully']);
}

/*---------------------------------------------------------
 10. Tickets: Get All Raised Tickets
---------------------------------------------------------*/
elseif ($action === 'getallTickets') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $stmt = $pdo->query("SELECT t.id, u.username, t.raised_date, t.issue, t.status
                         FROM tickets t 
                         JOIN users u ON t.user_id = u.id 
                         ORDER BY t.raised_date DESC");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status' => 'success', 'tickets' => $tickets]);
}

/*---------------------------------------------------------
 11. Tickets: Update Ticket Status
     Expects JSON body: { "ticket_id": number, "status": "opened"|"inprogress"|"closed" }
---------------------------------------------------------*/
elseif ($action === 'updateTicketStatus') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['ticket_id']) || empty($input['status'])) {
        respond(['status' => 'error', 'message' => 'Ticket ID and status required']);
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $stmt = $pdo->prepare("UPDATE tickets SET status=? WHERE id=?");
    if ($stmt->execute([$input['status'], $input['ticket_id']])) {
        respond(['status' => 'success', 'message' => 'Ticket status updated']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update ticket status']);
    }
}
elseif ($action === 'raiseTicket') {
    // Ensure the user is logged in (tenant or admin may raise a ticket)
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    // Read the POST JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate that the "issue" field is provided
    if (empty($data['issue'])) {
        respond(['status' => 'error', 'message' => 'Issue description is required']);
    }
    
    // Get the user ID from session data
    $user_id = $_SESSION['user']['id'];
    $issue = trim($data['issue']);
    
    // Insert the new ticket into the tickets table with default status "opened"
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, issue, status) VALUES (?, ?, 'opened')");
    if ($stmt->execute([$user_id, $issue])) {
        respond(['status' => 'success', 'message' => 'Ticket raised successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to raise ticket']);
    }
}
elseif ($action === 'getNewTickets') {
    // Check that the current user is an admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    try {
        // Fetch tickets raised in the last 24 hours.
        // Adjust the query and column names if necessary.
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
    } catch (Exception $e) {
        respond(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'getTickets') {
    // Ensure the user is logged in
    if (!isset($_SESSION['user'])) {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $user = $_SESSION['user'];
    $params = [];
    $query = "SELECT raised_date, issue, status FROM tickets ";
    
    // If the user is a tenant, restrict to only their tickets.
    if ($user['role'] === 'tenant') {
        $query .= "WHERE user_id = ? ";
        $params[] = $user['id'];
    }
    
    // If a status filter is provided (e.g., opened, inprogress, closed)
    if (!empty($_GET['status'])) {
        // Add WHERE or AND depending on previous clause
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "status = ? ";
        $params[] = $_GET['status'];
    }
    
    // If a period filter is provided (in days)
    if (!empty($_GET['period'])) {
        $period = (int) $_GET['period'];
        $thresholdDate = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $query .= (strpos($query, 'WHERE') === false ? "WHERE " : "AND ");
        $query .= "raised_date >= ? ";
        $params[] = $thresholdDate;
    }
    
    $query .= "ORDER BY raised_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond(['status' => 'success', 'tickets' => $tickets]);
}

/*---------------------------------------------------------
 12. Maintenance: Get Maintenance Records (Dummy Data)
---------------------------------------------------------*/
elseif ($action === 'getMaintenance') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }

    // Base query to fetch maintenance records
    $query = "SELECT tenant_name, block, door_number, phone, paid_on, status, maintenance_cost, floor 
              FROM maintenance";
    $conditions = [];
    $params = [];

    // Filter: Search by tenant name
    if (!empty($_GET['search'])) {
        $conditions[] = "tenant_name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
    }
    
    // Filter: Date Range (based on paid_on)
    if (!empty($_GET['daterange'])) {
        $days = (int)$_GET['daterange'];
        // This will return records with paid_on date in the last X days.
        $conditions[] = "paid_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    // Filter: Block
    if (!empty($_GET['block'])) {
        $conditions[] = "block = ?";
        $params[] = $_GET['block'];
    }
    
    // Filter: Floor
    if (!empty($_GET['floor'])) {
        $conditions[] = "floor = ?";
        $params[] = $_GET['floor'];
    }

    // Append conditions if any
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

elseif ($action === 'addMaintenance') {
    // Ensure the request is from an authorized admin (or modify as needed)
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    // Decode JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (
        empty($data['tenant_name']) ||
        empty($data['block']) ||
        empty($data['door_number']) ||
        empty($data['floor']) ||
        empty($data['phone']) ||
        !isset($data['maintenance_cost']) ||
        empty($data['user_id'])  // Ensure user_id is provided
    ) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }

    // Sanitize inputs
    $tenant_name = trim($data['tenant_name']);
    $block = trim($data['block']);
    $door_number = trim($data['door_number']);
    $floor = trim($data['floor']);
    $phone = trim($data['phone']);
    $maintenance_cost = (float)$data['maintenance_cost'];
    $user_id = (int)$data['user_id'];  // Convert to integer

    // Check if the user exists in the users table
    $userCheckStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $userCheckStmt->execute([$user_id]);

    if ($userCheckStmt->rowCount() === 0) {
        respond(['status' => 'error', 'message' => 'User ID does not exist']);
    }

    // Insert a new maintenance record
    $stmt = $pdo->prepare("INSERT INTO maintenance (user_id, tenant_name, block, door_number, floor, phone, maintenance_cost, paid_on, status) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 'unpaid')");

    if ($stmt->execute([$user_id, $tenant_name, $block, $door_number, $floor, $phone, $maintenance_cost])) {
        respond(['status' => 'success', 'message' => 'Maintenance record added successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to add maintenance record']);
    }
}



else {
    respond(['status' => 'error', 'message' => 'Invalid action']);
}
?>
