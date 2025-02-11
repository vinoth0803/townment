<?php
$pageTitle = "Gas Usage - TOWNMENT";
include 'admin_header.php';
?>

<div class="flex-1 p-6 overflow-auto">
    <h1 class="text-2xl font-bold mb-4">Gas Usage</h1>
    
    <!-- Gas Usage Table -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-4 py-2">Username</th>
                    <th class="border px-4 py-2">Tenant Name</th>
                    <th class="border px-4 py-2">Block</th>
                    <th class="border px-4 py-2">Door No</th>
                    <th class="border px-4 py-2">Phone</th>
                    <th class="border px-4 py-2">Gas Consumed</th>
                    <th class="border px-4 py-2">Amount</th>
                    <th class="border px-4 py-2">Due Date</th>
                    <th class="border px-4 py-2">Status</th>
                    <th class="border px-4 py-2">Action</th>
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
                <tr>
                    <td class="border px-4 py-2">${tenant.username}</td>
                    <td class="border px-4 py-2">${tenant.tenant_name}</td>
                    <td class="border px-4 py-2">${tenant.block}</td>
                    <td class="border px-4 py-2">${tenant.door_no}</td>
                    <td class="border px-4 py-2">${tenant.phone}</td>
                    <td class="border px-4 py-2">
                        <input type="number" step="0.01" class="border p-2 w-full gas-consumed" data-username="${tenant.username}">
                    </td>
                    <td class="border px-4 py-2">
                        <input type="number" step="0.01" class="border p-2 w-full gas-amount" data-username="${tenant.username}">
                    </td>
                    <td class="border px-4 py-2">${tenant.due_date || 'N/A'}</td>
                    <td class="border px-4 py-2 font-bold ${status === 'overdue' ? 'text-red-500' : 'text-yellow-500'}">${status}</td>
                    <td class="border px-4 py-2">
                        <button class="bg-blue-500 text-white px-4 py-2 rounded update-bill" data-username="${tenant.username}">Update Bill</button>
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
