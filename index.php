<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TOWNMENT - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen bg-cover bg-center px-4" 
      style="background-image: url('Assets/apartment2.jpeg');">

  <div class="bg-[#F6DED8] p-6 rounded-3xl shadow-md w-full max-w-sm sm:max-w-md bg-opacity-90">
      <h2 class="text-2xl font-bold mb-4 text-center text-[#B82132]">TOWNMENT LOGIN</h2>
      <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded-full mb-4 hidden"></div>

      <form id="login-form" class="space-y-4">
          <!-- Email Input -->
          <div>
              <label class="block text-[#B82132] font-semibold">Email</label>
              <input type="email" id="email" placeholder="Enter your email" required 
                     class="w-full border border-white p-2 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none">
          </div>

          <!-- Password Input with Toggle -->
          <div class="relative">
              <label class="block text-[#B82132] font-semibold">Password</label>
              <input type="password" id="password" placeholder="Enter your password" required
                     class="w-full border border-white p-2 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none pr-10">
              <button type="button" id="togglePassword" 
                      class="absolute right-3 top-10 text-gray-500">
                  👁️
              </button>
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
                  $("#login-btn").prop("disabled", false);
                  if (response.status === "success") {
                      alert("Login successful!");
                      let redirectPage = (response.user.role === "admin") ? "admin.php" : "tenant_dashboard.php";
                      window.location.href = redirectPage;
                  } else {
                      $("#error-message").text(response.message).removeClass("hidden");
                  }
              },
              error: function() {
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
      });
    });
  </script>

</body>
</html>
