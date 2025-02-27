<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
    <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-red-600">Admin Login</h2>
        <div id="error-message" class="hidden bg-red-200 text-red-800 p-2 rounded-lg mb-4"></div>

        <form id="admin-login-form" class="space-y-4">
            <div>
                <label class="block text-gray-700">Email</label>
                <input type="email" id="email" placeholder="Enter admin email" required 
                       class="w-full border p-2 rounded-lg focus:ring-1 focus:ring-red-600 focus:outline-none">
            </div>

            <div class="relative">
                <label class="block text-gray-700">Password</label>
                <input type="password" id="password" placeholder="Enter password" required
                       class="w-full border p-2 rounded-lg focus:ring-1 focus:ring-red-600 focus:outline-none pr-10">
                <button type="button" id="togglePassword" class="absolute right-3 top-10 text-gray-500">👁️</button>
            </div>

            <button type="submit" class="w-full bg-red-600 text-white p-2 rounded-lg hover:bg-red-700">
                Login
            </button>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            $("#admin-login-form").submit(function(event) {
                event.preventDefault();

                let email = $("#email").val().trim();
                let password = $("#password").val().trim();

                $("#error-message").addClass("hidden");

                $.ajax({
                    url: "api.php?action=admin_login",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ email: email, password: password }),
                    success: function(response) {
                        if (response.status === "success") {
                            window.location.href = "admin.php"; 
                        } else {
                            $("#error-message").text(response.message).removeClass("hidden");
                        }
                    },
                    error: function() {
                        $("#error-message").text("Something went wrong!").removeClass("hidden");
                    }
                });
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
