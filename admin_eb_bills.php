<?php
$pageTitle = "Update EB Details - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-3xl font-bold mb-6 text-gray-800">Electricity Bill Details</h1>
  
  <!-- EB Bills Table Container -->
  <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
          <th class="py-3 px-6 text-left">Tenant Name</th>
          <th class="py-3 px-6 text-left">Block</th>
          <th class="py-3 px-6 text-left">Door No</th>
          <th class="py-3 px-6 text-left">Floor</th>
          <th class="py-3 px-6 text-left">Paid On</th>
          <th class="py-3 px-6 text-center">Status</th>
        </tr>
      </thead>
      <tbody id="ebTable" class="text-gray-600 text-sm">
        <!-- Rows will be populated via JavaScript -->
      </tbody>
    </table>
  </div>
</div>

<script>
async function fetchEBDebts() {
  try {
    const response = await fetch('api.php?action=getEBDebts');
    const data = await response.json();
    let html = '';
    
    if (data.status === 'success' && data.tenants && data.tenants.length > 0) {
      data.tenants.forEach(record => {
        // Determine the status styling:
        let statusText = record.status;
        let statusClass = 'text-red-600'; // default for unpaid
        if (record.status.toLowerCase() === 'paid') {
          statusText = 'Paid';
          statusClass = 'text-green-600';
        }
        
        let paidOn = record.paid_on ? record.paid_on : 'N/A';
        html += `
          <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-6">${record.tenant_name}</td>
            <td class="py-3 px-6">${record.block}</td>
            <td class="py-3 px-6">${record.door_number}</td>
            <td class="py-3 px-6">${record.floor}</td>
            <td class="py-3 px-6">${paidOn}</td>
            <td class="py-3 px-6 text-center font-bold ${statusClass}">${statusText}</td>
          </tr>
        `;
      });
    } else {
      html = `<tr><td colspan="6" class="text-center py-4">No records found.</td></tr>`;
    }
    
    document.getElementById('ebTable').innerHTML = html;
  } catch (error) {
    console.error("Error fetching EB debts:", error);
    document.getElementById('ebTable').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
  }
}

fetchEBDebts();
</script>

<?php include 'admin_footer.php'; ?>
