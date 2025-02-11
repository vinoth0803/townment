<?php
$pageTitle = "Notification - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-2xl font-bold mb-4">Notification</h1>
  <div class="grid grid-cols-2 gap-4">
    <!-- Left Column: List Notifications -->
    <div>
      <h2 class="text-xl mb-2">Notifications</h2>
      <div id="notificationList" class="space-y-2"></div>
    </div>
    <!-- Right Column: Send Notification Form -->
    <div>
      <h2 class="text-xl mb-2">Send Notification to All Residents</h2>
      <form id="sendNotificationForm" class="space-y-4">
        <div>
          <label>Message:</label>
          <textarea name="message" required class="border p-2 rounded w-full"></textarea>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Send</button>
      </form>
      <div id="sendNotificationMessage" class="mt-4"></div>
    </div>
  </div>
</div>
<script>
  async function loadNotifications(){
    const res = await fetch('api.php?action=getNotifications');
    const data = await res.json();
    let html = '';
    if(data.notifications && data.notifications.length > 0){
      data.notifications.forEach(n => {
        let colorClass = (n.notification_type === 'sent') ? 'text-green-600' : 'text-yellow-600';
        html += `<div class="p-2 rounded ${n.notification_type==='sent'?'bg-green-100':'bg-yellow-100'}">
                    <span class="${colorClass} font-bold">${n.notification_type.toUpperCase()}</span>: ${n.message} <small>(${n.created_at})</small>
                 </div>`;
      });
    } else {
      html = `<p>No notifications found.</p>`;
    }
    document.getElementById('notificationList').innerHTML = html;
  }
  document.getElementById('sendNotificationForm').addEventListener('submit', async function(e){
    e.preventDefault();
    let formData = new FormData(this);
    let data = {};
    formData.forEach((value, key) => data[key] = value);
    const res = await fetch('api.php?action=sendNotification', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    document.getElementById('sendNotificationMessage').innerText = result.message;
    loadNotifications();
  });
  window.onload = loadNotifications;
</script>
<?php include 'admin_footer.php'; ?>
