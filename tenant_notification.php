<?php
$pageTitle = "Tenant Notifications - TOWNMENT";
include 'tenant_header.php';
?>
<div class="flex-1 p-6 bg-gray-50 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">Notifications</h1>
  <div id="notificationList" class="space-y-4">
    <!-- Notification cards will load here -->
  </div>
</div>

<script>
  async function loadNotifications(){
    try {
      const res = await fetch('api.php?action=getNotifications');
      const data = await res.json();
      let notifications = data.notifications;
      let html = "";
      
      if(notifications && notifications.length > 0){
        notifications.forEach(n => {
          html += `
          <div class="flex items-start p-4 bg-white shadow rounded-lg">
            <div class="flex-shrink-0 text-blue-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-gray-700">${n.message}</p>
              <small class="text-gray-500">${n.created_at}</small>
            </div>
          </div>`;
        });
      } else {
        html = `<p class="text-gray-600">No notifications available.</p>`;
      }
      
      document.getElementById('notificationList').innerHTML = html;
    } catch (error) {
      console.error('Error fetching notifications:', error);
      document.getElementById('notificationList').innerHTML = `<p class="text-red-500">Failed to load notifications.</p>`;
    }
  }
  
  window.onload = loadNotifications;
</script>

<?php include 'tenant_footer.php'; ?>
