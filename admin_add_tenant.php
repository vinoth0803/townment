<?php
$pageTitle = "Add a New Tenant - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-2xl font-bold mb-4">Add a New Tenant</h1>
  <form id="addTenantForm" class="space-y-4">
    <div>
      <label>Username:</label>
      <input type="text" name="username" required class="border p-2 rounded w-full">
    </div>
    <div>
      <label>Email:</label>
      <input type="email" name="email" required class="border p-2 rounded w-full">
    </div>
    <div>
      <label>Phone:</label>
      <input type="text" name="phone" required class="border p-2 rounded w-full">
    </div>
    <div>
      <label>Password:</label>
      <input type="password" name="password" required class="border p-2 rounded w-full">
    </div>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Add Tenant</button>
  </form>
  <div id="addTenantMessage" class="mt-4"></div>
</div>
<script>
  document.getElementById('addTenantForm').addEventListener('submit', async function(e){
    e.preventDefault();
    let formData = new FormData(this);
    let data = {};
    formData.forEach((value, key) => data[key] = value);
    const res = await fetch('api.php?action=addTenant', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    document.getElementById('addTenantMessage').innerText = result.message;
  });
</script>
<?php include 'admin_footer.php'; ?>
