<?php
$pageTitle = "Tenant Profile - TOWNMENT";
include 'tenant_header.php';

// Use the profile photo from the session or fall back to default
$profile_photo = isset($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['profile_photo'])
    ? $_SESSION['user']['profile_photo']
    : 'Assets/Default Profile picture.png';
?>
<div class="p-4 sm:p-6 lg:p-8">
  <div class="flex flex-col md:flex-row gap-6">
    <!-- Left Column: Profile Image & Tenant Additional Fields -->
    <div class="w-full md:w-1/2 space-y-6">
      <!-- Profile Image Card -->
      <div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col items-center">
    <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover">
    <a href="#" id="updatePhotoLink" class="mt-3 text-[#B82132] hover:underline" onclick="toggleUpdatePhotoForm(); return false;">
      Update Photo
    </a>
    <!-- Update Photo Form (initially hidden) -->
    <form id="updatePhotoForm" class="mt-3 hidden" enctype="multipart/form-data" method="POST">
      <input type="file" name="photo" id="profile_photo_input" accept="image/*" class="mb-2">
      <button type="submit" class="bg-[#B82132] text-white px-4 py-2 rounded">Upload</button>
    </form>
  </div>
</div>

      <!-- Tenant Additional Fields Card -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Tenant Details</h2>
        <div id="tenantFieldsContainer">
          <!-- Tenant fields will be loaded here via API -->
          <p class="text-gray-600">Loading tenant details...</p>
        </div>
      </div>
    </div>
    <!-- Right Column: Account Information & Update Password -->
    <div class="w-full md:w-1/2 space-y-6">
      <!-- Account Info Card -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Account Information</h2>
        <p class="text-gray-700"><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'N/A'); ?></p>
        <p class="text-gray-700"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'N/A'); ?></p>
        <p class="text-gray-700"><strong>Phone:</strong> <?php echo htmlspecialchars($_SESSION['user']['phone'] ?? 'N/A'); ?></p>
      </div>
      <!-- Update Password Card -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Update Password</h2>
        <form id="updatePasswordForm" class="space-y-4">
          <div>
            <label class="block text-gray-700">Old Password</label>
            <input type="password" name="old_password" required class="w-full border border-gray-300 p-2 rounded">
          </div>
          <div>
            <label class="block text-gray-700">New Password</label>
            <input type="password" name="new_password" required class="w-full border border-gray-300 p-2 rounded">
          </div>
          <div>
            <label class="block text-gray-700">Confirm New Password</label>
            <input type="password" name="confirm_password" required class="w-full border border-gray-300 p-2 rounded">
          </div>
          <button type="submit" class="w-full bg-[#B82132] text-white p-2 rounded">Update Password</button>
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
window.addEventListener('load', loadTenantFields);
</script>

<?php include 'tenant_footer.php'; ?>
