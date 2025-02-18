<?php
// admin_header.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header('Location: index.php');
  exit;
}
$admin = $_SESSION['user']; // Must contain: username, email, phone
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Responsive scaling -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?? "ADMIN Dashboard - TOWNMENT"; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Keyframes for bell swing animation */
    @keyframes swing {
      20% { transform: rotate(15deg); }
      40% { transform: rotate(-10deg); }
      60% { transform: rotate(5deg); }
      80% { transform: rotate(-5deg); }
      100% { transform: rotate(0deg); }
    }
    /* Top Bar: white background; text and icons: #d2665a */
    .top-navbar {
      background-color:rgb(255, 255, 255);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 50;
      padding: 0.3rem 1rem;
      color: #B82132;
    }
    /* Logo centered; remove any hover zoom/fade */
    .logo img {
      max-height: 50px;
      padding-left: 0;
    }
    /* Sidebar: background: #d2665a; text & icons: white */
    .sidebar {
      position: fixed;
      top: 3.5rem;
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
      color: white;
      border-radius: 5px;
    }
    /* Active sidebar link: white background with #d2665a text and icons */
    .sidebar a.active,
    .sidebar a:hover {
      background-color: white !important;
      border-radius: 5px;
      color: #B82132 !important;
    }
 
    /* Main Content */
    .content {
      margin-left: 17rem;
      margin-top: 4rem;
      padding: 1.25rem;
      transition: margin-left 0.3s ease-in-out;
    }
    /* Mobile adjustments: hide desktop sidebar */
    @media (max-width: 767px) {
      .sidebar { transform: translateX(-100%); }
      .content { margin-left: 0; margin-top: 4rem; }
      .logo img {
      max-height: 50px;
      padding-left: 100px;
    }
    }
    /* Mobile sidebar smooth transition */
    .mobile-sidebar {
      transition: transform 0.3s ease-in-out;
      background-color: #B82132 !important;
      color: white;
    }
    /* Bell icon swing animation on hover */
    .swing:hover {
      animation: swing 0.5s ease-in-out forwards;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Fixed Top Navbar -->
  <nav class="top-navbar flex items-center justify-between">
    <!-- Centered Logo -->
    <div class="logo flex justify-center">
    <img src="Assets/TOWNMENT logo 2.png" alt="TOWNMENT Logo" class="h-12 sm:h-14 md:h-16 lg:h-20 xl:h-24 px-4">
</div>

    <!-- Right: Icons -->
    <div class="flex items-center space-x-4">
      <!-- Notifications Button with swing animation on hover -->
      <button onclick="window.location.href='notification.php'" class="focus:outline-none swing">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
</svg>

      </button>
      <!-- Live Date & Time -->
      <div id="dateTime" class="text-[#B82132]"></div>
      <!-- Profile Icon & Dropdown -->
      <div class="relative">
        <button id="profileIcon" class="focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-7">
  <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
</svg>

        </button>
        <div id="profileDropdown" class="absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-md p-3 hidden">
          <p class="font-bold text-[#B82132]">Admin Details</p>
          <p id="adminUsername" class="text-sm text-[#B82132]"></p>
          <p id="adminEmail" class="text-sm text-[#B82132]"></p>
          <p id="adminPhone" class="text-sm text-[#B82132]"></p>
        </div>
      </div>
    </div>
  </nav>

  <!-- Mobile Sidebar Toggle Button (positioned top-left) -->
  <button id="menuToggle" class="fixed top-4 left-4 text-[#B82132] md:hidden z-50 focus:outline-none">
    <!-- Initially, display hamburger icon -->
    <svg id="menuIcon" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none"
         viewBox="0 0 24 24" stroke="#B82132">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>

  <!-- Fixed Sidebar for Desktop -->
  <aside id="sidebar" class="sidebar hidden md:block">
    <h2 class="text-xl font-bold mb-6 text-[#F6DED8]">ADMIN</h2>
    <ul class="space-y-4">
      <!-- Dashboard -->
      <li>
        <a href="admin.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
</svg>

          <span class="ml-2">Dashboard</span>
        </a>
      </li>
      
      <!-- Tenant Dropdown (toggle on click) -->
      <!-- Tenant Dropdown (toggle on click) -->
<li class="relative">
  <a href="#" id="tenantDropdownToggle" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 swing">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>
    <span class="ml-2">Tenant</span>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 ml-auto swing">
      <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
    </svg>
  </a>
  <!-- Removed absolute positioning so the dropdown pushes the next link down.
       If you prefer overlay, you can add back "absolute left-0 top-full mt-2" and a high z-index -->
  <ul id="tenantDropdownMenu" class="mt-2 w-full bg-[#B82132 ] border rounded-lg shadow-lg hidden transition-all duration-300">
    <li>
      <a href="admin_add_tenant.php" class="flex items-center px-4 py-2 text-[#d2665a] hover:bg-[#B82132] hover:text-white transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-3 swing">
  <path d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
</svg>

        Add a New Tenant
      </a>
    </li>
    <li>
      <a href="admin_add_tenant_fields.php" class="flex items-center px-4 py-2 text-[#d2665a] hover:bg-[#B82132] hover:text-white transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-3 swing">
  <path fill-rule="evenodd" d="M4.5 3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V6.75a3 3 0 0 0-3-3h-15Zm4.125 3a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm-3.873 8.703a4.126 4.126 0 0 1 7.746 0 .75.75 0 0 1-.351.92 7.47 7.47 0 0 1-3.522.877 7.47 7.47 0 0 1-3.522-.877.75.75 0 0 1-.351-.92ZM15 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15ZM14.25 12a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H15a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15Z" clip-rule="evenodd" />
</svg>

        Add Tenant Fields
      </a>
    </li>
  </ul>
</li>

      
      <!-- Manage Tenants -->
      <li>
        <a href="admin_manage_tenants.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
</svg>

          <span class="ml-2">Manage Tenants</span>
        </a>
      </li>
      
      <!-- Update EB Details -->
      <li>
        <a href="admin_eb_bills.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
</svg>

          <span class="ml-2">EB Details</span>
        </a>
      </li>
      
      <!-- Add Gas Usage -->
      <li>
        <a href="admin_gas_usage.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
</svg>

          <span class="ml-2">Add Gas Usage</span>
        </a>
      </li>
      
      <!-- Notification -->
      <li>
        <a href="notification.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
</svg>

          <span class="ml-2">Notification</span>
        </a>
      </li>
      
      <!-- Tickets -->
      <li>
        <a href="tickets.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="m7.875 14.25 1.214 1.942a2.25 2.25 0 0 0 1.908 1.058h2.006c.776 0 1.497-.4 1.908-1.058l1.214-1.942M2.41 9h4.636a2.25 2.25 0 0 1 1.872 1.002l.164.246a2.25 2.25 0 0 0 1.872 1.002h2.092a2.25 2.25 0 0 0 1.872-1.002l.164-.246A2.25 2.25 0 0 1 16.954 9h4.636M2.41 9a2.25 2.25 0 0 0-.16.832V12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 12V9.832c0-.287-.055-.57-.16-.832M2.41 9a2.25 2.25 0 0 1 .382-.632l3.285-3.832a2.25 2.25 0 0 1 1.708-.786h8.43c.657 0 1.281.287 1.709.786l3.284 3.832c.163.19.291.404.382.632M4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-2.625c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125V18a2.25 2.25 0 0 0 2.25 2.25Z" />
</svg>

          <span class="ml-2">Tickets</span>
        </a>
      </li>
      
      <!-- Maintenance -->
      <li>
        <a href="maintenance.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
</svg>

          <span class="ml-2">Maintenance</span>
        </a>
      </li>
    </ul>
    <!-- Fixed Logout Button -->
    <div class="mt-6">
      <a id="logoutBtn" href="logout.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#d2665a] transition-colors duration-300 ">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 swing">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
</svg>

        <span class="ml-2">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Mobile Sidebar (toggleable) -->
  <aside id="mobileSidebar" class="fixed top-0 left-0 w-64 h-full bg-[#B82132] shadow-lg p-5 z-40 hidden md:hidden transition-transform duration-300">
    <h2 class="text-xl font-bold mb-6">ADMIN</h2>
    <ul class="space-y-4">
      <li>
        <a href="admin.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
</svg>

          <span class="ml-2">Dashboard</span>
        </a>
      </li>
      <!-- Tenant Dropdown in Mobile Sidebar -->
      <li class="relative group">
        <a href="#" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300" id="mobileTenantDropdownToggle">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>
          <span class="ml-2">Tenant</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto" fill="none" 
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M19 9l-7 7-7-7" />
          </svg>
        </a>
        <ul id="mobileTenantDropdownMenu" class="absolute left-0 top-full mt-2 w-full bg-white border rounded-lg shadow-lg hidden transition-all duration-300">
          <li>
          <a href="admin_add_tenant.php" class="flex items-center px-4 py-2 text-[#B82132] hover:bg-[#B82132] hover:text-white transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-3">
  <path d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
</svg>

        Add a New Tenant
      </a>

          </li>
          <li>
          <a href="admin_add_tenant_fields.php" class="flex items-center px-4 py-2 text-[#B82132] hover:bg-[#B82132] hover:text-white transition-colors duration-300">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-3">
  <path fill-rule="evenodd" d="M4.5 3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V6.75a3 3 0 0 0-3-3h-15Zm4.125 3a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm-3.873 8.703a4.126 4.126 0 0 1 7.746 0 .75.75 0 0 1-.351.92 7.47 7.47 0 0 1-3.522.877 7.47 7.47 0 0 1-3.522-.877.75.75 0 0 1-.351-.92ZM15 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15ZM14.25 12a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H15a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15Z" clip-rule="evenodd" />
</svg>

        Add Tenant Fields
      </a>

          </li>
        </ul>
      </li>
      <li>
        <a href="admin_manage_tenants.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
</svg>

          <span class="ml-2">Manage Tenants</span>
        </a>
      </li>

      <li>
        <a href="admin_eb_bills.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
</svg>

          <span class="ml-2">EB Details</span>
        </a>
      </li>

      <!-- Add Gas Usage -->
      <li>
        <a href="admin_gas_usage.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
</svg>

          <span class="ml-2">Add Gas Usage</span>
        </a>
      </li>
<!-- Notification -->
      <li>
        <a href="notification.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
</svg>

          <span class="ml-2">Notification</span>
        </a>
      </li>
      
       <!-- Tickets -->
       <li>
        <a href="tickets.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m7.875 14.25 1.214 1.942a2.25 2.25 0 0 0 1.908 1.058h2.006c.776 0 1.497-.4 1.908-1.058l1.214-1.942M2.41 9h4.636a2.25 2.25 0 0 1 1.872 1.002l.164.246a2.25 2.25 0 0 0 1.872 1.002h2.092a2.25 2.25 0 0 0 1.872-1.002l.164-.246A2.25 2.25 0 0 1 16.954 9h4.636M2.41 9a2.25 2.25 0 0 0-.16.832V12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 12V9.832c0-.287-.055-.57-.16-.832M2.41 9a2.25 2.25 0 0 1 .382-.632l3.285-3.832a2.25 2.25 0 0 1 1.708-.786h8.43c.657 0 1.281.287 1.709.786l3.284 3.832c.163.19.291.404.382.632M4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-2.625c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125V18a2.25 2.25 0 0 0 2.25 2.25Z" />
</svg>

          <span class="ml-2">Tickets</span>
        </a>
      </li>
      <!-- Maintenance -->
      <li>
        <a href="maintenance.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
</svg>

          <span class="ml-2">Maintenance</span>
        </a>
      </li>
    </ul>
    <div class="mt-6">
      <a id="logoutBtn" href="logout.php" class="flex items-center px-2 py-1 text-white hover:bg-white hover:text-[#B82132] transition-colors duration-300 ">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
</svg>

        <span class="ml-2">Logout</span>
      </a>
    </div>

  </aside>

  <!-- Main Content Area -->
  <main class="content">
