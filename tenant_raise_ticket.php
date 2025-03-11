  <?php
  $pageTitle = "Raise Ticket - TOWNMENT";
  include 'tenant_header.php';
  ?>
  <div class="p-4 sm:p-6 lg:p-8 space-y-6">
    <!-- Row 1: Raise Ticket Form -->
    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Raise a Ticket</h2>
      <form id="raiseTicketForm" class="flex flex-col space-y-4">
        <div>
          <label for="issue" class="block text-lg font-medium text-gray-700">Issue</label>
          <input type="text" name="issue" id="issue" placeholder="Describe your issue" class="w-full border p-2 rounded" required>
        </div>
        <div>
          <label for="issue_description" class="block text-lg font-medium text-gray-700">Issue Description</label>
          <textarea name="issue_description" id="issue_description" placeholder="Provide a detailed description of your issue" rows="5" class="w-full border p-2 rounded" required></textarea>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Raise Ticket</button>
      </form>
    </div>

    <!-- Row 2: Ticket Filters -->
    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Filter Tickets</h2>
      <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-x-4">
        <select id="filterStatus" class="border p-2 rounded w-full md:w-auto">
          <option value="">All Statuses</option>
          <option value="opened">Opened</option>
          <option value="inprogress">In Progress</option>
          <option value="closed">Closed</option>
        </select>
        <select id="filterPeriod" class="border p-2 rounded w-full md:w-auto">
          <option value="">All Periods</option>
          <option value="7">Last 7 Days</option>
          <option value="90">Last 3 Months</option>
          <option value="180">Last 6 Months</option>
        </select>
        <button onclick="loadTickets()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Apply Filters</button>
      </div>
    </div>

    <!-- Row 3: Tickets Table -->
    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Raised Tickets</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full border-collapse">
          <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
            <tr>
              <th class="border px-4 py-2">Raised Date</th>
              <th class="border px-4 py-2">Issue</th>
              <th class="border px-4 py-2">Issue Description</th>
              <th class="border px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody id="ticketsTable" class="text-gray-800"></tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
  // Function to load tickets using filters
  async function loadTickets() {
    try {
      const status = document.getElementById('filterStatus').value;
      const period = document.getElementById('filterPeriod').value;
      let params = new URLSearchParams({ action: 'getTickets' });
      if (status) params.append('status', status);
      if (period) params.append('period', period);
      
      const res = await fetch('api.php?' + params.toString());
      const data = await res.json();
      let html = '';
      if (data.status === "success" && data.tickets && data.tickets.length > 0) {
        data.tickets.forEach(ticket => {
          html += `
            <tr class="border-b hover:bg-gray-100">
              <td class="border px-4 py-2">${ticket.raised_date}</td>
              <td class="border px-4 py-2">${ticket.issue}</td>
              <td class="border px-4 py-2">${ticket.issue_description}</td>
              <td class="border px-4 py-2">${ticket.status}</td>
            </tr>
          `;
        });
      } else {
        html = `<tr><td colspan="4" class="text-center py-4">No tickets Raised.</td></tr>`;
      }
      document.getElementById('ticketsTable').innerHTML = html;
    } catch (error) {
      console.error('Error loading tickets:', error);
      document.getElementById('ticketsTable').innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Error loading tickets.</td></tr>`;
    }
  }

  // Handle raise ticket form submission
  document.getElementById('raiseTicketForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const issue = document.getElementById('issue').value.trim();
    const issueDescription = document.getElementById('issue_description').value.trim();
    if (!issue || !issueDescription) {
      alert("Please describe your issue and provide details.");
      return;
    }
    const payload = { issue, issue_description: issueDescription };
    try {
      const res = await fetch('api.php?action=raiseTicket', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status === "success") {
        alert("Ticket raised successfully!");
        document.getElementById('issue').value = '';
        document.getElementById('issue_description').value = '';
        loadTickets();
      } else {
        alert("Failed to raise ticket: " + data.message);
      }
    } catch (error) {
      console.error("Error raising ticket:", error);
      alert("Error raising ticket.");
    }
  });

  // Load tickets on page load
  window.onload = function() {
    loadTickets();
  }
  </script>

  <?php include 'tenant_footer.php'; ?>
