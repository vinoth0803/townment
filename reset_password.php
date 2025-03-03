<?php
// reset_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center min-h-screen">
  <div class="bg-white p-10 rounded-lg shadow-lg w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-6">Reset Password</h1>
    <form id="resetPasswordForm">
      <div class="mb-4">
        <label for="new_password" class="block text-gray-700 font-semibold mb-2">New Password</label>
        <input 
          type="password" 
          name="new_password" 
          id="new_password" 
          placeholder="Enter new password" 
          required 
          class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:border-pink-500">
      </div>
      <div class="mb-4">
        <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
        <input 
          type="password" 
          name="confirm_password" 
          id="confirm_password" 
          placeholder="Confirm new password" 
          required 
          class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:border-pink-500">
      </div>
      <button 
        type="submit" 
        class="w-full bg-pink-500 text-white p-3 rounded hover:bg-pink-600 transition duration-200">
        Update Password
      </button>
    </form>
    <div id="msg" class="mt-4 text-center"></div>
  </div>
  
  <script>
    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const new_password = document.getElementById('new_password').value;
  const confirm_password = document.getElementById('confirm_password').value;
  const msgDiv = document.getElementById('msg');
  msgDiv.textContent = "";
  
  try {
    const response = await fetch('api.php?action=resetPassword', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ new_password, confirm_password })
    });
    const result = await response.json();
    console.log(result);
    if(result.status === 'success'){
      msgDiv.textContent = result.message;
      msgDiv.className = "mt-4 text-green-500 text-center";
      // Redirect to index.php after 3 seconds
      setTimeout(() => { window.location.href = "index.php"; }, 3000);
    } else {
      msgDiv.textContent = result.message;
      msgDiv.className = "mt-4 text-red-500 text-center";
    }
  } catch (err) {
    console.error("Fetch error:", err);
    msgDiv.textContent = "An error occurred. Please try again.";
    msgDiv.className = "mt-4 text-red-500 text-center";
  }
});
  </script>
</body>
</html>
