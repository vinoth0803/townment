<?php
$pageTitle = "Tenant Gas Usage - TOWNMENT";
include 'tenant_header.php';
?>
<div class="p-4 sm:p-6 lg:p-8">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">Gas Usage</h1>
  <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="w-full border-collapse">
      <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
        <tr>
          <th class="py-3 px-6 text-left">Tenant Name</th>
          <th class="py-3 px-6 text-left">Gas Consumed (kg)</th>
          <th class="py-3 px-6 text-left">Amount (₹)</th>
          <th class="py-3 px-6 text-left">Bill Date</th>
          <th class="py-3 px-6 text-left">Due Date</th>
          <th class="py-3 px-6 text-center">Status</th>
          <th class="py-3 px-6 text-center">Action</th>
        </tr>
      </thead>
      <tbody id="gasUsageTableBody" class="text-gray-600 text-sm">
        <!-- Rows populated via JS -->
      </tbody>
    </table>
  </div>
</div>

<!-- Include Razorpay Checkout library -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
async function loadGasUsage() {
  try {
    // Append a cache buster to ensure fresh data
    const res = await fetch('api.php?action=getGasUsage&_=' + Date.now());
    const data = await res.json();
    let html = '';

    if (data.status === 'success' && data.gas_usage && data.gas_usage.length > 0) {
      data.gas_usage.forEach(item => {
        let statusText = item.status;
        let statusClass = item.status === 'paid' ? 'text-green-600' : 'text-red-600';

        html += `
          <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-6">${item.tenant_name}</td>
            <td class="py-3 px-6">${item.gas_consumed}</td>
            <td class="py-3 px-6">${parseFloat(item.amount).toFixed(2)}</td>
            <td class="py-3 px-6">${item.bill_date}</td>
            <td class="py-3 px-6">${item.due_date}</td>
            <td class="py-3 px-6 text-center font-bold ${statusClass}">${statusText}</td>
            <td class="py-3 px-6 text-center">
              ${ item.status !== 'paid'
                  ? `<button onclick="payNow('${item.id}', ${item.amount}, '${item.tenant_name}', '${item.email}', '${item.phone}')" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition-colors">Pay Now</button>`
                  : 'Paid'
              }
            </td>
          </tr>
        `;
      });
    } else {
      html = `<tr><td colspan="10" class="text-center py-4">No gas usage records found.</td></tr>`;
    }

    document.getElementById('gasUsageTableBody').innerHTML = html;
  } catch (error) {
    console.error("Error fetching gas usage:", error);
    document.getElementById('gasUsageTableBody').innerHTML = `<tr><td colspan="10" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
  }
}

async function payNow(recordId, amount, tenantName, tenantEmail, tenantPhone) {
  try {
    // Create an order using the record id.
    const orderRes = await fetch('api.php?action=createGasOrder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: recordId })
    });
    const orderData = await orderRes.json();
    if (orderData.status !== 'success') {
      alert('Order creation failed: ' + orderData.message);
      return;
    }
    
    const options = {
      "key": "rzp_test_QBNbWNS9QSRoaK",
      "currency": "INR",
      "name": tenantName,
      "description": "Pay your gas bill",
      "order_id": orderData.order_id,
      "handler": async function(response) {
        // Capture the payment and update the gas usage record
        const captureRes = await fetch('api.php?action=captureGasPayment', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: recordId,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature
          })
        });
        const captureData = await captureRes.json();
        if (captureData.status === 'success') {
          alert("Gas bill paid successfully");
          loadGasUsage();
        } else {
          alert("Payment capture failed: " + captureData.message);
        }
      },
      "prefill": {
        "name": tenantName,
        "email": tenantEmail,
        "contact": tenantPhone
      },
      "theme": {
        "color": "#B82132"
      }
    };
    const rzp1 = new Razorpay(options);
    rzp1.open();
  } catch (error) {
    console.error("Error during payment process:", error);
    alert("Error processing payment.");
  }
}

window.onload = loadGasUsage;
</script>

<?php include 'tenant_footer.php'; ?>
