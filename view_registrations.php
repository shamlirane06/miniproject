<?php
session_start();
include "db.php";

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

// Fetch all events created by this admin
$events_result = $conn->prepare("SELECT * FROM events WHERE admin_id = ? ORDER BY event_date ASC");
$events_result->bind_param("i", $admin_id);
$events_result->execute();
$events = $events_result->get_result();

// Store events in array for JavaScript filtering
$all_events = [];
while($row = $events->fetch_assoc()){
    $all_events[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Events - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        .event-card {
            transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
            backdrop-filter: blur(2px);
        }
        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -12px rgba(79, 70, 229, 0.25);
            border-color: #c7d2fe;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .search-input {
            transition: all 0.2s;
        }
        .search-input:focus {
            transform: scale(1.01);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">

<nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-indigo-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-cyan-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-white text-sm"></i>
                </div>
                <span class="font-bold text-xl bg-gradient-to-r from-indigo-700 to-cyan-600 bg-clip-text text-transparent">EventHub</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php" class="text-slate-600 hover:text-indigo-600 transition flex items-center gap-1">
                    <i class="fas fa-tachometer-alt"></i> <span class="hidden sm:inline">Dashboard</span>
                </a>
                <a href="add_event.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-full text-sm font-medium transition shadow-sm">
                    <i class="fas fa-plus-circle mr-1"></i> New Event
                </a>
                <a href="logout.php" class="text-red-500 hover:text-red-700 transition flex items-center gap-1">
                    <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header with search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">📋 My Events</h1>
            <p class="text-slate-500 text-sm mt-1">Manage your created events and view registrations</p>
        </div>
        <div class="relative w-full md:w-72">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="searchInput" placeholder="Search events by title..." 
                   class="search-input w-full pl-9 pr-4 py-2 border border-slate-200 rounded-full bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 text-sm">
        </div>
    </div>

    <!-- Events Grid -->
    <div id="eventsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Cards will be injected via JavaScript -->
    </div>
    
    <!-- No results message -->
    <div id="noResults" class="hidden text-center py-16 bg-white/40 rounded-2xl mt-6">
        <i class="fas fa-calendar-times text-5xl text-slate-300 mb-3"></i>
        <p class="text-slate-500">No events match your search.</p>
    </div>
</div>

<script>
    // Pass PHP events array to JavaScript
    const events = <?php echo json_encode($all_events); ?>;

    function getEventStatus(eventDate) {
        const today = new Date();
        today.setHours(0,0,0,0);
        const evDate = new Date(eventDate);
        evDate.setHours(0,0,0,0);
        if (evDate > today) return { label: "Upcoming", color: "bg-emerald-100 text-emerald-700", icon: "fa-clock" };
        if (evDate.getTime() === today.getTime()) return { label: "Today", color: "bg-amber-100 text-amber-700", icon: "fa-hourglass-half" };
        return { label: "Past", color: "bg-slate-100 text-slate-500", icon: "fa-calendar-check" };
    }

    function renderEvents(filterText = "") {
        const grid = document.getElementById('eventsGrid');
        const noResults = document.getElementById('noResults');
        const filtered = events.filter(ev => ev.title.toLowerCase().includes(filterText.toLowerCase()));
        
        if (filtered.length === 0) {
            grid.innerHTML = '';
            noResults.classList.remove('hidden');
            return;
        }
        noResults.classList.add('hidden');
        
        let html = '';
        filtered.forEach(ev => {
            const status = getEventStatus(ev.event_date);
            const formattedDate = new Date(ev.event_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            html += `
                <div class="event-card bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden shadow-sm border border-slate-100 hover:border-indigo-200 transition-all">
                    <div class="relative">
                        <div class="absolute top-3 right-3 z-10">
                            <span class="status-badge ${status.color}"><i class="fas ${status.icon} text-xs"></i> ${status.label}</span>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-500 to-cyan-500 px-5 pt-6 pb-4">
                            <h3 class="text-white font-bold text-xl leading-tight pr-20">${escapeHtml(ev.title)}</h3>
                        </div>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex items-center text-slate-600 text-sm">
                            <i class="fas fa-calendar-alt w-5 text-indigo-400"></i>
                            <span>${formattedDate}</span>
                        </div>
                        <div class="flex items-center text-slate-600 text-sm">
                            <i class="fas fa-map-marker-alt w-5 text-indigo-400"></i>
                            <span class="truncate">${escapeHtml(ev.venue)}</span>
                        </div>
                        ${ev.is_paid == 1 ? `<div class="flex items-center text-slate-600 text-sm"><i class="fas fa-tag w-5 text-indigo-400"></i><span class="font-medium text-amber-600">Paid Event</span></div>` : ''}
                        <a href="event_registrations.php?event_id=${ev.event_id}" 
                           class="mt-4 block text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-2.5 rounded-xl transition text-sm">
                            <i class="fas fa-users mr-1"></i> View Registrations
                        </a>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Initial render
    renderEvents();
    
    // Search listener
    document.getElementById('searchInput').addEventListener('input', function(e) {
        renderEvents(e.target.value);
    });
</script>

<?php if($events->num_rows === 0 && empty($all_events)): ?>
<script>
    // If no events at all, show empty state differently
    document.getElementById('eventsGrid').innerHTML = '';
    document.getElementById('noResults').classList.remove('hidden');
    document.getElementById('noResults').innerHTML = `
        <div class="text-center py-16">
            <i class="fas fa-calendar-times text-5xl text-slate-300 mb-3"></i>
            <p class="text-slate-500 mb-4">You haven't created any events yet.</p>
            <a href="add_event.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-full transition shadow">Create Your First Event</a>
        </div>
    `;
</script>
<?php endif; ?>

</body>
</html>