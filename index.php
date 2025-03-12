<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TOWNMENT - Login</title>
  <link rel="stylesheet" href="style.css">
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen bg-cover bg-center px-4" 
      style="background-image: url('Assets/apartment2.jpeg');">

  <div class="bg-[#F6DED8] p-6 rounded-3xl shadow-md w-full max-w-sm sm:max-w-md bg-opacity-90">
      <h2 class="text-2xl font-bold mb-4 text-center text-[#B82132]">TOWNMENT LOGIN</h2>
      <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded-full mb-4 hidden"></div>
      
      <!-- Lockout Timer Display -->
      <div id="lockout-timer" class="text-center text-black-500 font-semibold"></div>

      <form id="login-form" class="space-y-4">
          <!-- Email Input -->
          <div>
              <label class="block text-[#B82132] font-semibold">Email</label>
              <input type="email" id="email" placeholder="Enter your email" required 
                     class="w-full border border-white p-2 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none">
          </div>

          <!-- Password Input with Toggle -->
          <div class="relative w-full">
              <label class="block text-[#B82132] font-semibold mb-1">Password</label>
              <div class="relative">
                  <input type="password" id="password" placeholder="Enter your password" required
                         class="w-full p-3 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none pr-12">
                  <!-- Toggle button (eye icon) aligned inside input field -->
                  <button type="button" id="togglePassword" 
                          class="absolute inset-y-0 right-4 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-[#B82132]">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                      </svg>
                  </button>
              </div>
          </div>

          <!-- Forgot Password Link -->
          <div class="text-right">
              <a href="forgot_password.php" class="text-[#B82132] text-sm">Forgot Password?</a>
          </div>

          <!-- Login Button -->
          <button type="submit" id="login-btn"
                  class="w-full bg-[#B82132] text-white p-2 shadow-md rounded-full transition-transform transform hover:scale-105 active:scale-95">
              Login
          </button>

          <!-- Loading Indicator -->
          <div id="loading" class="hidden text-center text-[#B82132] font-semibold">Logging in...</div>
      </form>
  </div>

  <script>
    $(document).ready(function () {
      // Check if the user is already logged in
      $.ajax({
          url: "api.php?action=check_login",
          type: "GET",
          success: function(response) {
              if (response.status === "success") {
                  let redirectPage = (response.user.role === "admin") ? "admin.php" : "tenant_dashboard.php";
                  window.location.href = redirectPage;
              }
          },
          error: function(xhr, status, error) {
              console.error("Error checking login status:", status, error);
          }
      });

      // Handle login form submission
      $("#login-form").submit(function(event) {
          event.preventDefault();

          let email = $("#email").val().trim();
          let password = $("#password").val().trim();

          $("#error-message").addClass("hidden");
          $("#login-btn").prop("disabled", true);
          $("#loading").removeClass("hidden");

          if (!email || !password) {
              $("#error-message").text("Please fill in both fields").removeClass("hidden");
              $("#login-btn").prop("disabled", false);
              $("#loading").addClass("hidden");
              return;
          }

          $.ajax({
              url: "api.php?action=login",
              type: "POST",
              contentType: "application/json",
              data: JSON.stringify({ email: email, password: password }),
              success: function(response) {
                  $("#loading").addClass("hidden");
                  
                  if (response.status === "success") {
                      alert("Login successful!");
                      let redirectPage = (response.user.role === "admin") ? "admin.php" : "tenant_dashboard.php";
                      window.location.href = redirectPage;
                  } else {
                      $("#error-message").text(response.message).removeClass("hidden");
                      
                      // If lockout is active, start a countdown timer.
                      if (response.lockout_remaining) {
                          let remaining = response.lockout_remaining;
                          $("#login-btn").prop("disabled", true);
                          $("#lockout-timer").text("Please wait " + remaining + " seconds before trying again.");
                          
                          var interval = setInterval(function() {
                              remaining--;
                              $("#lockout-timer").text("Please wait " + remaining + " seconds before trying again.");
                              if (remaining <= 0) {
                                  clearInterval(interval);
                                  $("#lockout-timer").text("");
                                  $("#login-btn").prop("disabled", false);
                              }
                          }, 1000);
                      } else {
                          $("#login-btn").prop("disabled", false);
                      }
                  }
              },
              error: function(xhr, status, error) {
                  console.error("AJAX error:", status, error, xhr.responseText);
                  $("#error-message").text("Something went wrong! Please try again.").removeClass("hidden");
                  $("#loading").addClass("hidden");
                  $("#login-btn").prop("disabled", false);
              }
          });
      });

      $("input").focus(function () {
          $("#error-message").addClass("hidden");
      });

      $("#togglePassword").click(function() {
          let passwordField = $("#password");
          let type = passwordField.attr("type") === "password" ? "text" : "password";
          passwordField.attr("type", type);
          // Toggle the icon based on the current type.
          if (type === "text") {
              $("#togglePassword").html(`
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-[#B82132]">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                </svg>
              `);
          } else {
              $("#togglePassword").html(`
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-[#B82132]">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              `);
          }
      });
    });
  </script>

</body>
</html>
