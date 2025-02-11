</main>
  <!-- End Main Content Area -->

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Update Date & Time every second
      function updateDateTime() {
        document.getElementById('dateTime').innerText = new Date().toLocaleString();
      }
      setInterval(updateDateTime, 1000);
      updateDateTime();

      // Populate Admin Details when DOM is loaded
      const admin = <?php echo json_encode($admin); ?>;
      if (admin) {
        document.getElementById('adminUsername').innerText = "Username: " + (admin.username || "N/A");
        document.getElementById('adminEmail').innerText = "Email: " + (admin.email || "N/A");
        document.getElementById('adminPhone').innerText = "Phone: " + (admin.phone || "N/A");
      }

      // Toggle Profile Dropdown on click
      document.getElementById("profileIcon").addEventListener("click", function() {
        document.getElementById("profileDropdown").classList.toggle("hidden");
      });

      // Attach logout functionality to desktop and mobile logout buttons
      function attachLogout(selector) {
        const btn = document.getElementById(selector);
        if (btn) {
          btn.addEventListener("click", function(e) {
            e.preventDefault();
            fetch('logout.php', { method: 'GET' })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  window.location.href = 'login.php';
                } else {
                  alert('Logout failed!');
                }
              })
              .catch(error => console.error('Error:', error));
          });
        }
      }
      attachLogout('logoutBtn');
      attachLogout('mobileLogoutBtn');

      // Mobile sidebar toggle
      const menuToggle = document.getElementById("menuToggle");
      const mobileSidebar = document.getElementById("mobileSidebar");
      
      if (menuToggle && mobileSidebar) {
        menuToggle.addEventListener("click", function() {
          mobileSidebar.classList.toggle("hidden");
          // Toggle the menu icon between hamburger and "X"
          const menuIcon = document.getElementById("menuIcon");
          if (!mobileSidebar.classList.contains("hidden")) {
            // Show "X" icon
            menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />`;
          } else {
            // Show hamburger icon
            menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />`;
          }
        });
      }

      // Toggle Tenant Dropdown on click (Desktop)
      const tenantDropdownToggle = document.getElementById("tenantDropdownToggle");
  const tenantDropdownMenu = document.getElementById("tenantDropdownMenu");
  if (tenantDropdownToggle && tenantDropdownMenu) {
    tenantDropdownToggle.addEventListener("click", function(e) {
      e.preventDefault();
      tenantDropdownMenu.classList.toggle("hidden");
    });
  }

      // Mobile Tenant Dropdown Toggle
      const mobileTenantDropdownToggle = document.getElementById("mobileTenantDropdownToggle");
      const mobileTenantDropdownMenu = document.getElementById("mobileTenantDropdownMenu");
      if (mobileTenantDropdownToggle && mobileTenantDropdownMenu) {
        mobileTenantDropdownToggle.addEventListener("click", function(e) {
          e.preventDefault();
          mobileTenantDropdownMenu.classList.toggle("hidden");
        });
      }
    });
  </script>
</body>
</html>
