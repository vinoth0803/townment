<?php
// verify_otp.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-green-400 to-blue-600 flex items-center justify-center min-h-screen">
  <div class="bg-white p-10 rounded-lg shadow-lg w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-6">Verify OTP</h1>
    <form id="verifyOTPForm">
      <div class="mb-4">
        <label for="otp" class="block text-gray-700 font-semibold mb-2">Enter OTP</label>
        <input 
          type="text" 
          name="otp" 
          id="otp" 
          placeholder="Enter the OTP" 
          required 
          class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:border-green-500">
      </div>
      <button 
        type="submit" 
        class="w-full bg-green-500 text-white p-3 rounded hover:bg-green-600 transition duration-200">
        Verify OTP
      </button>
    </form>
    <div id="errorMsg" class="mt-4 text-red-500 text-center"></div>
  </div>
  
  <script>
    document.getElementById('verifyOTPForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const otp = document.getElementById('otp').value;
      const errorMsg = document.getElementById('errorMsg');
      errorMsg.textContent = "";
      
      try {
        const response = await fetch('api.php?action=verifyOTP', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ otp })
        });
        const result = await response.json();
        console.log(result);
        if(result.status === 'success'){
          // Redirect to reset password page
          window.location.href = "reset_password.php";
        } else {
          errorMsg.textContent = result.message;
        }
      } catch (err) {
        console.error("Fetch error:", err);
        errorMsg.textContent = "An error occurred. Please try again.";
      }
    });
  </script>
</body>
</html>
