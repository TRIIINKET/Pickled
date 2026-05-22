<?php
$pageTitle = 'Manage Events';
$activePage = 'events';
require_once __DIR__ . '/includes/_header.php';
require_once __DIR__ . '/../backend/services/AdminService.php';

$adminService = new AdminService();
$statusFilter = $_GET['status'] ?? 'all';
$eventId = $_GET['id'] ?? null;
$successMsg = '';
$errorMsg = '';

// Handle event actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_event') {
            $eventId = $adminService->createEvent(
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['event_date'] ?? '',
                $_POST['event_time'] ?? '',
                $_POST['location'] ?? '',
                (int) ($_POST['max_participants'] ?? 0),
                $_SESSION['user']['id']
            );
            if ($eventId) {
                $successMsg = 'Event created successfully';
            } else {
                $errorMsg = 'Failed to create event';
            }
        } elseif ($action === 'update_event') {
            $id = (int) ($_POST['event_id'] ?? 0);
            if ($adminService->updateEvent(
                $id,
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['event_date'] ?? '',
                $_POST['event_time'] ?? '',
                $_POST['location'] ?? '',
                (int) ($_POST['max_participants'] ?? 0),
                $_POST['status'] ?? 'upcoming',
                $_SESSION['user']['id']
            )) {
                $successMsg = 'Event updated successfully';
            } else {
                $errorMsg = 'Failed to update event';
            }
        } elseif ($action === 'delete_event') {
            $id = (int) ($_POST['event_id'] ?? 0);
            if ($adminService->deleteEvent($id, $_SESSION['user']['id'])) {
                $successMsg = 'Event deleted successfully';
                $eventId = null;
            } else {
                $errorMsg = 'Failed to delete event';
            }
        }
    }
}

// Get events based on filter
$events = [];
if ($statusFilter === 'all') {
    $events = $adminService->getAllEvents(50, 0);
} else {
    $events = $adminService->getEventsByStatus($statusFilter);
}

$currentEvent = null;
if ($eventId) {
    $currentEvent = $adminService->getEventDetail((int) $eventId);
}
?>

<?php require_once __DIR__ . '/includes/_navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Events</h1>
            <p class="admin-subtitle">Create, edit, and manage events</p>
        </div>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-group">
            <a href="?status=all" class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="?status=upcoming" class="btn <?php echo $statusFilter === 'upcoming' ? 'btn-primary' : 'btn-secondary'; ?>">Upcoming</a>
            <a href="?status=past" class="btn <?php echo $statusFilter === 'past' ? 'btn-primary' : 'btn-secondary'; ?>">Past</a>
            <a href="?status=cancelled" class="btn <?php echo $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">Cancelled</a>
        </div>
        
        <div class="grid-2">
            <!-- Events List -->
            <section>
                <h2>Events List</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Participants</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo $event['current_participants'] ?? 0; ?>/<?php echo $event['max_participants'] ?? 0; ?></td>
                                    <td><span class="badge badge-<?php echo htmlspecialchars($event['status']); ?>"><?php echo htmlspecialchars($event['status']); ?></span></td>
                                    <td>
                                        <a href="?id=<?php echo $event['id']; ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Event Detail / Create -->
            <section>
                <?php if ($currentEvent): ?>
                    <h2>Event Details</h2>
                    <form method="POST" class="form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $currentEvent['id']; ?>">
                        <input type="hidden" name="action" value="update_event">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($currentEvent['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($currentEvent['description']); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_date">Date</label>
                                <input type="date" id="event_date" name="event_date" value="<?php echo htmlspecialchars($currentEvent['event_date']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_time">Time</label>
                                <input type="time" id="event_time" name="event_time" value="<?php echo htmlspecialchars($currentEvent['event_time']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($currentEvent['location']); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_participants">Max Participants</label>
                                <input type="number" id="max_participants" name="max_participants" value="<?php echo $currentEvent['max_participants']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="upcoming" <?php echo $currentEvent['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="past" <?php echo $currentEvent['status'] === 'past' ? 'selected' : ''; ?>>Past</option>
                                    <option value="cancelled" <?php echo $currentEvent['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Update Event</button>
                            <button type="button" class="btn btn-secondary" onclick="window.location='manage-events.php'">Back</button>
                        </div>
                    </form>
                    
                    <form method="POST" class="delete-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $currentEvent['id']; ?>">
                        <input type="hidden" name="action" value="delete_event">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete Event</button>
                    </form>
                <?php else: ?>
                    <h2>Create New Event</h2>
                    <form method="POST" class="form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                        <input type="hidden" name="action" value="create_event">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_date">Date</label>
                                <input type="date" id="event_date" name="event_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_time">Time</label>
                                <input type="time" id="event_time" name="event_time">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_participants">Max Participants</label>
                            <input type="number" id="max_participants" name="max_participants">
                        </div>
                        
                        <button type="submit" class="btn btn-success">Create Event</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>
