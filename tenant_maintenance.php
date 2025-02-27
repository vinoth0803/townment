<?php
$pageTitle = "Maintenance Payment - TOWNMENT";
include 'tenant_header.php';
?>
<div class="container mx-auto p-6">
  <h1 class="text-3xl font-bold mb-6">Maintenance Payment</h1>
  <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="min-w-full border-collapse">
      <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
        <tr>
          <th class="border px-4 py-2 text-left">Tenant Name</th>
          <th class="border px-4 py-2 text-left">Maintenance Cost</th>
          <th class="border px-4 py-2 text-left">Due Date</th>
          <th class="border px-4 py-2 text-left">Status</th>
          <th class="border px-4 py-2 text-center">Action</th>
        </tr>
      </thead>
      <tbody id="maintenanceTable" class="text-gray-800"></tbody>
    </table>
  </div>
</div>

<!-- Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
async function loadMaintenance() {
    try {
        // Fetch the tenant's maintenance records
        const res = await fetch('api.php?action=getTenantMaintenance');
        const data = await res.json();
        if (data.status !== 'success') {
            alert(data.message || 'Error fetching records');
            return;
        }
        let records = data.maintenance;
        let html = '';
        if (records && records.length > 0) {
            records.forEach(record => {
                // Show "Pay Now" only if the record is unpaid.
                let actionButton = (record.status.toLowerCase() === 'paid') 
                    ? 'Paid' 
                    : `<button onclick="payMaintenance(${record.id}, ${record.maintenance_cost})" class="bg-blue-500 text-white px-4 py-2 rounded">Pay Now</button>`;
                html += `<tr class="border-b hover:bg-gray-100">
                    <td class="border px-4 py-3">${record.tenant_name}</td>
                    <td class="border px-4 py-3">${parseFloat(record.maintenance_cost).toFixed(2)}</td>
                    <td class="border px-4 py-3">${record.due_date || 'N/A'}</td>
                    <td class="border px-4 py-3">${record.status}</td>
                    <td class="border px-4 py-3 text-center">${actionButton}</td>
                </tr>`;
            });
        } else {
            html = `<tr><td colspan="5" class="text-center py-4">No maintenance records found.</td></tr>`;
        }
        document.getElementById('maintenanceTable').innerHTML = html;
    } catch (error) {
        console.error('Error fetching maintenance records:', error);
        alert('Error fetching maintenance records.');
    }
}

async function payMaintenance(recordId, amount) {
    try {
        // Create a maintenance order via the API
        const response = await fetch('api.php?action=createMaintenanceOrder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recordId })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            alert(data.message || 'Error creating order');
            return;
        }
        const orderId = data.order_id;
        // Set up Razorpay options
        const options = {
            "key": "rzp_test_QBNbWNS9QSRoaK", // Your Razorpay Key Id
            "amount": amount * 100, // Amount in paise
            "currency": "INR",
            "name": "TOWNMENT",
            "description": "Maintenance Payment",
            "order_id": orderId,
            "handler": async function (response) {
                // On payment success, capture the payment
                const captureResponse = await fetch('api.php?action=captureMaintenancePayment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        id: recordId
                    })
                });
                const captureData = await captureResponse.json();
                if (captureData.status === 'success') {
                    alert('Maintenance fee paid successfully!');
                    loadMaintenance(); // Refresh the records
                } else {
                    alert(captureData.message || 'Payment capture failed');
                }
            },
            "prefill": {
                // Optionally prefill tenant details
                "name": "",
                "email": "",
                "contact": ""
            },
            "theme": { "color": "#528FF0" }
        };
        const rzp1 = new Razorpay(options);
        rzp1.open();
    } catch (error) {
        console.error('Error processing payment:', error);
        alert('Error processing payment.');
    }
}

window.onload = loadMaintenance;
</script>

<?php include 'tenant_footer.php'; ?>
