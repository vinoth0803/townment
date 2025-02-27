<?php
$pageTitle = "Update Password - TOWNMENT";

?>
<div class="flex items-center justify-center min-h-screen bg-gray-100">
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4 text-center">Update Password</h2>
    <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded mb-4 hidden"></div>
    <form id="updatePasswordForm" class="space-y-4">
      <div>
        <label class="block text-gray-700">New Password</label>
        <input type="password" name="new_password" id="new_password" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-gray-700">Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required class="w-full border p-2 rounded">
      </div>
      <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Update Password</button>
    </form>
  </div>
</div>
<script>
document.getElementById('updatePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const new_password = document.getElementById('new_password').value;
    const confirm_password = document.getElementById('confirm_password').value;
    if (new_password !== confirm_password) {
       document.getElementById('error-message').textContent = "Passwords do not match.";
       document.getElementById('error-message').classList.remove('hidden');
       return;
    }
    try {
       const res = await fetch('api.php?action=updatePasswordAfterOtp', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ new_password, confirm_password })
       });
       const data = await res.json();
       if (data.status === 'success') {
           window.location.href = 'index.php?reset=success';
       } else {
           document.getElementById('error-message').textContent = data.message;
           document.getElementById('error-message').classList.remove('hidden');
       }
    } catch (error) {
       document.getElementById('error-message').textContent = "An error occurred. Please try again.";
       document.getElementById('error-message').classList.remove('hidden');
    }
});
</script>

