<?php
$pageTitle = "Tickets - TOWNMENT";
include 'admin_header.php';
?>
<div class="flex-1 p-6 overflow-auto">
  <h1 class="text-2xl font-bold mb-4">Tickets</h1>
  <div id="ticketsTable"></div>
</div>
<script>
  async function loadTickets(){
    const res = await fetch('api.php?action=getTickets');
    const data = await res.json();
    let tickets = data.tickets;
    let html = '';
    if(tickets && tickets.length > 0){
      html += `<table class="min-w-full border">
                <thead>
                  <tr>
                    <th class="border p-2">Tenant Username</th>
                    <th class="border p-2">Raised Date</th>
                    <th class="border p-2">Issue</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Action</th>
                  </tr>
                </thead>
                <tbody>`;
      tickets.forEach(ticket => {
        html += `<tr>
                  <td class="border p-2">${ticket.username}</td>
                  <td class="border p-2">${ticket.raised_date}</td>
                  <td class="border p-2">${ticket.issue}</td>
                  <td class="border p-2">${ticket.status}</td>
                  <td class="border p-2">
                    <select onchange="updateTicketStatus(${ticket.id}, this.value)" class="border p-1 rounded">
                      <option value="opened" ${ticket.status==='opened'?'selected':''}>Opened</option>
                      <option value="inprogress" ${ticket.status==='inprogress'?'selected':''}>Inprogress</option>
                      <option value="closed" ${ticket.status==='closed'?'selected':''}>Closed</option>
                    </select>
                  </td>
                </tr>`;
      });
      html += `</tbody></table>`;
    } else {
      html = `<p>No tickets raised.</p>`;
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
