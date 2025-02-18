<?php
$pageTitle = "Tenant Profile - TOWNMENT";
include 'tenant_header.php';

$stmt = $pdo->prepare("SELECT photo_path FROM tenant_photos WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$photoRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine which photo URL to use: use the one from the database if available,
// otherwise fall back to the default photo.
$profile_photo = ($photoRecord && !empty($photoRecord['photo_path']))
    ? $photoRecord['photo_path']
    : 'Assets/Default Profile picture.png';
?>
<div class="p-4 sm:p-6 lg:p-8">
  <div class="flex flex-col md:flex-row gap-6">
    <!-- Left Column: Profile Image & Tenant Additional Fields -->
    <div class="w-full md:w-1/2 space-y-6">
      <!-- Profile Image Card -->
      <div class="bg-[white] p-6 rounded-xl shadow">
  <div class="flex flex-col items-center">
    <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover">
    <a href="#" id="updatePhotoLink" class="mt-3 text-[#B82132] hover:underline" onclick="toggleUpdatePhotoForm(); return false;">
      Update Photo
    </a>
    <!-- Update Photo Form (initially hidden) -->
    <form id="updatePhotoForm" class="mt-3 hidden" enctype="multipart/form-data" method="POST">
  <div class="flex items-center space-x-2">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
    </svg>
    <input type="file" name="photo" id="profile_photo_input" accept="image/*" class="mb-2">
  </div>
  <button type="submit" class="bg-[#B82132] text-white px-4 py-2 rounded mt-2">Upload</button>
</form>

  </div>
</div>

      <!-- Tenant Additional Fields Card -->
      <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-xl font-bold text-[#B82132] mb-4">Tenant Details</h2>
        <div id="tenantFieldsContainer">
          <!-- Tenant fields will be loaded here via API -->
          <p class="text-gray-600">
Loading tenant details...</p>
        </div>
      </div>
    </div>
    <!-- Right Column: Account Information & Update Password -->
    <div class="w-full md:w-1/2 space-y-6">
      <!-- Account Info Card -->
      <div class="bg-white p-6 rounded-xl shadow">
        <h2  class="text-xl font-bold text-[#B82132] mb-4">Account Information</h2>
        <p  id="profileUsername" class="text-gray-700"></p>
        <p id="profileEmail" class="text-gray-700"></p>
        <p id="profilePhone" class="text-gray-700"></p>
      </div>
      <!-- Update Password Card -->
      <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-xl font-bold text-[#B82132] mb-4">Update Password</h2>
        <form id="updatePasswordForm" class="space-y-4">
          <div>
            <label class="block text-gray-700">Old Password</label>
            <input type="password" name="old_password" required class="w-full border border-gray-300 p-2 rounded-2xl">
          </div>
          <div>
            <label class="block text-gray-700">New Password</label>
            <input type="password" name="new_password" required class="w-full border border-gray-300 p-2 rounded-2xl">
          </div>
          <div>
            <label class="block text-gray-700">Confirm New Password</label>
            <input type="password" name="confirm_password" required class="w-full border border-gray-300 p-2 rounded-2xl">
          </div>
          <button type="submit" class="w-full bg-[#B82132] hover:bg-[#8E1616] text-white p-2 rounded-full">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle the update photo form visibility
function toggleUpdatePhotoForm() {
  document.getElementById('updatePhotoForm').classList.toggle('hidden');
}
// Fetch tenant profile details via REST API
async function loadTenantProfile(){
    try {
      const res = await fetch('api.php?action=getTenantProfile');
      const data = await res.json();
      if(data.status === 'success'){
        const profile = data.profile;
        document.getElementById('profileUsername').innerText = "Username: " + profile.username;
        document.getElementById('profileEmail').innerText = "Email: " + profile.email;
        document.getElementById('profilePhone').innerText = "Phone: " + profile.phone;
      } else {
        console.error("Error loading profile:", data.message);
      }
    } catch(error){
      console.error("Error fetching tenant profile:", error);
    }
  }
// Fetch tenant additional fields via API
async function loadTenantFields() {
  try {
    const res = await fetch('api.php?action=getTenantFields');
    const data = await res.json();
    if (data.status === "success" && data.fields) {
      const fields = data.fields;
      document.getElementById('tenantFieldsContainer').innerHTML = `
        <p><strong>Door Number:</strong> ${fields.door_number || 'N/A'}</p>
        <p><strong>Floor:</strong> ${fields.floor || 'N/A'}</p>
        <p><strong>Block:</strong> ${fields.block || 'N/A'}</p>
        <p><strong>Tenant/Lease Name:</strong> ${fields.tenant_name || 'N/A'}</p>
        <p><strong>Configuration:</strong> ${fields.configuration || 'N/A'}</p>
        <p><strong>Maintenance:</strong> ${fields.maintenance_cost || 'N/A'}</p>
      `;
    } else {
      document.getElementById('tenantFieldsContainer').innerHTML = `<p class="text-gray-600">No tenant details found.</p>`;
    }
  } catch (error) {
    console.error("Error fetching tenant fields:", error);
    document.getElementById('tenantFieldsContainer').innerHTML = `<p class="text-red-500">Error loading tenant details.</p>`;
  }
}

// Handle update photo form submission
document.getElementById('updatePhotoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('api.php?action=uploadPhoto', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === "success") {
        alert("Photo updated successfully!");
        // Update the image source to the new photo without reloading the page:
        document.querySelector('img[alt="Profile Picture"]').src = data.photo_url + '?' + new Date().getTime();
      } else {
        alert("Photo update failed: " + data.message);
      }
    } catch (error) {
      console.error("Error uploading photo:", error);
      alert("Error uploading photo.");
    }
  });

// Handle update password form submission
document.getElementById('updatePasswordForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  if (formData.get('new_password') !== formData.get('confirm_password')) {
    alert("New password and confirm password do not match.");
    return;
  }
  const payload = {
    old_password: formData.get('old_password'),
    new_password: formData.get('new_password')
  };
  try {
    const res = await fetch('api.php?action=updatePassword', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.status === "success") {
      alert("Password updated successfully!");
    } else {
      alert("Password update failed: " + data.message);
    }
  } catch (error) {
    console.error("Error updating password:", error);
    alert("Error updating password.");
  }
});

// Update live date & time in the top bar
function updateDateTime() {
  document.getElementById('dateTime').innerText = new Date().toLocaleString();
}
setInterval(updateDateTime, 1000);
updateDateTime();

// On page load, fetch tenant additional fields via API
window.addEventListener('load', function() {
  loadTenantFields();
  loadTenantProfile();
});

</script>

<?php include 'tenant_footer.php'; ?>
