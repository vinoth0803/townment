<?php
$pageTitle = "Dashboard - TOWNMENT";
include 'tenant_header.php';

// Use the profile photo from the session or fall back to default
$profile_photo = isset($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['profile_photo'])
    ? $_SESSION['user']['profile_photo']
    : 'Assets/Default Profile picture.png';
?>
<style>
  li {
    list-style: none;
  }
</style>
<div class="space-y-6 p-4 sm:p-6 lg:p-8">
  <!-- Row 1: User Profile Card -->
  <div class="bg-white p-6 rounded shadow flex flex-col md:flex-row items-center">
    <div class="flex-shrink-0">
      <!-- Replace with actual tenant profile picture if available -->
      <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover">
    </div>
    <div class="mt-4 md:mt-0 md:ml-6">
      <h2 id="profileUsername" class="text-2xl font-bold text-gray-800"></h2>
      <p id="profileEmail" class="text-gray-600"></p>
      <p id="profilePhone" class="text-gray-600"></p>
    </div>
  </div>

  <!-- Row 2: Reminders Card -->
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Reminders</h3>
    <ul class="list-disc list-inside space-y-2 text-gray-700">
      <li>⚠️ Pay Maintenance Fee for the <?php echo date('F'); ?> Month</li>
      <li>⚠️ Check your Gas and Eb bill for the <?php echo date('F'); ?> Month</li>
    </ul>
  </div>

  <!-- Row 3: Latest Activities (Placeholder) -->
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Latest Activities</h3>
    <p class="text-gray-600">[Additional content goes here]</p>
  </div>
</div>

<script>
  // Fetch tenant profile details via REST API
  async function loadTenantProfile(){
    try {
      const res = await fetch('api.php?action=getTenantProfile');
      const data = await res.json();
      if(data.status === 'success'){
        const profile = data.profile;
        document.getElementById('profileUsername').innerText = profile.tenant_name ? profile.tenant_name : profile.username;
        document.getElementById('profileEmail').innerText = "Email: " + profile.email;
        document.getElementById('profilePhone').innerText = "Phone: " + profile.phone;
      } else {
        console.error("Error loading profile:", data.message);
      }
    } catch(error){
      console.error("Error fetching tenant profile:", error);
    }
  }
  
  window.onload = function(){
    loadTenantProfile();
  }
</script>

<?php include 'tenant_footer.php'; ?>
