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

  <div class="bg-[#F6DED8] p-6 rounded-3xl shadow-md w-full max-w-sm sm:max-w-md bg-opacity-80">
      <h2 class="text-2xl font-bold mb-4 text-center text-[#B82132]">TOWNMENT LOGIN</h2>
      <div id="error-message" class="bg-red-200 text-red-800 p-2 rounded-full mb-4 hidden"></div>
      <form id="login-form" class="space-y-4">
          <div>
              <label class="block text-[#B82132]">Email</label>
              <input type="email" id="email" required class="w-full border border-white p-2 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none">
          </div>
          <div>
              <label class="block text-[#B82132]">Password</label>
              <input type="password" id="password" required class="w-full border border-white p-2 rounded-full focus:ring-1 focus:ring-[#B82132] focus:outline-none">
          </div>
          <button type="submit" class="w-full bg-[#B82132] text-white p-2 shadow-md rounded-full transition-transform transform hover:scale-105 active:scale-95">Login</button>
      </form>
  </div>

  <script>
    $("#login-form").submit(function(event) {
        event.preventDefault(); // Prevent page refresh

        let email = $("#email").val();
        let password = $("#password").val();

        $.ajax({
            url: "api.php?action=login",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({ email: email, password: password }),
            success: function(response) {
                if (response.status === "success") {
                    alert("Login successful!");
                    window.location.href = (response.user.role === "admin") ? "admin.php" : "tenant_dashboard.php";
                } else {
                    $("#error-message").text(response.message).removeClass("hidden");
                }
            },
            error: function() {
                $("#error-message").text("Something went wrong!").removeClass("hidden");
            }
        });
    });
  </script>
</body>
</html>
