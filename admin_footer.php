</main>
  <!-- End Main Content Area -->

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Update Date & Time every second
      function updateDateTime() {
        const dateTimeElem = document.getElementById('dateTime');
        if (dateTimeElem) {
          dateTimeElem.innerText = new Date().toLocaleString();
        } else {
          console.warn("Element with id 'dateTime' not found");
        }
      }
      setInterval(updateDateTime, 1000);
      updateDateTime();

      // Attach logout functionality to desktop and mobile logout buttons
      function attachLogout(selector) {
        const btn = document.getElementById(selector);
        if (btn) {
          btn.addEventListener("click", function(e) {
            e.preventDefault();
            console.log("Logout button clicked:", selector);
            fetch('logout.php', { method: 'GET' })
              .then(response => response.json())
              .then(data => {
                console.log("Logout API response:", data);
                if (data.success) {
                  window.location.href = 'index.php';
                } else {
                  alert('Logout failed!');
                }
              })
              .catch(error => {
                console.error('Error during logout:', error);
                alert('Logout encountered an error.');
              });
          });
        } else {
          console.warn("Logout button not found for selector:", selector);
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
          const menuIcon = document.getElementById("menuIcon");
          if (!mobileSidebar.classList.contains("hidden")) {
            console.log("Mobile sidebar opened");
            // Show "X" icon
            menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />`;
          } else {
            console.log("Mobile sidebar closed");
            // Show hamburger icon
            menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />`;
          }
        });
      } else {
        console.warn("Menu toggle or mobile sidebar element not found");
      }

      // Toggle Tenant Dropdown on click (Desktop)
      const tenantDropdownToggle = document.getElementById("tenantDropdownToggle");
      const tenantDropdownMenu = document.getElementById("tenantDropdownMenu");
      if (tenantDropdownToggle && tenantDropdownMenu) {
        tenantDropdownToggle.addEventListener("click", function(e) {
          e.preventDefault();
          console.log("Toggling tenant dropdown (desktop)");
          tenantDropdownMenu.classList.toggle("hidden");
        });
      } else {
        console.warn("Tenant dropdown toggle or menu not found (desktop)");
      }

      // Mobile Tenant Dropdown Toggle
      const mobileTenantDropdownToggle = document.getElementById("mobileTenantDropdownToggle");
      const mobileTenantDropdownMenu = document.getElementById("mobileTenantDropdownMenu");
      if (mobileTenantDropdownToggle && mobileTenantDropdownMenu) {
        mobileTenantDropdownToggle.addEventListener("click", function(e) {
          e.preventDefault();
          console.log("Toggling tenant dropdown (mobile)");
          mobileTenantDropdownMenu.classList.toggle("hidden");
        });
      } else {
        console.warn("Tenant dropdown toggle or menu not found (mobile)");
      }
    });
  </script>
</body>
</html>
