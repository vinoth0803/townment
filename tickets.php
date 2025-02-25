<?php
$pageTitle = "Tickets - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto bg-gray-50 min-h-screen">
  <h1 class="text-3xl font-semibold text-gray-800 mb-6">Tickets</h1>
  <div id="ticketsTable" class="overflow-x-auto"></div>
</div>
<script>
  async function loadTickets(){
    const res = await fetch('api.php?action=getallTickets');
    const data = await res.json();
    let tickets = data.tickets;
    let html = '';
    if(tickets && tickets.length > 0){
      html += `
      <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tenant Username
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Raised Date
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Issue
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Action
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">`;
      tickets.forEach(ticket => {
        // Determine the badge classes based on status
        let badgeClasses = "";
        if(ticket.status === 'opened'){
          badgeClasses = "bg-green-100 text-green-800";
        } else if(ticket.status === 'inprogress'){
          badgeClasses = "bg-yellow-100 text-yellow-800";
        } else if(ticket.status === 'closed'){
          badgeClasses = "bg-red-100 text-red-800";
        }
        html += `
            <tr>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${ticket.username}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">${ticket.raised_date}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${ticket.issue}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClasses}">
                  ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select onchange="updateTicketStatus(${ticket.id}, this.value)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                  <option value="opened" ${ticket.status==='opened'?'selected':''}>Opened</option>
                  <option value="inprogress" ${ticket.status==='inprogress'?'selected':''}>In Progress</option>
                  <option value="closed" ${ticket.status==='closed'?'selected':''}>Closed</option>
                </select>
              </td>
            </tr>`;
      });
      html += `
          </tbody>
        </table>
      </div>`;
    } else {
      html = `<p class="text-gray-600 text-center">No tickets raised.</p>`;
    }
    document.getElementById('ticketsTable').innerHTML = html;
  }

  async function updateTicketStatus(ticketId, status){
    const res = await fetch('api.php?action=updateTicketStatus', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ticket_id: ticketId, status: status })
    });
    const data = await res.json();
    alert(data.message);
    loadTickets();
  }

  window.onload = loadTickets;
</script>
<?php include 'admin_footer.php'; ?>
