<?php
$pageTitle = "Maintenance - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">Maintenance</h1>
  
  <!-- Filter Section -->
  <div class="mb-4 flex flex-wrap items-center space-y-2 md:space-y-0">
    <input type="text" id="searchTenant" placeholder="Filter by tenant name" class="border p-2 rounded w-full md:w-1/3">
    <select id="dateRange" class="border p-2 rounded w-full md:w-auto">
      <option value="">Date Range</option>
      <option value="7">Last 7 days</option>
      <option value="90">Last 3 months</option>
      <option value="180">Last 6 months</option>
    </select>
    <select id="blockFilter" class="border p-2 rounded w-full md:w-auto">
      <option value="">All Blocks</option>
      <option value="A">A</option>
      <option value="B">B</option>
      <option value="C">C</option>
    </select>
    <select id="floorFilter" class="border p-2 rounded w-full md:w-auto">
      <option value="">All Floors</option>
      <option value="1">1</option>
      <option value="2">2</option>
      <option value="3">3</option>
      <option value="4">4</option>
    </select>
    <button onclick="loadMaintenance()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition ml-2">
      Filter
    </button>
  </div>

  <!-- Maintenance Table Container -->
  <div class="bg-white shadow-md rounded-lg p-4 overflow-x-auto">
    <table class="min-w-full border-collapse">
      <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
        <tr>
          <th class="border px-4 py-2 text-left">Tenant Name</th>
          <th class="border px-4 py-2 text-left">Block</th>
          <th class="border px-4 py-2 text-left">Door No</th>
          <th class="border px-4 py-2 text-left">Phone</th>
          <th class="border px-4 py-2 text-left">Paid On</th>
          <th class="border px-4 py-2 text-left">Amount</th>
          <th class="border px-4 py-2 text-center">Status</th>
        </tr>
      </thead>
      <tbody id="maintenanceTable" class="text-gray-800"></tbody>
    </table>
  </div>
</div>

<script>
async function loadMaintenance() {
    try {
        // Gather filter values
        const search = document.getElementById('searchTenant').value;
        const dateRange = document.getElementById('dateRange').value;
        const block = document.getElementById('blockFilter').value;
        const floor = document.getElementById('floorFilter').value;
        
        // Build query parameters
        let params = new URLSearchParams();
        params.append('action', 'getMaintenance');
        if (search) params.append('search', search);
        if (dateRange) params.append('daterange', dateRange);
        if (block) params.append('block', block);
        if (floor) params.append('floor', floor);
        
        const res = await fetch('api.php?' + params.toString());
        const data = await res.json();
        let maints = data.maintenance;
        let html = '';

        if (maints && maints.length > 0) {
            // Get current date for extra charge calculation
            const currentDate = new Date();
            maints.forEach(m => {
                let statusText = 'Unpaid';
                let statusClass = 'bg-red-500 text-white';
                let baseCost = parseFloat(m.maintenance_cost) || 0;
                let extraCharge = 0;
                
                if (m.status === 'paid') {
                    statusText = 'Paid';
                    statusClass = 'bg-green-500 text-white';
                } else {
                    // For unpaid, if current day > 10, add extra 500 for every 5 days overdue
                    if (currentDate.getDate() > 10) {
                        let overdueDays = currentDate.getDate() - 10;
                        let increments = Math.floor(overdueDays / 5);
                        extraCharge = increments * 500;
                        statusText = 'Overdue';
                        statusClass = 'bg-yellow-500 text-white';
                    }
                }
                
                let totalAmount = baseCost + extraCharge;
                let paidOn = m.paid_on ? m.paid_on : "N/A";
                
                html += `
                    <tr class="border-b hover:bg-gray-100">
                        <td class="border px-4 py-3">${m.tenant_name}</td>
                        <td class="border px-4 py-3">${m.block}</td>
                        <td class="border px-4 py-3">${m.door_number}</td>
                        <td class="border px-4 py-3">${m.phone}</td>
                        <td class="border px-4 py-3">${paidOn}</td>
                        <td class="border px-4 py-3">${totalAmount.toFixed(2)}</td>
                        <td class="border px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded font-bold ${statusClass}">${statusText}</span>
                        </td>
                    </tr>
                `;
            });
        } else {
            html = `<tr><td colspan="7" class="text-center py-4 text-gray-500">No maintenance records found.</td></tr>`;
        }
        
        document.getElementById('maintenanceTable').innerHTML = html;
    } catch (error) {
        console.error('Error fetching maintenance data:', error);
        document.getElementById('maintenanceTable').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
    }
}

window.onload = loadMaintenance;
</script>

<?php include 'admin_footer.php'; ?>
