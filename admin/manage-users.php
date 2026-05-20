<?php
$pageTitle = 'Manage Users';
$activePage = 'users';
require_once __DIR__ . '/includes/_header.php';
require_once __DIR__ . '/../backend/services/AdminService.php';
require_once __DIR__ . '/../backend/repositories/UserRepository.php';

$adminService = new AdminService();
$userRepo = new UserRepository();
$roleFilter = $_GET['role'] ?? 'all';
$userId = $_GET['id'] ?? null;
$successMsg = '';
$errorMsg = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['user_id'] ?? 0);
        
        if ($action === 'update_role' && $id && $id !== $_SESSION['user']['id']) {
            $role = $_POST['role'] ?? '';
            if ($adminService->updateUserRole($id, $role, $_SESSION['user']['id'])) {
                $successMsg = 'User role updated successfully';
                $userId = null;
            } else {
                $errorMsg = 'Failed to update user role';
            }
        } elseif ($action === 'delete_user' && $id && $id !== $_SESSION['user']['id']) {
            if ($adminService->deleteUser($id, $_SESSION['user']['id'])) {
                $successMsg = 'User deleted successfully';
                $userId = null;
            } else {
                $errorMsg = 'Failed to delete user';
            }
        }
    }
}

// Get users based on filter
$users = [];
if ($roleFilter === 'all') {
    $users = $userRepo->findAll();
} else {
    $users = $userRepo->findByRole($roleFilter);
}

$currentUser = null;
if ($userId) {
    $currentUser = $userRepo->findById((int) $userId);
}
?>

<?php require_once __DIR__ . '/includes/_navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Users</h1>
            <p class="admin-subtitle">View and manage user accounts and roles</p>
        </div>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-group">
            <a href="?role=all" class="btn <?php echo $roleFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="?role=player" class="btn <?php echo $roleFilter === 'player' ? 'btn-primary' : 'btn-secondary'; ?>">Players</a>
            <a href="?role=coach" class="btn <?php echo $roleFilter === 'coach' ? 'btn-primary' : 'btn-secondary'; ?>">Coaches</a>
            <a href="?role=admin" class="btn <?php echo $roleFilter === 'admin' ? 'btn-primary' : 'btn-secondary'; ?>">Admins</a>
        </div>
        
        <div class="grid-2">
            <!-- Users List -->
            <section>
                <h2>Users List</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge badge-role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="?id=<?php echo $user['id']; ?>&role=<?php echo urlencode($roleFilter); ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- User Detail -->
            <?php if ($currentUser): ?>
                <section>
                    <h2>User Details</h2>
                    <div class="detail-box">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                        <p><strong>Current Role:</strong> <span class="badge badge-role-<?php echo htmlspecialchars($currentUser['role']); ?>"><?php echo htmlspecialchars($currentUser['role']); ?></span></p>
                        <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($currentUser['created_at'])); ?></p>
                        
                        <?php if ($currentUser['id'] !== $_SESSION['user']['id']): ?>
                            <form method="POST" class="form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                                <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="role">Change Role</label>
                                    <select id="role" name="role" required>
                                        <option value="player" <?php echo $currentUser['role'] === 'player' ? 'selected' : ''; ?>>Player</option>
                                        <option value="coach" <?php echo $currentUser['role'] === 'coach' ? 'selected' : ''; ?>>Coach</option>
                                        <option value="admin" <?php echo $currentUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <button type="submit" name="action" value="update_role" class="btn btn-success">Update Role</button>
                            </form>
                            
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                                <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">
                                <button type="submit" name="action" value="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete User</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">You cannot edit your own account</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>
