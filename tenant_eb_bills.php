<?php
$pageTitle = "Tenant EB Bills - TOWNMENT";
include 'tenant_header.php';

// Determine profile photo from session or default (for tenant dashboard header, if needed)
$profile_photo = isset($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['profile_photo'])
    ? $_SESSION['user']['profile_photo']
    : 'Assets/Default Profile picture.png';
?>
<div class="p-6">
  <h1 class="text-3xl font-bold mb-6 text-gray-800">Your Electricity Bill Details</h1>
  <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="w-full border-collapse">
      <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
        <tr>
          <th class="py-3 px-6 text-left">Tenant Name</th>
          <th class="py-3 px-6 text-left">Block</th>
          <th class="py-3 px-6 text-left">Door No</th>
          <th class="py-3 px-6 text-left">Phone No</th>
          <th class="py-3 px-6 text-left">Paid On</th>
          <th class="py-3 px-6 text-center">Status</th>
          <th class="py-3 px-6 text-center">Action</th>
        </tr>
      </thead>
      <tbody id="tenantEbTable" class="text-gray-600 text-sm">
        <!-- Content loaded via JS -->
      </tbody>
    </table>
  </div>
</div>
<script>
  async function fetchTenantEBDebt() {
    try {
      const response = await fetch('api.php?action=getTenantEBDebt');
      const data = await response.json();
      let html = '';
      if (data.status === 'success' && data.eb && Object.keys(data.eb).length > 0) {
        // data.eb contains tenant-specific eb bill details plus tenant fields
        const eb = data.eb;
        // Determine status as per the logic: "paid" if marked paid, otherwise "unpaid" by default,
        // and "overdue" if current day > 10.
        let statusText = eb.status;
        let statusClass = 'text-red-600';
        const now = new Date();
        const day = now.getDate();
        if (eb.status === 'paid') {
          statusText = 'Paid';
          statusClass = 'text-green-600';
        } else if (day > 10) {
          statusText = 'Overdue';
          statusClass = 'text-yellow-600';
        }
        let paidOn = eb.paid_on ? eb.paid_on : 'N/A';
        html += `
          <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-6">${eb.tenant_name}</td>
            <td class="py-3 px-6">${eb.block}</td>
            <td class="py-3 px-6">${eb.door_number}</td>
            <td class="py-3 px-6">${eb.phone}</td>
            <td class="py-3 px-6">${paidOn}</td>
            <td class="py-3 px-6 text-center font-bold ${statusClass}">${statusText}</td>
            <td class="py-3 px-6 text-center">
              ${eb.status === 'paid' ? '' : `<button onclick="markAsPaid()" class="bg-green-500 text-white px-3 py-1 rounded">Mark as Paid</button>`}
            </td>
          </tr>
        `;
      } else {
        html = `<tr><td colspan="7" class="text-center py-4">No EB bill details found.</td></tr>`;
      }
      document.getElementById('tenantEbTable').innerHTML = html;
    } catch (error) {
      console.error("Error fetching tenant EB bill:", error);
      document.getElementById('tenantEbTable').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
    }
  }
  
  async function markAsPaid() {
    try {
      const response = await fetch('api.php?action=markAsPaid', {
        method: 'POST'
      });
      const data = await response.json();
      if (data.status === 'success') {
        alert("Your EB bill marked as paid!");
        fetchTenantEBDebt();
      } else {
        alert("Failed to mark as paid: " + data.message);
      }
    } catch (error) {
      console.error("Error marking as paid:", error);
      alert("Error marking as paid.");
    }
  }
  
  fetchTenantEBDebt();
</script>
<?php include 'tenant_footer.php'; ?>
