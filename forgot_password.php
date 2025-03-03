<?php
// forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen bg-cover bg-center px-4" style="background-image: url('Assets/apartment 3.jpg');">
  <div class="bg-white p-10 rounded-2xl shadow-lg w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-6">Change Password</h1>
    <form id="forgotPasswordForm">
      <div class="mb-4">
        <label for="email" class="block text-gray-700 font-semibold mb-2">Email Address</label>
        <input 
          type="email" 
          name="email" 
          id="email" 
          placeholder="Enter your email" 
          required 
          class="w-full p-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-blue-500">
      </div>
      <button 
        type="submit" 
        class="w-full bg-blue-500 text-white p-3 rounded-2xl hover:bg-blue-600 transition duration-200">
        Get OTP
      </button>
    </form>
    <div id="errorMsg" class="mt-4 text-red-500 text-center"></div>
  </div>
  
  <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const email = document.getElementById('email').value;
      const errorMsg = document.getElementById('errorMsg');
      errorMsg.textContent = "";
      
      try {
        const response = await fetch('api.php?action=sendOTP', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email })
        });
        const result = await response.json();
        console.log(result);  // Debug: log API response
        if(result.status === 'success'){
          // Redirect to OTP verification page
          window.location.href = "verify_otp.php";
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
