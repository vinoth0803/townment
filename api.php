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

elseif ($action === 'updateGasBill') {
    // Ensure the admin is logged in
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? null;
    $gasConsumed = $data['gasConsumed'] ?? null;
    $amount = $data['amount'] ?? null;
    
    if (!$username || $gasConsumed === null || $amount === null) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    
    // Set due date 10 days ahead of today
    $dueDate = date('Y-m-d', strtotime('+10 days'));
    
    // Use PDO to check if a gas_usage record exists for the given tenant_username
    $stmt = $pdo->prepare("SELECT * FROM gas_usage WHERE tenant_username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        // Record exists; update it
        $stmt = $pdo->prepare("UPDATE gas_usage SET gas_consumed = ?, amount = ?, due_date = ?, status = 'unpaid' WHERE tenant_username = ?");
        $success = $stmt->execute([$gasConsumed, $amount, $dueDate, $username]);
    } else {
        // No record found; insert a new one
        $stmt = $pdo->prepare("INSERT INTO gas_usage (tenant_username, gas_consumed, amount, due_date, status) VALUES (?,?,?,?, 'unpaid')");
        $success = $stmt->execute([$username, $gasConsumed, $amount, $dueDate]);
    }
    
    if ($success) {
        respond(['status' => 'success', 'message' => 'Gas bill updated successfully!']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update gas bill']);
    }
}

elseif ($action === 'getTenants') {
    // Build a query to fetch tenant details
    // Here we assume:
    // - The main tenant info is in "users" where role='tenant'
    // - Extra fields (tenant name, block, door number, etc.) are in "tenant_fields"
    // - Gas usage info is in "gas_usage" with a linking column tenant_username matching users.username
    $query = "SELECT 
                u.username, 
                tf.tenant_name, 
                tf.block, 
                tf.door_number AS door_no, 
                u.phone, 
                g.gas_consumed, 
                g.amount, 
                g.due_date, 
                g.status 
              FROM users u
              LEFT JOIN tenant_fields tf ON u.id = tf.user_id
              LEFT JOIN gas_usage g ON u.username = g.tenant_username
              WHERE u.role = 'tenant'";
              
    // Use PDO for the query
    $stmt = $pdo->query($query);
    $tenants = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tenants[] = $row;
    }
    
    echo json_encode($tenants);
    exit;
}

/*---------------------------------------------------------
  1. Add a New Tenant
     Expects JSON body: { "username": "", "email": "", "phone": "", "password": "" }
---------------------------------------------------------*/
if ($action === 'addTenant') {
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
elseif ($action === 'getEBDebts') {
    // Using PDO (assuming your connection is in $pdo)
    $query = "SELECT u.username, tf.tenant_name, tf.block, tf.door_number AS door_no, u.phone, 
                     COALESCE(eb.status, 'unpaid') as status
              FROM users u
              LEFT JOIN tenant_fields tf ON u.id = tf.user_id
              LEFT JOIN eb_details eb ON u.id = eb.user_id
              WHERE u.role = 'tenant'";
    
    $stmt = $pdo->query($query);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond(['status' => 'success', 'tenants' => $tenants]);
}

elseif ($action === 'updateEBStatus') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $username = trim($data['username'] ?? '');
    $status = strtolower(trim($data['status'] ?? ''));

    // Validate input
    if (!$username || !in_array($status, ['paid', 'unpaid'])) {
        respond(['status' => 'error', 'message' => 'Invalid parameters']);
    }

    // Get user_id from username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'tenant'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respond(['status' => 'error', 'message' => 'Tenant not found']);
    }

    $user_id = $user['id'];

    // Insert or update EB status using efficient query
    $stmt = $pdo->prepare("
        INSERT INTO eb_details (user_id, status) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");
    
    $success = $stmt->execute([$user_id, $status]);

    if ($success) {
        respond(['status' => 'success', 'message' => 'EB status updated successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to update EB status']);
    }
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
elseif ($action === 'updateEBDetails') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['username']) || empty($input['detail'])) {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $username = $input['username'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND role='tenant'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        respond(['status' => 'error', 'message' => 'Tenant not found']);
    }
    $user_id = $user['id'];
    $stmt = $pdo->prepare("SELECT id FROM eb_details WHERE user_id=?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE eb_details SET detail=? WHERE user_id=?");
        $success = $stmt->execute([$input['detail'], $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO eb_details (user_id, detail) VALUES (?,?)");
        $success = $stmt->execute([$user_id, $input['detail']]);
    }
    respond($success ? ['status' => 'success', 'message' => 'EB details updated successfully'] : ['status' => 'error', 'message' => 'Failed to update EB details']);
}

/*---------------------------------------------------------
  7. Add Gas Usage
     Expects JSON body: { "username": "", "usage_date": "YYYY-MM-DD", "usage_amount": number }
---------------------------------------------------------*/
elseif ($action === 'addGasUsage') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['username']) || empty($input['usage_date']) || $input['usage_amount'] === '') {
        respond(['status' => 'error', 'message' => 'Missing parameters']);
    }
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        respond(['status' => 'error', 'message' => 'Unauthorized']);
    }
    $username = $input['username'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND role='tenant'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        respond(['status' => 'error', 'message' => 'Tenant not found']);
    }
    $user_id = $user['id'];
    $stmt = $pdo->prepare("INSERT INTO gas_usage (user_id, usage_date, usage_amount) VALUES (?,?,?)");
    if ($stmt->execute([$user_id, $input['usage_date'], $input['usage_amount']])) {
        respond(['status' => 'success', 'message' => 'Gas usage added successfully']);
    } else {
        respond(['status' => 'error', 'message' => 'Failed to add gas usage']);
    }
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
elseif ($action === 'getTickets') {
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
