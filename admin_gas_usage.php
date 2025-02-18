<?php
$pageTitle = "Gas Usage - TOWNMENT";
include 'admin_header.php';
?>

<div class="flex-1 p-6 overflow-auto">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Gas Usage</h1>
    
    <!-- Gas Usage Table -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Username</th>
                    <th class="py-3 px-6 text-left">Tenant Name</th>
                    <th class="py-3 px-6 text-left">Block</th>
                    <th class="py-3 px-6 text-left">Door No</th>
                    <th class="py-3 px-6 text-left">Phone</th>
                    <th class="py-3 px-6 text-left">Gas Consumed</th>
                    <th class="py-3 px-6 text-left">Amount</th>
                    <th class="py-3 px-6 text-left">Due Date</th>
                    <th class="py-3 px-6 text-left">Status</th>
                    <th class="py-3 px-6 text-left">Action</th>
                </tr>
            </thead>
            <tbody id="gasUsageTable">
                <!-- Data will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<script>
async function fetchTenants() {
    try {
        const response = await fetch('api.php?action=getTenants');
        const tenants = await response.json();
        const tableBody = document.getElementById('gasUsageTable');
        tableBody.innerHTML = '';

        tenants.forEach(tenant => {
            const today = new Date().toISOString().split('T')[0]; // Current date
            let status = tenant.status;
            if (status === 'unpaid' && today > tenant.due_date) {
                status = 'overdue';
            }

            const row = `
                <tr class="border-b hover:bg-gray-100">
                    <td class="py-3 px-6 text-left">${tenant.username}</td>
                    <td class="py-3 px-6 text-left">${tenant.tenant_name}</td>
                    <td class="py-3 px-6 text-left">${tenant.block}</td>
                    <td class="py-3 px-6 text-left">${tenant.door_no}</td>
                    <td class="py-3 px-6 text-left">${tenant.phone}</td>
                    <td class="py-3 px-6 text-left">
                        <input type="number" step="0.01" class="border p-2 w-full gas-consumed" data-username="${tenant.username}">
                    </td>
                    <td class="py-3 px-6 text-left">
                        <input type="number" step="0.01" class="border p-2 w-full gas-amount" data-username="${tenant.username}">
                    </td>
                    <td class="py-3 px-6 text-left">${tenant.due_date || 'N/A'}</td>
                    <td class="py-3 px-6 text-left font-bold ${status === 'overdue' ? 'text-red-500' : 'text-yellow-500'}">${status}</td>
                    <td class="py-3 px-6 text-left">
                        <button class="bg-[#B82132] text-white px-4 py-2 rounded-2xl update-bill" data-username="${tenant.username}">Update Bill</button>
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
                fetchTenants(); // Refresh table
            } catch (error) {
                console.error('Error updating gas bill:', error);
            }
        });
    });
}

fetchTenants(); // Load tenants on page load
</script>

<?php include 'admin_footer.php'; ?>
