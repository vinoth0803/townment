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
          <div class="flex flex-col p-4 bg-blue-50 border border-blue-200 rounded-lg shadow-sm">
            <div class="flex items-center">
              <div class="text-blue-500 mr-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="text-blue-800 font-bold">${n.subject}</p>
                <p class="text-blue-700">${n.message}</p>
              </div>
            </div>
            <small class="text-gray-500 mt-2">${n.created_at}</small>
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
