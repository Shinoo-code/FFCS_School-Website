<?php
// manage_activities.php - REVAMPED with Status and Calendar
include 'api/db_connect.php'; // Establishes $pdo

// --- Fetch Categories (for Add form dropdown) ---
$categories_for_form = [];
try {
    $categories_stmt = $pdo->query("SELECT slug, name FROM categories ORDER BY name ASC");
    $categories_for_form = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for form: " . $e->getMessage());
    // Handle error display if necessary
}

// --- Fetch Activities (with new date columns) ---
$activities = [];
$calendar_events = []; // For FullCalendar
$today = date('Y-m-d'); // Get today's date for status calculation

try {
    // Fetch start_date and end_date
    $activities_stmt = $pdo->query("SELECT a.id, a.title, a.description, a.start_date, a.end_date, a.category, a.image_path, c.name as category_name
                                   FROM activities a
                                   LEFT JOIN categories c ON a.category = c.slug
                                   ORDER BY a.start_date DESC, a.id DESC");
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Prepare events for FullCalendar ---
    foreach ($activities as $activity) {
        if (!empty($activity['start_date'])) {
            $event_end_date = $activity['end_date'];
            // FullCalendar's event 'end' is exclusive. Add 1 day if end_date exists.
            if ($event_end_date) {
                try {
                    $endDateObj = new DateTime($event_end_date);
                    $endDateObj->modify('+1 day');
                    $event_end_date = $endDateObj->format('Y-m-d');
                } catch (Exception $e) {
                    $event_end_date = null; // Handle invalid date format if necessary
                }
            }

            $calendar_events[] = [
                'title' => $activity['title'],
                'start' => $activity['start_date'],
                'end' => $event_end_date, // Use adjusted end date or null
                 // Optional: Add more data if needed for clicks, etc.
                'id' => $activity['id'],
                'allDay' => true // Assume all-day events
            ];
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching activities: " . $e->getMessage());
    // Handle error display if necessary
}

// --- Feedback Message Handling ---
$success_message = '';
$error_message = ''; // Added for potential errors
if (isset($_GET['success_msg'])) { // Use a unique parameter
    $success_message = htmlspecialchars($_GET['success_msg']);
}
if (isset($_GET['error_msg'])) { // Use a unique parameter
    $error_message = htmlspecialchars($_GET['error_msg']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Activities - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
  <link rel="stylesheet" href="css/manage_activities.css">
</head>
<body>

<div class="page-container">
  <h2 class="text-center">Manage Activities</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= $success_message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
      <?= $error_message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="add-activity-card">
      <form action="add_activity.php" method="post" enctype="multipart/form-data">
        <h3 class="form-section-title">Add Activity</h3>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="title" class="form-label">Title *</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="Activity Title" required>
          </div>
           <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date *</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
           </div>
           <div class="col-md-3">
                <label for="end_date" class="form-label">End Date (Optional)</label>
                <input type="date" class="form-control" id="end_date" name="end_date">
                <small class="form-text text-muted">Leave blank for single-day events.</small>
           </div>
          <div class="col-md-12">
            <label for="description" class="form-label">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Activity Description" required></textarea>
          </div>
          <div class="col-md-6">
            <label for="category" class="form-label">Category *</label>
            <select class="form-select" id="category" name="category" required>
              <option value="">Select Category...</option>
              <?php foreach ($categories_for_form as $cat): ?>
                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="image" class="form-label">Image *</label>
            <input type="file" class="form-control" id="image" name="image" required>
          </div>
        </div>
        <div class="btn-container mt-3 text-center">
          <button type="submit" class="btn btn-primary form-submit-btn"><i class="fas fa-plus-circle"></i> Add</button>
        </div>
      </form>
  </div>


  <hr class="my-4">

  <h3 class="form-section-title">Existing Activities</h3>
  <?php if (empty($activities)): ?>
    <p class="text-center text-muted">No activities found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Image</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Category</th>
            <th>Title</th>
            <th class="description-cell">Description</th>
            <th>Status</th>
            <th class="actions-cell">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activities as $activity): ?>
            <?php
                // Determine Status
                $status = 'End'; // Default to End
                $start = $activity['start_date'];
                $end = $activity['end_date']; // Could be null

                if ($start && $start <= $today) {
                    if (!$end || $end >= $today) {
                        $status = 'Ongoing';
                    }
                } elseif ($start && $start > $today) {
                     $status = 'Upcoming'; // Optional: Add an upcoming status
                }

                $statusClass = ($status === 'Ongoing' || $status === 'Upcoming') ? 'status-ongoing' : 'status-end';

                // Format dates for display
                $startDateFormatted = $start ? date('M j, Y', strtotime($start)) : 'N/A';
                $endDateFormatted = $end ? date('M j, Y', strtotime($end)) : ($start ? $startDateFormatted : 'N/A'); // If no end date, use start date
                if ($start && $end && $start === $end) {
                    $endDateFormatted = ''; // Don't show end date if it's same as start
                } elseif (!$end && $start) {
                     $endDateFormatted = ''; // Don't show end date if it's null (single day event)
                }

            ?>
            <tr>
              <td>
                <?php if (!empty($activity['image_path'])): ?>
                  <img src="uploads/activities/<?= htmlspecialchars($activity['image_path']) ?>" alt="<?= htmlspecialchars($activity['title']) ?>" class="image-preview">
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($startDateFormatted) ?></td>
              <td><?= htmlspecialchars($endDateFormatted) ?></td>
              <td><?= htmlspecialchars($activity['category_name'] ?? $activity['category'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($activity['title']) ?></td>
              <td class="description-cell"><?= nl2br(htmlspecialchars($activity['description'])) ?></td>
              <td class="<?= $statusClass ?>"><?= htmlspecialchars($status) ?></td>
              <td class="actions-cell">
                <div class="action-buttons-group">
                  <a href="edit_activity.php?id=<?= $activity['id'] ?>" class="btn btn-sm btn-edit" title="Edit Activity">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form action="delete_activity.php" method="post" onsubmit="return confirm('Are you sure?');">
                    <input type="hidden" name="id" value="<?= $activity['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-delete" title="Delete Activity">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <hr class="my-4">
  <div id="calendar-container">
      <h3 class="form-section-title">Activities Calendar</h3>
      <p class="text-muted">Shows start and end dates of activities. Overlapping events might appear stacked or side-by-side.</p>
      <div id='calendar'></div>
  </div>


  <div class="back-container mt-4">
    <a href="dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
  // --- Alert Hiding ---
  const alertElements = document.querySelectorAll('.alert-dismissible');
  alertElements.forEach(alertEl => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
      if (bsAlert) bsAlert.close();
    }, 5000); // Hide after 5 seconds
  });

  // --- FullCalendar Initialization ---
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    // Get events data from PHP
    const activityEvents = <?php echo json_encode($calendar_events); ?>;

    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Start with month view
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek' // Add week and list views
            },
            events: activityEvents,
            editable: false, // Don't allow dragging/resizing from this view
            selectable: false, // Don't allow selecting date ranges
            eventDisplay: 'block', // How events are displayed ('auto', 'block', 'list-item', 'background')
            eventColor: '#007bff', // Default color for events (Bootstrap primary blue)
            eventTextColor: '#ffffff', // White text on events

            // Optional: Handle event clicks if you want to link back or show details
            /*
            eventClick: function(info) {
                console.log('Event clicked:', info.event);
                // Example: Redirect to edit page
                // window.location.href = `edit_activity.php?id=${info.event.id}`;
                alert(`Activity: ${info.event.title}\nStart: ${info.event.start.toLocaleDateString()}`);
                info.jsEvent.preventDefault(); // Prevent browser from following link (if any)
            }
            */
        });

        calendar.render();
        console.log("FullCalendar rendered.");
    } else {
        console.error("Calendar element not found!");
    }
  });

</script>
</body>
</html>