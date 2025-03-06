<?php
// tenant_dashboard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
    header("Location: index.php");
    exit();
}

$pageTitle = "Dashboard - TOWNMENT";
include 'tenant_header.php';

// Fetch profile photo
$stmt = $pdo->prepare("SELECT photo_path FROM tenant_photos WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$photoRecord = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_photo = ($photoRecord && !empty($photoRecord['photo_path']))
    ? $photoRecord['photo_path']
    : 'Assets/Default Profile picture.png';
?>


<div class="space-y-6 p-4 sm:p-6 lg:p-8">
  <!-- Row 1: User Profile Card -->
  <div class="bg-white p-6 rounded-3xl shadow flex flex-col md:flex-row items-center">
    <div class="flex-shrink-0">
      <img 
        src="<?php echo htmlspecialchars($profile_photo); ?>" 
        alt="Profile Picture" 
        class="w-32 h-32 rounded-full object-cover"
      >
    </div>
    <div class="mt-4 md:mt-0 md:ml-6">
      <h2 id="profileUsername" class="text-2xl font-bold text-[#B82132]"></h2>
      <p id="profileEmail" class="text-gray-600"></p>
      <p id="profilePhone" class="text-gray-600"></p>
    </div>
  </div>

  <!-- Row 2: Reminders Card (Improved UI using provided SVG) -->
  <div class="bg-white p-6 rounded-xl shadow">
    <h3 class="text-xl font-bold text-[#B82132] mb-4">Reminders</h3>
    <ul class="space-y-3">
      <!-- Reminder Item 1 -->
      <li class="flex items-center gap-3 bg-[#FFF8F0] p-3 rounded-md">
        <!-- Provided Reminder SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-[#B82132]">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
        </svg>
        <span class="text-gray-700">Pay Maintenance Fee for the <?php echo date('F'); ?> Month</span>
      </li>
      <!-- Reminder Item 2 -->
      <li class="flex items-center gap-3 bg-[#FFF8F0] p-3 rounded-md">
        <!-- Provided Reminder SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-[#B82132]">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
        </svg>
        <span class="text-gray-700">Check your Gas and Electricity bill for the <?php echo date('F'); ?> Month</span>
      </li>
    </ul>
  </div>

  <!-- Row 3: Latest Activities -->
  <div class="bg-white p-6 rounded-xl shadow">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Latest Activities</h3>
    <ul id="activityList" class="space-y-4">
      <li class="text-gray-500">Loading latest activities...</li>
    </ul>
  </div>
</div>

<script>
  // Fetch tenant profile details via REST API
  async function loadTenantProfile() {
    try {
      const res = await fetch('api.php?action=getTenantProfile');
      const data = await res.json();
      if (data.status === 'success') {
        const profile = data.profile;
        document.getElementById('profileUsername').innerText =
          profile.tenant_name ? profile.tenant_name : profile.username;
        document.getElementById('profileEmail').innerText = "Email: " + profile.email;
        document.getElementById('profilePhone').innerText = "Phone: " + profile.phone;
      } else {
        console.error("Error loading profile:", data.message);
      }
    } catch (error) {
      console.error("Error fetching tenant profile:", error);
    }
  }

  // Fetch notifications for Latest Activities using getNotifications API
  async function loadNotifications() {
    try {
      const res = await fetch('api.php?action=getNotifications');
      const data = await res.json();
      const activityList = document.getElementById('activityList');

      if (data.status === 'success' && data.notifications.length > 0) {
        activityList.innerHTML = ""; // Clear the loading text
        data.notifications.forEach(notification => {
          const dateObj = new Date(notification.created_at);
          const formattedDate = dateObj.toLocaleDateString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric'
          });
          const formattedTime = dateObj.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', hour12: true
          });

          const li = document.createElement('li');
          li.className = "flex items-start gap-4 p-3 border border-gray-100 rounded-md";

          li.innerHTML = `
            <!-- Provided Latest Activities SVG -->
            <div class="flex-shrink-0 text-[#B82132]">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
              </svg>
            </div>
            <div>
              <p class="text-gray-800 font-medium">${notification.message}</p>
              <p class="text-sm text-gray-500">${formattedDate} at ${formattedTime}</p>
            </div>
          `;
          activityList.appendChild(li);
        });
      } else {
        activityList.innerHTML = "<li class='text-gray-500'>No recent activities.</li>";
      }
    } catch (error) {
      console.error("Error fetching notifications:", error);
      document.getElementById('activityList').innerHTML = "<li class='text-red-500'>Failed to load activities.</li>";
    }
  }

  // Load data on page load
  window.onload = function() {
    loadTenantProfile();
    loadNotifications();
  };
</script>

<?php include 'tenant_footer.php'; ?>
