<?php
$pageTitle = "Forgot Password - TOWNMENT";

?>
<div class="flex items-center justify-center min-h-screen bg-gray-100">
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4 text-center">Forgot Password</h2>
    <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded mb-4 hidden"></div>
    <form id="forgotPasswordForm" class="space-y-4">
      <div>
        <label class="block text-gray-700">Email</label>
        <input type="email" name="email" id="email" required class="w-full border p-2 rounded">
      </div>
      <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Send OTP</button>
    </form>
  </div>
</div>
<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    if (!email) {
       document.getElementById('error-message').textContent = "Please enter an email address.";
       document.getElementById('error-message').classList.remove('hidden');
       return;
    }
    try {
       const res = await fetch('api.php?action=forgotPassword', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ email })
       });
       const data = await res.json();
       if (data.status === 'success') {
           window.location.href = 'otp_verification.php';
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

