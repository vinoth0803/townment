<?php
$pageTitle = "Gas Usage - TOWNMENT";
include 'admin_header.php';
?>

<!-- CSS to remove number input arrows -->
<style>
.no-arrows::-webkit-outer-spin-button,
.no-arrows::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.no-arrows {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>

<div class="flex-1 p-6 overflow-auto">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Add Gas Usage</h1>
    
    <!-- Gas Usage Update Table -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Username</th>
                <th class="py-3 px-6 text-left">Phone</th>
                <th class="py-3 px-6 text-left">Gas Consumed (kg)</th>
                <th class="py-3 px-6 text-left">Amount</th>
                <th class="py-3 px-6 text-left">Action</th>
            </tr>
        </thead>
        <tbody id="gasUsageTable">
            <!-- Data will be populated by JavaScript -->
        </tbody>
    </table>
</div>


    <!-- Gas Usage Details Table with Filters -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Gas Usage Details</h2>
        <div class="flex flex-wrap gap-4 mb-4">
            <div>
                <label for="filterStatus" class="block text-gray-700">Status</label>
                <select id="filterStatus" class="border rounded p-2">
                    <option value="">All</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div>
                <label for="filterTenant" class="block text-gray-700">Tenant Username</label>
                <input type="text" id="filterTenant" placeholder="Tenant username" class="border rounded p-2">
            </div>
            <div class="flex items-end">
                <button id="filterBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
            </div>
        </div>
        <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Username</th>
                        <th class="py-3 px-6 text-left">Gas Consumed (kg)</th>
                        <th class="py-3 px-6 text-left">Amount (in Kg)</th>
                        <th class="py-3 px-6 text-left">Due Date</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Paid On</th>
                    </tr>
                </thead>
                <tbody id="gasUsageDetailsTable">
                    <!-- Gas usage details will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function fetchTenants() {
    try {
        const response = await fetch('api.php?action=getTenants');
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error("Failed to fetch tenants");
        }

        const tenants = data.tenants;
        const tableBody = document.getElementById('gasUsageTable');
        tableBody.innerHTML = '';

        tenants.forEach(tenant => {
    const row = `
        <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-6 text-left">${tenant.username}</td>
            <td class="py-3 px-6 text-left">${tenant.phone}</td>
            <td class="py-3 px-6 text-left">
                <div class="relative">
                    <input type="number" step="0.01" min="0" 
                           class="border p-2 w-full gas-consumed no-arrows" 
                           data-username="${tenant.username}"
                           oninput="this.value = this.value.replace(/[^0-9.]/g, ''); if(this.value < 0){ this.value = Math.abs(this.value); }">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-500">kg</span>
                </div>
            </td>
            <td class="py-3 px-6 text-left">
                <input type="number" step="0.01" min="0" 
                       class="border p-2 w-full gas-amount no-arrows" 
                       data-username="${tenant.username}"
                       oninput="this.value = this.value.replace(/[^0-9.]/g, ''); if(this.value < 0){ this.value = Math.abs(this.value); }">
            </td>
            <td class="py-3 px-6 text-left">
                <button class="bg-[#B82132] text-white px-4 py-2 rounded-2xl update-bill" data-username="${tenant.username}">
                    Update Bill
                </button>
            </td>
        </tr>
    `;
    tableBody.innerHTML += row;
});


        attachEventListeners();
    } catch (error) {
        console.error('Error fetching tenants:', error);
    }
}

function attachEventListeners() {
    document.querySelectorAll('.update-bill').forEach(button => {
        button.addEventListener('click', async function () {
            const username = this.dataset.username;
            const gasConsumed = document.querySelector(`.gas-consumed[data-username="${username}"]`).value;
            const amount = document.querySelector(`.gas-amount[data-username="${username}"]`).value;

            if (!gasConsumed || !amount) {
                alert('Please enter gas consumption and amount.');
                return;
            }

            try {
                const response = await fetch('api.php?action=updateGasBill', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, gasConsumed, amount })
                });
                const result = await response.json();
                alert(result.message);
                fetchTenants(); // Refresh update table
                fetchGasUsageDetails(); // Refresh details table
            } catch (error) {
                console.error('Error updating gas bill:', error);
            }
        });
    });
}

async function fetchGasUsageDetails(){
    try {
        // Get filter values
        const status = document.getElementById('filterStatus').value;
        const tenant = document.getElementById('filterTenant').value;
        // Build query string
        let query = 'api.php?action=getGasDetails';
        const params = new URLSearchParams();
        if(status) params.append('status', status);
        if(tenant) params.append('tenant_username', tenant);
        if([...params].length > 0){
            query += '&' + params.toString();
        }
        const response = await fetch(query);
        const data = await response.json();
        if(data.status !== 'success'){
            throw new Error("Failed to fetch gas usage details");
        }
        let usage = data.gasUsage;
        let html = '';
        if(usage.length > 0){
            usage.forEach(u => {
                html += `
                <tr class="border-b hover:bg-gray-100">
                    <td class="py-3 px-6 text-left">${u.tenant_username}</td>
                    <td class="py-3 px-6 text-left">${u.gas_consumed} kg</td>
                    <td class="py-3 px-6 text-left">₹ ${u.amount}</td>
                    <td class="py-3 px-6 text-left">${u.due_date}</td>
                    <td class="py-3 px-6 text-left ${
  u.status && u.status.toLowerCase() === 'paid'
    ? 'text-green-500'
    : u.status && u.status.toLowerCase() === 'unpaid'
    ? 'text-yellow-500'
    : u.status && u.status.toLowerCase() === 'overdue'
    ? 'text-red-500'
    : 'text-gray-500'
}">${u.status}</td>

                    <td class="py-3 px-6 text-left">${u.paid_on || 'N/A'}</td>
                </tr>
                `;
            });
        } else {
            html = `<tr><td colspan="6" class="text-center py-4 text-gray-500">No gas usage details found.</td></tr>`;
        }
        document.getElementById('gasUsageDetailsTable').innerHTML = html;
    } catch(err){
        console.error("Error fetching gas usage details:", err);
        document.getElementById('gasUsageDetailsTable').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Error loading gas usage details.</td></tr>`;
    }
}

document.getElementById('filterBtn').addEventListener('click', function(){
    fetchGasUsageDetails();
});

window.onload = function(){
    fetchTenants();
    fetchGasUsageDetails();
}
</script>

<?php include 'admin_footer.php'; ?>
