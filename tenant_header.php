<?php
// tenant_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_name("tenant_session");
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
    header("Location: index.php");
    exit();
}

require_once 'config.php';  
$tenant = $_SESSION['user'];

// Load additional tenant fields (with defaults)  
$tenantFields = $_SESSION['tenant_fields'] ?? [
    'tenant_name' => 'Unknown Tenant',
    'door_number' => '',
    'block'       => '',
    'floor'       => ''
];

// Retrieve tenant profile photo from the database
$stmt = $pdo->prepare("SELECT photo_path FROM tenant_photos WHERE user_id = ?");
$stmt->execute([$tenant['id']]);
$photoRecord = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_photo = ($photoRecord && !empty($photoRecord['photo_path']))
    ? $photoRecord['photo_path']
    : 'Assets/Default Profile picture.png';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Responsive scaling -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tenant Dashboard - TOWNMENT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Top Bar: white background; text/icons: #B82132 */
    .top-navbar {
      background-color: white;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 50;
      padding: 0.3rem 1rem;
      color: #B82132;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    /* Sidebar: background: #B82132; text/icons: white */
    .sidebar {
      position: fixed;
      top: 3.2rem;
      left: 0;
      width: 16rem;
      height: calc(100vh - 3.5rem);
      background-color: #B82132;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
      padding: 1.25rem;
      overflow-y: auto;
     
      transition: transform 0.3s ease-in-out;
    }
    .sidebar a {
      color: #F6DED8;
      border-radius: 16px;
    }
    .sidebar a.active,
    .sidebar a:hover {
      background-color: white !important;
      color: #B82132 !important;
    }
    /* Main Content */
  
    .content {
      margin-left: 17rem;
      margin-top: 4rem;
      padding: 1.25rem;
      transition: margin-left 0.3s ease-in-out;
    }
    @media (max-width: 767px) {
      .content { margin-left: 0; margin-top: 4rem; }
      .sidebar {
        transform: translateX(-100%);
      }
      
      .content {
        margin-left: 0;
        margin-top: 4rem;
      }
      .calendar-card{
        right: -100px;
      }
      .logo img {
      max-height: 50px;
      padding-left: 40px;
    }
    }
    /* Mobile Sidebar: same styling as desktop */
    .mobile-sidebar {
      transition: transform 0.3s ease-in-out;
      background-color: #B82132;
      color: white;
      z-index: -1;
    }
    /* Calendar Card */
    .calendar-card {
      position: absolute;
      right: -50px;
      top: 2.5rem;
      color: white;
      border-radius: 0.5rem;
      padding: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      display: none;
      z-index: 60;
    }
    /* Profile Dropdown */
    .profile-dropdown {
      position: absolute;
      right: 0;
      top: 2.5rem;
      background-color: white;
      color: #B82132;
      border: 1px solid #ddd;
      border-radius: 0.5rem;
      padding: 0.75rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      display: none;
      z-index: 60;
    }
    /* Bell Swing Animation */
    @keyframes swing {
      20% { transform: rotate(15deg); }
      40% { transform: rotate(-10deg); }
      60% { transform: rotate(5deg); }
      80% { transform: rotate(-5deg); }
      100% { transform: rotate(0deg); }
    }
    .rotate{
        transition: transform 0.5s ease;
    }

    .rotate:hover{
        transform: rotate(180deg);
    }
    .swing:hover {
      animation: swing 0.5s ease-in-out forwards;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Top Navbar -->
  <nav class="top-navbar">
    <!-- Left: Logo -->
    <div class="flex items-center logo">
      <img src="Assets/TOWNMENT logo 2.png" alt="TOWNMENT Logo" class="h-10">
    </div>
    <!-- Right: Calendar, Bell, Date/Time, Profile -->
    <div class="flex items-center space-x-4">
      <!-- Google Calendar Icon -->
      <div class="relative">
        <button id="calendarBtn" class="focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 16 16">
            <path d="M5.75 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM5 10.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0ZM10.25 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM7.25 8.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0ZM8 9.5A.75.75 0 1 0 8 11a.75.75 0 0 0 0-1.5Z"/>
            <path fill-rule="evenodd" d="M4.75 1a.75.75 0 0 0-.75.75V3a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2V1.75a.75.75 0 0 0-1.5 0V3h-5V1.75A.75.75 0 0 0 4.75 1ZM3.5 7a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1V7Z" clip-rule="evenodd"/>
          </svg>
        </button>
        <div id="calendarCard" class="calendar-card calendar">
          <iframe src="https://calendar.google.com/calendar/embed?height=300&wkst=1&ctz=Asia%2FKolkata&showPrint=0&showTitle=0&showTz=0&showCalendars=0&src=NmI0M2M2YjAwZjA5M2UxM2ZhMTRhZTNjMzA2MjZmOTY0MDdjNGQ3Y2ExZTIxMjY2MzBkOTkwOWE3NDljYjQ2OUBncm91cC5jYWxlbmRhci5nb29nbGUuY29t&src=ZW4uaW5kaWFuI2hvbGlkYXlAZ3JvdXAudi5jYWxlbmRhci5nb29nbGUuY29t&color=%23B39DDB&color=%230B8043" style="border:none" width="300" height="300" frameborder="0" scrolling="no" ></iframe>
        </div>
      </div>
      <!-- Bell Icon with swing animation -->
      <button onclick="window.location.href='tenant_notification.php'" class="focus:outline-none swing">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="#B82132">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
      </button>
      <!-- Live Date & Time -->
      <div id="dateTime" class="text-[#B82132]"></div>
      <!-- Profile Icon -->
      <div class="relative">
        <button id="profileIcon" class="focus:outline-none">
        <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" class="w-10 h-10 rounded-full object-cover">
        </button>
        <div id="profileDropdown" class="profile-dropdown hidden bg-white border border-gray-300 rounded shadow-md p-4">
          <div class="text-center">
            <img src="Assets/Default Profile picture.png" alt="Profile" class="h-20 w-20 rounded-full mx-auto">
            <a href="tenant_profile.php" class="block text-sm text-blue-500 mt-2">Update Photo</a>
          </div>
          <p class="font-bold mt-2">Tenant Details</p>
          <p class="text-sm"><?php echo htmlspecialchars($tenantFields['tenant_name']); ?></p>
          <p class="text-sm"><?php echo htmlspecialchars($tenant['email']); ?></p>
          <p class="text-sm"><?php echo htmlspecialchars($tenant['phone']); ?></p>
        </div>
    </div>
  </nav>

  <!-- Mobile Sidebar Toggle Button (top-left) -->
  <button id="menuToggle" class="fixed top-4 left-4 text-[#B82132] md:hidden z-50 focus:outline-none">
    <svg id="menuIcon" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="#B82132">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>

  <!-- Sidebar for Desktop -->
  <aside id="sidebar" class="sidebar hidden md:block">
    <h2 class="text-xl font-bold mb-6 text-white">TENANT</h2>
    <ul class="space-y-4">
      <li>
        <a href="tenant_dashboard.php" class="flex items-center text-white px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 rotate">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
</svg>
          <span class="ml-2">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="tenant_profile.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 swing">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>
          <span class="ml-2">Profile</span>
        </a>
      </li>
      <li>
        <a href="tenant_eb_bills.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
</svg>
          <span class="ml-2">EB Bills</span>
        </a>
      </li>
      <li>  
        <a href="tenant_gas_usage.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
</svg>
          <span class="ml-2">Gas Usage</span>
        </a>
      </li>
      <li>
        <a href="tenant_notification.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
</svg>
          <span class="ml-2">Notification</span>
        </a>
      </li>
      <li>
        <a href="tenant_raise_ticket.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-6 swing">
  <path fill-rule="evenodd" d="M1 8.74c0 .983.713 1.825 1.69 1.943.904.108 1.817.19 2.737.243.363.02.688.231.85.556l1.052 2.103a.75.75 0 0 0 1.342 0l1.052-2.103c.162-.325.487-.535.85-.556.92-.053 1.833-.134 2.738-.243.976-.118 1.689-.96 1.689-1.942V4.259c0-.982-.713-1.824-1.69-1.942a44.45 44.45 0 0 0-10.62 0C1.712 2.435 1 3.277 1 4.26v4.482Zm3-3.49a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 5.25ZM4.75 7a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z" clip-rule="evenodd" />
</svg>

          <span class="ml-2">Raise Ticket</span>
        </a>
      </li>
      <li>
        <a href="tenant_maintenance.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 rotate">
  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
</svg>
          <span class="ml-2">Maintenance</span>
        </a>
      </li>
    </ul>
    <!-- Logout at bottom -->
    <div class=" mt-5  ">
      <a id="logoutBtn" href="logout.php?role=tenant" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
</svg>
        <span class="ml-2">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Mobile Sidebar (toggleable) -->
  <aside id="mobileSidebar" class="fixed top-14 left-0 w-64 h-full mobile-sidebar shadow-lg p-5 z-50 transform -translate-x-full transition-transform duration-300 md:hidden">
    <h2 class="text-xl font-bold mb-6">TENANT</h2>
    <ul class="space-y-4">
      <li>
        <a href="tenant_dashboard.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 rotate">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
</svg>
          <span class="ml-2">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="tenant_profile.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 swing">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>
          <span class="ml-2">Profile</span>
        </a>
      </li>
      <li>
        <a href="tenant_eb_bills.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
</svg>
          <span class="ml-2">EB Bills</span>
        </a>
      </li>
      <li>
        <a href="tenant_gas_usage.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
</svg>
          <span class="ml-2">Gas Usage</span>
        </a>
      </li>
      <li>
        <a href="tenant_notification.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
</svg>
          <span class="ml-2">Notification</span>
        </a>
      </li>
      <li>
        <a href="tenant_raise_ticket.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-6 swing">
  <path fill-rule="evenodd" d="M1 8.74c0 .983.713 1.825 1.69 1.943.904.108 1.817.19 2.737.243.363.02.688.231.85.556l1.052 2.103a.75.75 0 0 0 1.342 0l1.052-2.103c.162-.325.487-.535.85-.556.92-.053 1.833-.134 2.738-.243.976-.118 1.689-.96 1.689-1.942V4.259c0-.982-.713-1.824-1.69-1.942a44.45 44.45 0 0 0-10.62 0C1.712 2.435 1 3.277 1 4.26v4.482Zm3-3.49a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 5.25ZM4.75 7a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z" clip-rule="evenodd" />
</svg>
          <span class="ml-2">Raise Ticket</span>
        </a>
      </li>
      <li>
        <a href="tenant_maintenance.php" class="flex items-center px-2 py-1 hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 rotate">
  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
</svg>
          <span class="ml-2">Maintenance</span>
        </a>
      </li>
    </ul>
    <div class="mt-5">
      <a id="mobileLogoutBtn" href="logout.php?role=tenant" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
</svg>
        <span class="ml-2">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="content">
