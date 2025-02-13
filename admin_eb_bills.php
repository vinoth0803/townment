<?php
$pageTitle = "Update Maintenance Details - TOWNMENT";
include 'admin_header.php';
?>

<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-3xl font-bold mb-6 text-gray-800">EB Details</h1>

  <!-- Maintenance Table Container -->
  <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
          <th class="py-3 px-6 text-left">Tenant Name</th>
          <th class="py-3 px-6 text-left">Block</th>
          <th class="py-3 px-6 text-left">Door No</th>
          <th class="py-3 px-6 text-left">Phone</th>
          <th class="py-3 px-6 text-left">Paid On</th>
          <th class="py-3 px-6 text-center">Status</th>
        </tr>
      </thead>
      <tbody id="maintenanceTable" class="text-gray-600 text-sm">
        <!-- Rows will be populated via JavaScript -->
      </tbody>
    </table>
  </div>
</div>

<script>
  // Fetch maintenance details via API and populate the table.
  async function fetchMaintenance() {
    try {
      const response = await fetch('api.php?action=getMaintenance');
      const data = await response.json();
      
      const tableBody = document.getElementById('maintenanceTable');
      tableBody.innerHTML = '';
      
      if (data.status === 'success' && data.maintenance.length > 0) {
        const currentDate = new Date();

        data.maintenance.forEach(tenant => {
          let statusClass = "text-red-600 font-bold"; // Default: Unpaid
          let statusText = "Unpaid";

          if (tenant.status === "paid") {
            statusClass = "text-green-600 font-bold";
            statusText = "Paid";
          } else if (currentDate.getDate() > 10) {
            statusClass = "text-yellow-600 font-bold";
            statusText = "Overdue";
          }

          let paidOn = tenant.paid_on ? tenant.paid_on : "N/A";

          const row = `
            <tr class="border-b border-gray-200 hover:bg-gray-100">
              <td class="py-3 px-6">${tenant.tenant_name}</td>
              <td class="py-3 px-6">${tenant.block}</td>
              <td class="py-3 px-6">${tenant.door_number}</td>
              <td class="py-3 px-6">${tenant.phone}</td>
              <td class="py-3 px-6">${paidOn}</td>
              <td class="py-3 px-6 text-center ${statusClass}">${statusText}</td>
            </tr>
          `;
          tableBody.innerHTML += row;
        });
      } else {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-gray-500">No maintenance records found.</td></tr>`;
      }
    } catch (error) {
      console.error('Error fetching maintenance data:', error);
      document.getElementById('maintenanceTable').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
    }
  }
  
  // Load data on page load.
  fetchMaintenance();
</script>

<?php include 'admin_footer.php'; ?>
