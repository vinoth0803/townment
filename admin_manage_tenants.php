<?php
$pageTitle = "Manage Tenants - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-2xl font-bold mb-6">Manage Tenants</h1>
  <div class="mb-4">
    <input type="text" id="searchUsername" placeholder="Search tenant by username" class="border p-2 rounded-full w-1/2">
    <button onclick="searchTenantManage()" class="bg-[#B82132] text-white px-4 py-2 rounded-full ml-2 hover:bg-[#D2665A    ] transition">Search</button>
  </div>
  <div id="tenantsTable"></div>
</div>
<script>
  // Load default list (all tenants)
  async function loadAllTenants(){
    const response = await fetch('api.php?action=searchTenant&username=');
    const data = await response.json();
    displayTenants(data.tenants);
  }
  
  async function searchTenantManage(){
    let username = document.getElementById('searchUsername').value;
    const response = await fetch('api.php?action=searchTenant&username=' + encodeURIComponent(username));
    const data = await response.json();
    displayTenants(data.tenants);
  }
  
  function displayTenants(tenants){
    let html = '';
    if(tenants.length > 0){
      html += `<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3">Username</th>
            <th scope="col" class="px-6 py-3">Email</th>
            <th scope="col" class="px-6 py-3">Phone</th>
            <th scope="col" class="px-6 py-3">Action</th>
          </tr>
        </thead>
        <tbody class="text-gray-800">`;
      tenants.forEach(t => {
        html += `<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
          <td class=" px-4 py-2">${t.username}</td>
          <td class=" px-4 py-2">${t.email}</td>
          <td class=" px-4 py-2">${t.phone}</td>
          <td class=" px-4 py-2 text-center">
            <button onclick="deleteTenant('${t.username}')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-full transition">
              Delete
            </button>
          </td>
        </tr>`;
      });
      html += `</tbody></table>`;
    } else {
      html = `<p class="text-center py-4 text-gray-500">No tenants found.</p>`;
    }
    document.getElementById('tenantsTable').innerHTML = html;
  }
  
  async function deleteTenant(username) {
    if(!confirm("Are you sure you want to delete tenant " + username + "?")) return;
    
    try {
      // Assuming the delete endpoint accepts the username as a GET parameter
      const response = await fetch('api.php?action=deleteTenant&username=' + encodeURIComponent(username), {
        method: 'GET'
      });
      const result = await response.json();
      alert(result.message);
      // Refresh the list
      loadAllTenants();
    } catch (error) {
      console.error('Error deleting tenant:', error);
      alert('An error occurred while deleting the tenant.');
    }
  }
  
  window.onload = loadAllTenants;
</script>
<?php include 'admin_footer.php'; ?>
