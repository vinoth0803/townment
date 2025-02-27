<?php
$pageTitle = "OTP Verification - TOWNMENT";

?>
<div class="flex items-center justify-center min-h-screen bg-gray-100">
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4 text-center">OTP Verification</h2>
    <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded mb-4 hidden"></div>
    <div id="success-message" class="bg-green-200 text-green-800 p-2 rounded mb-4 hidden"></div>
    <form id="otpForm" class="space-y-4">
      <div>
        <label class="block text-gray-700">Enter OTP</label>
        <input type="text" name="otp" id="otp" maxlength="4" required class="w-full border p-2 rounded">
      </div>
      <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Verify OTP</button>
    </form>
    <div class="text-center mt-4">
      <a href="#" id="resendLink" class="text-blue-500 underline">Didn't receive the code? Click here to resend</a>
    </div>
  </div>
</div>
<script>
document.getElementById('otpForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const otp = document.getElementById('otp').value.trim();
    try {
        const res = await fetch('api.php?action=verifyOtp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ otp })
        });
        const data = await res.json();
        if (data.status === 'success') {
            window.location.href = 'update_password.php';
        } else {
            document.getElementById('error-message').textContent = data.message;
            document.getElementById('error-message').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('error-message').textContent = "An error occurred. Please try again.";
        document.getElementById('error-message').classList.remove('hidden');
    }
});

document.getElementById('resendLink').addEventListener('click', async function(e) {
    e.preventDefault();
    try {
        const res = await fetch('api.php?action=resendOtp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('success-message').textContent = data.message;
            document.getElementById('success-message').classList.remove('hidden');
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

