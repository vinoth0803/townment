<?php
$pageTitle = "Add Tenant Fields - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-2xl font-bold mb-6">Add Tenant Fields</h1>
  <form id="addTenantFieldsForm" class="space-y-4 max-w-lg mx-auto">
    <div>
      <label class="block text-gray-700">Tenant Username:</label>
      <input type="text" name="username" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Door Number:</label>
      <input type="text" name="door_number" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Floor:</label>
      <input type="text" name="floor" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Block:</label>
      <input type="text" name="block" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Tenant/Lease Name:</label>
      <input type="text" name="tenant_name" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Configuration:</label>
      <input type="text" name="configuration" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-gray-700">Maintenance Cost:</label>
      <input type="number" step="0.01" name="maintenance_cost" required class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-all">Add Fields</button>
  </form>
  <div id="addTenantFieldsMessage" class="mt-4 text-center text-gray-700"></div>
</div>
<script>
  document.getElementById('addTenantFieldsForm').addEventListener('submit', async function(e){
    e.preventDefault();
    let formData = new FormData(this);
    let data = {};
    formData.forEach((value, key) => data[key] = value);
    
    const res = await fetch('api.php?action=addTenantFields', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    document.getElementById('addTenantFieldsMessage').innerText = result.message;
  });
</script>
<?php include 'admin_footer.php'; ?>
