<?php
$pageTitle = "Dashboard - TOWNMENT";
include 'admin_header.php';
?>

<div class="space-y-6 p-4 sm:p-6 lg:p-8">
  <!-- Row 1: Total Tenants and Search Bar -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="bg-[#B82132] p-4 rounded shadow">
      <h2 class="text-2xl font-bold mb-2 text-center text-[#F6DED8]">Total Tenants</h2>
      <p id="totalTenants" class="text-3xl text-center text-[#F6DED8]"></p>
    </div>
    <div class="bg-[#F6DED8] p-4 rounded shadow flex flex-col sm:flex-row items-center">
      <input type="text" id="searchUsername" placeholder="Search tenant by username" 
             class="flex-1 border p-2 rounded focus:ring-1 focus:ring-[#B82132] focus:outline-none w-full sm:w-auto">
      <button onclick="searchTenantDashboard()" 
              class="bg-[#B82132] text-[#F6DED8] px-4 py-2 rounded mt-2 sm:mt-0 sm:ml-2">Search</button>
    </div>
  </div>

  <!-- Row 2: Newly Raised Tickets Reminder -->
  <div id="ticketsReminder" class="bg-white p-4 rounded shadow"></div>

  <!-- Row 3: Latest Tenants Table with Filters -->
  <div class="bg-white p-4 rounded shadow">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 space-y-4 sm:space-y-0">
      <h2 class="text-xl font-bold">Latest Tenants</h2>
      <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
        <select id="filterBHK" class="border p-2 rounded w-full sm:w-auto">
          <option value="">All BHK</option>
          <option value="1BHK">1BHK</option>
          <option value="2BHK">2BHK</option>
          <option value="3BHK">3BHK</option>
        </select>
        <select id="filterPeriod" class="border p-2 rounded w-full sm:w-auto">
          <option value="">All Periods</option>
          <option value="7">Last 7 Days</option>
          <option value="90">Last 3 Months</option>
          <option value="180">Last 6 Months</option>
          <option value="365">Last 12 Months</option>
        </select>
        <button onclick="loadLatestTenants()" 
                class="bg-[#B82132] text-[#F6DED8] px-4 py-2 rounded w-full sm:w-auto">Filter</button>
      </div>
    </div>

    <!-- Responsive Table -->
    <div class="overflow-x-auto">
      <div id="tenantsTable"></div>
    </div>
  </div>
</div>

<script>
  // Load total tenants count
  async function loadTotalTenants(){
    try {
      const res = await fetch('api.php?action=getTotalTenants');
      const data = await res.json();
      document.getElementById('totalTenants').innerText = data.total;
    } catch (error) {
      console.error("Error loading total tenants:", error);
    }
  }

  // Load new tickets and display in the "ticketsReminder" section
  async function loadNewTickets(){
    try {
      const res = await fetch('api.php?action=getNewTickets');
      const data = await res.json();
      console.log("API Response for new tickets:", data); // Debug: check API response

      const tickets = data.tickets || data;
      let html = '';
      if (tickets.length > 0) {
        tickets.forEach(ticket => {
          html += `<div class="flex items-center justify-between border-b py-2">
                      <span>${ticket.username} has raised a ticket</span>
                      <button onclick="this.parentElement.style.display='none'" class="text-red-500">x</button>
                   </div>`;
        });
      } else {
        html = `<p>No new tickets.</p>`;
      }
      document.getElementById('ticketsReminder').innerHTML = html;
    } catch (error) {
      console.error('Error fetching new tickets:', error);
    }
  }

  // Load latest tenants with filters
  async function loadLatestTenants(){
    try {
      let bhk = document.getElementById('filterBHK').value;
      let period = document.getElementById('filterPeriod').value;
      const res = await fetch(`api.php?action=getLatestTenants&bhk=${encodeURIComponent(bhk)}&period=${encodeURIComponent(period)}`);
      const data = await res.json();
      let tenants = data.tenants;
      let html = '';
      if (tenants.length > 0) {
        html += `<table class="min-w-full border">
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="border p-2">Username</th>
                      <th class="border p-2">Email</th>
                      <th class="border p-2">Phone</th>
                      <th class="border p-2">Configuration</th>
                      <th class="border p-2">Created At</th>
                    </tr>
                  </thead>
                  <tbody>`;
        tenants.forEach(t => {
          html += `<tr class="hover:bg-gray-100">
                    <td class="border p-2">${t.username}</td>
                    <td class="border p-2">${t.email}</td>
                    <td class="border p-2">${t.phone}</td>
                    <td class="border p-2">${t.configuration}</td>
                    <td class="border p-2">${t.created_at}</td>
                  </tr>`;
        });
        html += `</tbody></table>`;
      } else {
        html = `<p>No tenants found.</p>`;
      }
      document.getElementById('tenantsTable').innerHTML = html;
    } catch (error) {
      console.error("Error loading latest tenants:", error);
    }
  }

  // Search tenants by username
  async function searchTenantDashboard(){
    try {
      let username = document.getElementById('searchUsername').value;
      const res = await fetch('api.php?action=searchTenant&username=' + encodeURIComponent(username));
      const data = await res.json();
      document.getElementById('tenantsTable').innerHTML = data.tenants.length > 0 ? `<p>Results found.</p>` : `<p>No tenants found.</p>`;
    } catch (error) {
      console.error("Error searching tenants:", error);
    }
  }

  // View individual tenant profile
  async function viewTenantProfile(username){
    try {
      const res = await fetch('api.php?action=getTenantProfileByUsername&username=' + encodeURIComponent(username));
      const data = await res.json();
      document.getElementById('tenantsTable').innerHTML = `<p>Profile of ${data.tenant.username}</p>`;
    } catch (error) {
      console.error("Error loading tenant profile:", error);
    }
  }

  window.onload = function(){
    loadTotalTenants();
    loadNewTickets();
    loadLatestTenants();
  }
</script>

<?php include 'admin_footer.php'; ?>
