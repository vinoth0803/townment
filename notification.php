<?php
$pageTitle = "Notification - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Notifications</h1>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Left Column: List Notifications -->
    <div>
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Notification List</h2>
      <div id="notificationList" class="space-y-4">
        <!-- Notifications will load here -->
      </div>
    </div>
    <!-- Right Column: Send Notification Form -->
    <div>
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Send Notification to All Residents</h2>
      <form id="sendNotificationForm" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        <!-- Subject Input Field -->
        <div>
          <label for="subject" class="block text-lg font-medium text-gray-700">Subject</label>
          <input type="text" id="subject" name="subject" required 
                 class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="message" class="block text-lg font-medium text-gray-700">Message</label>
          <textarea id="message" name="message" required rows="4" 
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>
        <button type="submit" 
                class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition">
          Send Notification
        </button>
      </form>
      <div id="sendNotificationMessage" class="mt-4 text-center text-lg text-green-600"></div>
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
        html += `<div class="p-4 bg-white shadow rounded-lg">
                    <p class="text-gray-800 font-bold">${n.subject}</p>
                    <p class="text-gray-800">${n.message}</p>
                    <small class="text-gray-500">${n.created_at}</small>
                 </div>`;
      });
    } else {
      html = `<p class="text-gray-600">No notifications found.</p>`;
    }
    document.getElementById('notificationList').innerHTML = html;
  }

  document.getElementById('sendNotificationForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const data = {};
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
