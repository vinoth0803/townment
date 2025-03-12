<?php
$pageTitle = "Tickets - TOWNMENT";
include 'admin_header.php';
?>
<div class="container mx-auto px-4 py-6 bg-gray-50 min-h-screen">
  <h1 class="text-3xl font-semibold text-gray-800 mb-6">Tickets</h1>
  <!-- Responsive table wrapper -->
  <div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
        <div id="ticketsTable"></div>
      </div>
    </div>
  </div>
</div>
<script>
  // Toggle the detailed view for a ticket on all devices
  function toggleTicketDetails(ticketId) {
    const detailRow = document.getElementById('ticket-details-' + ticketId);
    detailRow.classList.toggle('hidden');
  }

  async function loadTickets(){
    try {
      const res = await fetch('api.php?action=getallTickets');
      const data = await res.json();
      let tickets = data.tickets;
      let html = '';
      if(tickets && tickets.length > 0){
        html += `
        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200" style="min-width: 800px;">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tenant Username
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Raised Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Issue
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Action
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
        `;
        tickets.forEach(ticket => {
          let badgeClasses = "";
          if(ticket.status === 'opened'){
            badgeClasses = "bg-green-100 text-green-800";
          } else if(ticket.status === 'inprogress'){
            badgeClasses = "bg-yellow-100 text-yellow-800";
          } else if(ticket.status === 'closed'){
            badgeClasses = "bg-red-100 text-red-800";
          }
          html += `
              <tr class="hover:bg-gray-100 cursor-pointer" onclick="toggleTicketDetails(${ticket.id})">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ticket.username}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${ticket.raised_date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ticket.issue}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClasses}">
                    ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select onchange="updateTicketStatus(${ticket.id}, this.value)" class="mt-1 block w-full sm:w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="opened" ${ticket.status==='opened'?'selected':''}>Opened</option>
                    <option value="inprogress" ${ticket.status==='inprogress'?'selected':''}>In Progress</option>
                    <option value="closed" ${ticket.status==='closed'?'selected':''}>Closed</option>
                  </select>
                </td>
              </tr>
              <tr id="ticket-details-${ticket.id}" class="hidden">
                <td colspan="5" class="px-6 py-4">
                  <div class="p-4 bg-gray-100 rounded-lg shadow-md">
                    <p class="font-bold">Issue: ${ticket.issue}</p>
                    <p class="mt-2">Description: ${ticket.issue_description}</p>
                    <p class="mt-2 text-sm text-gray-600">Raised on: ${ticket.raised_date}</p>
                  </div>
                </td>
              </tr>
          `;
        });
        html += `
            </tbody>
          </table>
        </div>`;
      } else {
        html = `<p class="text-gray-600 text-center">No tickets raised.</p>`;
      }
      document.getElementById('ticketsTable').innerHTML = html;
    } catch(error) {
      console.error('Error loading tickets:', error);
      document.getElementById('ticketsTable').innerHTML = `<p class="text-gray-600 text-center">Error loading tickets.</p>`;
    }
  }

  async function updateTicketStatus(ticketId, status){
    try {
      const res = await fetch('api.php?action=updateTicketStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId, status: status })
      });
      const data = await res.json();
      alert(data.message);
      loadTickets();
    } catch(error) {
      console.error('Error updating ticket status:', error);
    }
  }

  window.onload = loadTickets;
</script>
<?php include 'admin_footer.php'; ?>
