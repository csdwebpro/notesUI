<?php
// notion_notes_complete_fixed.php - Complete Fixed Notes App
session_start();

// Configuration
define('DATA_DIR', __DIR__ . '/data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('NOTES_FILE', DATA_DIR . 'notes.json');

// Create data directory if it doesn't exist
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialize data files if they don't exist
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode([]));
}
if (!file_exists(NOTES_FILE)) {
    file_put_contents(NOTES_FILE, json_encode([]));
}

// NoSQL Data Functions
function getUsers() {
    if (!file_exists(USERS_FILE)) return [];
    $data = file_get_contents(USERS_FILE);
    return json_decode($data, true) ?: [];
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function getNotes() {
    if (!file_exists(NOTES_FILE)) return [];
    $data = file_get_contents(NOTES_FILE);
    return json_decode($data, true) ?: [];
}

function saveNotes($notes) {
    file_put_contents(NOTES_FILE, json_encode($notes, JSON_PRETTY_PRINT));
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function login($email, $password) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            return true;
        }
    }
    return false;
}

function register($username, $email, $password) {
    $users = getUsers();
    
    // Check if email already exists
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return false;
        }
    }
    
    $newUser = [
        'id' => uniqid(),
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'email_notifications' => true
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    // Auto-login after registration
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['username'];
    $_SESSION['email'] = $newUser['email'];
    
    return true;
}

function logout() {
    session_destroy();
    header('Location: ?action=login');
    exit;
}

// Note functions
function getAllNotes($user_id) {
    $notes = getNotes();
    $userNotes = [];
    
    foreach ($notes as $note) {
        if ($note['user_id'] === $user_id && empty($note['deleted_at'])) {
            $userNotes[] = $note;
        }
    }
    
    // Sort by updated_at descending
    usort($userNotes, function($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });
    
    return $userNotes;
}

function getNoteById($id, $user_id) {
    $notes = getNotes();
    foreach ($notes as $note) {
        if ($note['id'] === $id && $note['user_id'] === $user_id && empty($note['deleted_at'])) {
            return $note;
        }
    }
    return null;
}

function createNote($user_id, $title, $content, $category = 'general') {
    $notes = getNotes();
    
    $newNote = [
        'id' => uniqid(),
        'user_id' => $user_id,
        'title' => $title,
        'content' => $content,
        'category' => $category,
        'is_favorite' => false,
        'is_pinned' => false,
        'color' => '#ffffff',
        'tags' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'deleted_at' => null
    ];
    
    $notes[] = $newNote;
    saveNotes($notes);
    
    return $newNote['id'];
}

