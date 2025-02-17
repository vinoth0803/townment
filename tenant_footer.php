<script>
document.addEventListener("DOMContentLoaded", function() {
  // Update live date & time
  function updateDateTime() {
  const now = new Date();
  const day = String(now.getDate()).padStart(2, '0');
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const year = now.getFullYear();
  const time = now.toLocaleTimeString();
  
  document.getElementById('dateTime').innerText = `${day}/${month}/${year} ${time}`;
}

setInterval(updateDateTime, 1000);
updateDateTime();

 // Toggle Google Calendar
 document.getElementById('calendarBtn').addEventListener('click', function () {
      let calendarCard = document.getElementById('calendarCard');
      calendarCard.style.display = calendarCard.style.display === 'block' ? 'none' : 'block';
    });

  // Toggle Profile Dropdown
  document.getElementById('profileIcon').addEventListener('click', function () {
      let profileDropdown = document.getElementById('profileDropdown');
      profileDropdown.classList.toggle('hidden');
    });
  

  // Attach logout functionality
  function attachLogout(selector) {
    const btn = document.getElementById(selector);
    if (btn) {
      btn.addEventListener("click", function(e) {
        e.preventDefault();
        fetch('logout.php', { method: 'GET' })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              window.location.href = 'index.php';
            } else {
              alert('Logout failed!');
            }
          })
          .catch(error => console.error('Logout Error:', error));
      });
    }
  }
  attachLogout('logoutBtn');
  attachLogout('mobileLogoutBtn');

  // Mobile sidebar toggle
  const menuToggle = document.getElementById("menuToggle");
  const mobileSidebar = document.getElementById("mobileSidebar");
  const menuIcon = document.getElementById("menuIcon");
  if (menuToggle && mobileSidebar && menuIcon) {
    menuToggle.addEventListener("click", function() {
      mobileSidebar.classList.toggle("-translate-x-full");
      // Toggle icon between hamburger and X
      if (mobileSidebar.classList.contains("-translate-x-full")) {
        menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />`;
      } else {
        menuIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />`;
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
