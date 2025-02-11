<?php
// tenant.php
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tenant') {
    header('Location: index.php');
    exit;
}
$tenant = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Dashboard - TOWNMENT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .sidebar { min-width: 220px; }
  </style>
</head>
<body class="bg-gray-100 flex">
  <!-- Sidebar -->
  <div class="sidebar bg-white h-screen shadow-md p-4">
    <h2 class="text-xl font-bold mb-6">TENANT</h2>
    <ul class="space-y-4">
      <li><a href="#" onclick="loadDashboard()" class="text-blue-500 hover:underline">Dashboard</a></li>
      <li><a href="#" onclick="loadProfile()" class="text-blue-500 hover:underline">Profile</a></li>
      <li><a href="#" onclick="loadGasUsage()" class="text-blue-500 hover:underline">Gas Usage</a></li>
      <li><a href="#" onclick="loadBills()" class="text-blue-500 hover:underline">Bills</a></li>
      <li><a href="#" onclick="loadEBDetails()" class="text-blue-500 hover:underline">EB Details</a></li>
      <li><a href="logout.php" class="text-red-500 hover:underline">Logout</a></li>
    </ul>
  </div>
  <!-- Main Content Area -->
  <div id="content" class="flex-1 p-6 overflow-auto">
    <h1 class="text-2xl font-bold">Welcome to Tenant Dashboard</h1>
    <p>Select an option from the sidebar.</p>
  </div>

  <script>
    // Dashboard: display tenant profile and latest bill paid.
    async function loadDashboard(){
      document.getElementById('content').innerHTML = `<h1 class="text-2xl font-bold mb-4">Dashboard</h1>
      <div id="dashboardContent"></div>`;
      
      // Load profile
      let profileResponse = await fetch('api.php?action=getProfile&user_id=<?php echo $tenant['id']; ?>');
      let profileData = await profileResponse.json();
      // Load latest bill
      let billResponse = await fetch('api.php?action=getLatestBill&user_id=<?php echo $tenant['id']; ?>');
      let billData = await billResponse.json();
      
      let html = `<h2 class="text-xl font-bold">Profile</h2>
      <p><strong>Username:</strong> ${profileData.data.username}</p>
      <p><strong>Email:</strong> ${profileData.data.email}</p>
      <p><strong>Phone:</strong> ${profileData.data.phone}</p>
      <h2 class="text-xl font-bold mt-4">Latest Bill</h2>`;
      
      if(billData.data){
        html += `<table class="min-w-full border">
          <thead>
            <tr>
              <th class="border p-2">Maintenance Bill</th>
              <th class="border p-2">EB Bill</th>
              <th class="border p-2">Gas Bill</th>
              <th class="border p-2">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border p-2">${billData.data.maintenance_bill}</td>
              <td class="border p-2">${billData.data.eb_bill}</td>
              <td class="border p-2">${billData.data.gas_bill}</td>
              <td class="border p-2">${billData.data.is_paid == 1 ? 'Paid' : 'Unpaid'}</td>
            </tr>
          </tbody>
        </table>`;
      } else {
        html += `<p>No bills available.</p>`;
      }
      
      document.getElementById('dashboardContent').innerHTML = html;
    }
    
    async function loadProfile(){
      const response = await fetch('api.php?action=getProfile&user_id=<?php echo $tenant['id']; ?>');
      const data = await response.json();
      let html = `<h1 class="text-2xl font-bold mb-4">Profile</h1>
      <p><strong>Username:</strong> ${data.data.username}</p>
      <p><strong>Email:</strong> ${data.data.email}</p>
      <p><strong>Phone:</strong> ${data.data.phone}</p>`;
      document.getElementById('content').innerHTML = html;
    }
    
    async function loadGasUsage(){
      const response = await fetch('api.php?action=getGasUsage&user_id=<?php echo $tenant['id']; ?>');
      const data = await response.json();
      let html = `<h1 class="text-2xl font-bold mb-4">Gas Usage</h1>`;
      if(data.data && data.data.length > 0){
        html += `<table class="min-w-full border">
          <thead>
            <tr>
              <th class="border p-2">Usage Date</th>
              <th class="border p-2">Usage Amount</th>
            </tr>
          </thead>
          <tbody>`;
        data.data.forEach(item => {
          html += `<tr>
            <td class="border p-2">${item.usage_date}</td>
            <td class="border p-2">${item.usage_amount}</td>
          </tr>`;
        });
        html += `</tbody></table>`;
      } else {
        html += `<p>No gas usage data available.</p>`;
      }
      document.getElementById('content').innerHTML = html;
    }
    
    async function loadBills(){
      const response = await fetch('api.php?action=getLatestBill&user_id=<?php echo $tenant['id']; ?>');
      const data = await response.json();
      let html = `<h1 class="text-2xl font-bold mb-4">Bills</h1>`;
      if(data.data){
        html += `<table class="min-w-full border">
          <thead>
            <tr>
              <th class="border p-2">Maintenance Bill</th>
              <th class="border p-2">EB Bill</th>
              <th class="border p-2">Gas Bill</th>
              <th class="border p-2">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border p-2">${data.data.maintenance_bill}</td>
              <td class="border p-2">${data.data.eb_bill}</td>
              <td class="border p-2">${data.data.gas_bill}</td>
              <td class="border p-2">${data.data.is_paid == 1 ? 'Paid' : 'Unpaid'}</td>
            </tr>
          </tbody>
        </table>`;
      } else {
        html += `<p>No bills available.</p>`;
      }
      document.getElementById('content').innerHTML = html;
    }
    
    async function loadEBDetails(){
      const response = await fetch('api.php?action=getEBDetails&user_id=<?php echo $tenant['id']; ?>');
      const data = await response.json();
      let html = `<h1 class="text-2xl font-bold mb-4">EB Details</h1>`;
      if(data.data){
        html += `<p><strong>Details:</strong> ${data.data.detail}</p>`;
        html += `<p><strong>Updated at:</strong> ${data.data.updated_at}</p>`;
      } else {
        html += `<p>No EB details available.</p>`;
      }
      document.getElementById('content').innerHTML = html;
    }
  </script>
</body>
</html>