function updateNote($id, $user_id, $title, $content, $category) {
    $notes = getNotes();
    
    foreach ($notes as &$note) {
        if ($note['id'] === $id && $note['user_id'] === $user_id) {
            $note['title'] = $title;
            $note['content'] = $content;
            $note['category'] = $category;
            $note['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveNotes($notes);
    return true;
}

function deleteNote($id, $user_id) {
    $notes = getNotes();
    
    foreach ($notes as &$note) {
        if ($note['id'] === $id && $note['user_id'] === $user_id) {
            $note['deleted_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveNotes($notes);
    return true;
}

function searchNotes($user_id, $query) {
    $notes = getAllNotes($user_id);
    $results = [];
    
    foreach ($notes as $note) {
        if (stripos($note['title'], $query) !== false || stripos($note['content'], $query) !== false) {
            $results[] = $note;
        }
    }
    
    return $results;
}

function toggleFavorite($id, $user_id) {
    $notes = getNotes();
    
    foreach ($notes as &$note) {
        if ($note['id'] === $id && $note['user_id'] === $user_id) {
            $note['is_favorite'] = !$note['is_favorite'];
            $note['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveNotes($notes);
    return true;
}

function togglePin($id, $user_id) {
    $notes = getNotes();
    
    foreach ($notes as &$note) {
        if ($note['id'] === $id && $note['user_id'] === $user_id) {
            $note['is_pinned'] = !$note['is_pinned'];
            $note['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveNotes($notes);
    return true;
}

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = $_GET['message'] ?? '';

// Create default user if no users exist
$users = getUsers();
if (empty($users)) {
    $defaultUser = [
        'id' => 'admin',
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'email_notifications' => true
    ];
    $users[] = $defaultUser;
    saveUsers($users);
    
    // Create welcome note
    createNote('admin', 'Welcome to NotionNotes!', 
        "## Welcome to Your Advanced Notes App! ✨\n\nThis is a sample note to get you started. You can:\n- 📝 Create new notes\n- 🏷️ Organize them by categories\n- 🔍 Search through your content\n- ⭐ Mark favorites\n- 📌 Pin important notes\n- 📱 Use on any device\n\n### Getting Started\n1. Create your first note\n2. Organize them using categories\n3. Use search to find notes quickly\n4. Customize your workspace",
        'general'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action'] ?? '') {
        case 'login':
            if (login($_POST['email'], $_POST['password'])) {
                header('Location: ?action=dashboard');
                exit;
            } else {
                $message = 'Invalid email or password.';
                $action = 'login';
            }
            break;
            
        case 'register':
            if (register($_POST['username'], $_POST['email'], $_POST['password'])) {
                header('Location: ?action=dashboard');
                exit;
            } else {
                $message = 'Registration failed. Email might already exist.';
                $action = 'register';
            }
            break;
            
        case 'create_note':
            if (isLoggedIn()) {
                if (createNote($_SESSION['user_id'], $_POST['title'], $_POST['content'], $_POST['category'])) {
                    $message = 'Note created successfully!';
                } else {
                    $message = 'Error creating note.';
                }
                header('Location: ?action=dashboard&message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'update_note':
            if (isLoggedIn()) {
                if (updateNote($_POST['note_id'], $_SESSION['user_id'], $_POST['title'], $_POST['content'], $_POST['category'])) {
                    $message = 'Note updated successfully!';
                } else {
                    $message = 'Error updating note.';
                }
                header('Location: ?action=dashboard&message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'delete_note':
            if (isLoggedIn()) {
                if (deleteNote($_POST['note_id'], $_SESSION['user_id'])) {
                    $message = 'Note deleted successfully!';
                } else {
                    $message = 'Error deleting note.';
                }
                header('Location: ?action=dashboard&message=' . urlencode($message));
                exit;
            }
            break;
    }
}

// Handle GET actions
if (isset($_GET['toggle_favorite'])) {
    if (isLoggedIn()) {
        toggleFavorite($_GET['toggle_favorite'], $_SESSION['user_id']);
        header('Location: ?action=dashboard');
        exit;
    }
}

if (isset($_GET['toggle_pin'])) {
    if (isLoggedIn()) {
        togglePin($_GET['toggle_pin'], $_SESSION['user_id']);
        header('Location: ?action=dashboard');
        exit;
    }
}

// Redirect to login if not authenticated
if (!isLoggedIn() && !in_array($action, ['login', 'register'])) {
    header('Location: ?action=login');
    exit;
}

// Display the appropriate page
switch ($action) {
    case 'login':
        displayLoginPage($message);
        break;
    case 'register':
        displayRegisterPage($message);
        break;
    case 'logout':
        logout();
        break;
    case 'dashboard':
        displayDashboard($message);
        break;
    case 'edit_note':
        displayEditNote($_GET['id'] ?? null);
        break;
    default:
        displayDashboard();
}

function displayLoginPage($message = '') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            padding: 40px; 
            width: 100%; 
            max-width: 400px;
        }
        
        .login-header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        
        .login-header i { 
            font-size: 48px; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px; 
        }
        
        .login-header h1 { 
            font-size: 24px; 
            font-weight: 700; 
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 500; 
            font-size: 14px; 
            color: #2d3748;
        }
        
        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px; 
            transition: all 0.3s ease;
        }
        
        .form-control:focus { 
            outline: none; 
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn { 
            width: 100%; 
            padding: 12px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 600; 
            transition: all 0.3s ease;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error { 
            background: #fed7d7;
            color: #c53030; 
            font-size: 14px; 
            margin-bottom: 20px; 
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #c53030;
        }
        
        .register-link { 
            text-align: center; 
            margin-top: 20px; 
            font-size: 14px; 
            color: #718096;
        }
        
        .register-link a { 
            color: #667eea; 
            text-decoration: none;
            font-weight: 600;
        }
        
        .demo-credentials { 
            background: #f0f9ff; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 20px; 
            font-size: 13px; 
            color: #666;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-sticky-note"></i>
            <h1>NotionNotes</h1>
            <p>Your Smart Note-Taking App</p>
        </div>
        
        <?php if ($message): ?>
            <div class="error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        
        <div class="demo-credentials">
            <strong>Demo Account:</strong><br>
            Email: admin@example.com<br>
            Password: admin123
        </div>
        
        <div class="register-link">
            Don't have an account? <a href="?action=register">Sign up here</a>
        </div>
    </div>
</body>
</html>
<?php
}

function displayRegisterPage($message = '') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 20px;
        }
        
        .register-container { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            padding: 40px; 
            width: 100%; 
            max-width: 400px;
        }
        
        .register-header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        
        .register-header i { 
            font-size: 48px; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px; 
        }
        
        .register-header h1 { 
            font-size: 24px; 
            font-weight: 700; 
            color: #2d3748;
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 500; 
            font-size: 14px; 
            color: #2d3748;
        }
        
        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px; 
            transition: all 0.3s ease;
        }
        
        .form-control:focus { 
            outline: none; 
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn { 
            width: 100%; 
            padding: 12px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 600; 
            transition: all 0.3s ease;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error { 
            background: #fed7d7;
            color: #c53030; 
            font-size: 14px; 
            margin-bottom: 20px; 
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #c53030;
        }
        
        .login-link { 
            text-align: center; 
            margin-top: 20px; 
            font-size: 14px; 
            color: #718096;
        }
        
        .login-link a { 
            color: #667eea; 
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-sticky-note"></i>
            <h1>Create Account</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="?action=login">Sign in here</a>
        </div>
    </div>
</body>
</html>
<?php
}

function displayDashboard($message = '') {
    $user_id = $_SESSION['user_id'];
    $search_query = $_GET['search'] ?? '';
    
    if ($search_query) {
        $notes = searchNotes($user_id, $search_query);
    } else {
        $notes = getAllNotes($user_id);
    }
    
    // Separate pinned notes
    $pinned_notes = array_filter($notes, function($note) {
        return $note['is_pinned'];
    });
    $other_notes = array_filter($notes, function($note) {
        return !$note['is_pinned'];
    });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #2d3748;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: fixed;
            height: 100vh;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 24px 24px;
            font-weight: 700;
            font-size: 24px;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .logo i {
            margin-right: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 28px;
        }

        .user-info {
            padding: 16px 24px;
            background: white;
            margin: 0 16px 24px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .user-info p {
            font-size: 14px;
            color: #718096;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #2d3748;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f7fafc;
            color: #667eea;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
            color: #667eea;
            border-left-color: #667eea;
            font-weight: 600;
        }

        .nav-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            align-items: center;
            padding: 20px 32px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px 12px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }

        .user-actions {
            display: flex;
            align-items: center;
            margin-left: auto;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #2d3748;
        }

        /* Notes Container */
        .notes-container {
            flex: 1;
            padding: 32px;
            background: #f8fafc;
        }

        .notes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .notes-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 32px 0 20px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .note-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .note-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        .note-card.pinned {
            border-left: 4px solid #f59e0b;
        }

        .note-card.favorite {
            border-right: 4px solid #ef4444;
        }

        .note-title {
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 16px;
            line-height: 1.4;
            color: #2d3748;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            padding-right: 40px;
        }

        .note-preview {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .note-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #718096;
        }

        .note-category {
            background: #f7fafc;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .note-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .note-action {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            padding: 6px;
            font-size: 12px;
            color: #718096;
            transition: all 0.3s ease;
        }

        .note-action:hover {
            background: #f7fafc;
            transform: scale(1.1);
        }

        .note-action.active {
            color: #ef4444;
            border-color: #ef4444;
        }

        .note-action.pinned {
            color: #f59e0b;
            border-color: #f59e0b;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #2d3748;
        }

        .message {
            padding: 16px;
            background: #c6f6d5;
            color: #276749;
            border-radius: 8px;
            margin: 0 32px 24px;
            border-left: 4px solid #276749;
            font-weight: 500;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #2d3748;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 200px;
            resize: vertical;
            line-height: 1.5;
        }

        .modal-footer {
            padding: 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
            .notes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-sticky-note"></i>
            <span>NotionNotes</span>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            <p style="font-size: 12px; margin-top: 4px;"><?= htmlspecialchars($_SESSION['email']) ?></p>
        </div>
        
        <a href="?action=dashboard" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="?action=logout" class="nav-item" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <form method="GET" action="" id="searchForm">
                    <input type="hidden" name="action" value="dashboard">
                    <input type="text" name="search" placeholder="Search notes..." value="<?= htmlspecialchars($search_query) ?>">
                </form>
            </div>
            
            <div class="user-actions">
                <button class="btn btn-primary" onclick="openNoteModal()">
                    <i class="fas fa-plus"></i>
                    New Note
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="notes-container">
            <div class="notes-header">
                <h2><?= $search_query ? "Search Results" : "My Notes" ?></h2>
                <div class="notes-stats">
                    <span style="color: #718096; font-size: 14px;">
                        <?= count($notes) ?> note<?= count($notes) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
            
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <i class="fas fa-sticky-note"></i>
                    <h3>No notes found</h3>
                    <p><?= $search_query ? "Try a different search term" : "Create your first note to get started" ?></p>
                    <button class="btn btn-primary" style="margin-top: 20px;" onclick="openNoteModal()">
                        <i class="fas fa-plus"></i>
                        Create Your First Note
                    </button>
                </div>
            <?php else: ?>
                <?php if (!empty($pinned_notes)): ?>
                    <div class="section-title">
                        <i class="fas fa-thumbtack" style="color: #f59e0b;"></i>
                        Pinned Notes
                    </div>
                    <div class="notes-grid">
                        <?php foreach ($pinned_notes as $note): ?>
                            <div class="note-card pinned <?= $note['is_favorite'] ? 'favorite' : '' ?>" onclick="editNote('<?= $note['id'] ?>')">
                                <div class="note-actions">
                                    <button class="note-action <?= $note['is_favorite'] ? 'active' : '' ?>" onclick="event.stopPropagation(); toggleFavorite('<?= $note['id'] ?>')" title="<?= $note['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <button class="note-action pinned" onclick="event.stopPropagation(); togglePin('<?= $note['id'] ?>')" title="Unpin note">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                </div>
                                <div class="note-title"><?= htmlspecialchars($note['title']) ?></div>
                                <div class="note-preview"><?= htmlspecialchars($note['content']) ?></div>
                                <div class="note-meta">
                                    <span class="note-category"><?= htmlspecialchars($note['category']) ?></span>
                                    <span><?= date('M j, Y', strtotime($note['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($other_notes)): ?>
                    <?php if (!empty($pinned_notes)): ?>
                        <div class="section-title">
                            <i class="fas fa-notes"></i>
                            All Notes
                        </div>
                    <?php endif; ?>
                    <div class="notes-grid">
                        <?php foreach ($other_notes as $note): ?>
                            <div class="note-card <?= $note['is_favorite'] ? 'favorite' : '' ?>" onclick="editNote('<?= $note['id'] ?>')">
                                <div class="note-actions">
                                    <button class="note-action <?= $note['is_favorite'] ? 'active' : '' ?>" onclick="event.stopPropagation(); toggleFavorite('<?= $note['id'] ?>')" title="<?= $note['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <button class="note-action" onclick="event.stopPropagation(); togglePin('<?= $note['id'] ?>')" title="Pin note">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                </div>
                                <div class="note-title"><?= htmlspecialchars($note['title']) ?></div>
                                <div class="note-preview"><?= htmlspecialchars($note['content']) ?></div>
                                <div class="note-meta">
                                    <span class="note-category"><?= htmlspecialchars($note['category']) ?></span>
                                    <span><?= date('M j, Y', strtotime($note['updated_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Note Modal -->
    <div class="modal" id="noteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">New Note</h3>
                <button type="button" onclick="closeNoteModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #718096;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="noteForm" method="POST" action="">
                <input type="hidden" name="action" value="create_note">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="noteTitle">Title</label>
                        <input type="text" class="form-control" id="noteTitle" name="title" required placeholder="Enter note title">
                    </div>
                    <div class="form-group">
                        <label for="noteCategory">Category</label>
                        <select class="form-control" id="noteCategory" name="category">
                            <option value="general">General</option>
                            <option value="work">Work</option>
                            <option value="personal">Personal</option>
                            <option value="ideas">Ideas</option>
                            <option value="todo">To-Do</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="noteContent">Content</label>
                        <textarea class="form-control" id="noteContent" name="content" rows="10" placeholder="Start writing your note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeNoteModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openNoteModal() {
            document.getElementById('modalTitle').textContent = 'New Note';
            document.getElementById('noteForm').reset();
            document.getElementById('noteModal').style.display = 'flex';
        }
        
        function closeNoteModal() {
            document.getElementById('noteModal').style.display = 'none';
        }
        
        function editNote(noteId) {
            window.location.href = '?action=edit_note&id=' + noteId;
        }
        
        function toggleFavorite(noteId) {
            window.location.href = '?toggle_favorite=' + noteId;
        }
        
        function togglePin(noteId) {
            window.location.href = '?toggle_pin=' + noteId;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('noteModal');
            if (event.target === modal) {
                closeNoteModal();
            }
        }
        
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    </script>
</body>
</html>
<?php
}

function displayEditNote($note_id) {
    if (!$note_id) {
        header('Location: ?action=dashboard');
        return;
    }
    
    $note = getNoteById($note_id, $_SESSION['user_id']);
    if (!$note) {
        header('Location: ?action=dashboard');
        return;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note - NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #ffffff;
            color: #2d3748;
        }
        
        .editor-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .editor-header {
            margin-bottom: 30px;
        }
        
        .editor-title {
            width: 100%;
            font-size: 40px;
            font-weight: 700;
            border: none;
            outline: none;
            padding: 10px 0;
            margin-bottom: 10px;
            background: transparent;
            color: #2d3748;
        }
        
        .editor-title::placeholder {
            color: #718096;
        }
        
        .editor-meta {
            display: flex;
            gap: 20px;
            color: #718096;
            font-size: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .editor-content textarea {
            width: 100%;
            min-height: 500px;
            border: none;
            outline: none;
            resize: none;
            font-size: 16px;
            line-height: 1.6;
            padding: 10px 0;
            background: transparent;
            color: #2d3748;
        }
        
        .editor-content textarea::placeholder {
            color: #718096;
        }
        
        .editor-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #2d3748;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #2d3748;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .editor-container {
                padding: 20px 15px;
            }
            
            .editor-title {
                font-size: 32px;
            }
            
            .editor-actions {
                position: static;
                margin-top: 30px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_note">
            <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
            
            <div class="editor-header">
                <input type="text" class="editor-title" name="title" value="<?= htmlspecialchars($note['title']) ?>" placeholder="Note title...">
                <div class="editor-meta">
                    <div>
                        <select name="category">
                            <option value="general" <?= $note['category'] == 'general' ? 'selected' : '' ?>>General</option>
                            <option value="work" <?= $note['category'] == 'work' ? 'selected' : '' ?>>Work</option>
                            <option value="personal" <?= $note['category'] == 'personal' ? 'selected' : '' ?>>Personal</option>
                            <option value="ideas" <?= $note['category'] == 'ideas' ? 'selected' : '' ?>>Ideas</option>
                            <option value="todo" <?= $note['category'] == 'todo' ? 'selected' : '' ?>>To-Do</option>
                        </select>
                    </div>
                    <div>Last edited: <?= date('M j, Y g:i A', strtotime($note['updated_at'])) ?></div>
                </div>
            </div>
            
            <div class="editor-content">
                <textarea name="content" placeholder="Start writing..."><?= htmlspecialchars($note['content']) ?></textarea>
            </div>
            
            <div class="editor-actions">
                <button type="button" class="btn btn-outline" onclick="window.location.href='?action=dashboard'">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </button>
                <button type="button" class="btn btn-danger" onclick="deleteNote('<?= $note['id'] ?>')">
                    <i class="fas fa-trash"></i>
                    Delete Note
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
    
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="delete_note">
        <input type="hidden" name="note_id" id="deleteNoteId">
    </form>
    
    <script>
        function deleteNote(noteId) {
            if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
                document.getElementById('deleteNoteId').value = noteId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
<?php
}
?>
