<?php
// notion_notes_ai_enhanced.php - AI-Powered Notes App with Grok Integration
session_start();

// Configuration
define('DATA_DIR', __DIR__ . '/data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('NOTES_FILE', DATA_DIR . 'notes.json');
define('AI_SETTINGS_FILE', DATA_DIR . 'ai_settings.json');

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
if (!file_exists(AI_SETTINGS_FILE)) {
    file_put_contents(AI_SETTINGS_FILE, json_encode([
        'ai_enabled' => true,
        'auto_summarize' => true,
        'smart_suggestions' => true,
        'writing_assistant' => true
    ]));
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

function getAISettings() {
    if (!file_exists(AI_SETTINGS_FILE)) return [];
    $data = file_get_contents(AI_SETTINGS_FILE);
    return json_decode($data, true) ?: [];
}

function saveAISettings($settings) {
    file_put_contents(AI_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
}

// AI Functions
function callGrokAI($prompt, $context = '') {
    // This would be called from JavaScript, but we'll create a PHP wrapper for server-side calls
    return [
        'success' => true,
        'message' => 'AI features require JavaScript integration. See the AI panel in the editor.',
        'content' => ''
    ];
}

function generateAISuggestions($content) {
    $suggestions = [];
    
    // Basic auto-suggestions based on content analysis
    if (strlen($content) > 500) {
        $suggestions[] = [
            'type' => 'summary',
            'title' => 'Generate Summary',
            'description' => 'Create a concise summary of this long note'
        ];
    }
    
    if (preg_match('/\b(meeting|discuss|agenda)\b/i', $content)) {
        $suggestions[] = [
            'type' => 'meeting',
            'title' => 'Meeting Notes Template',
            'description' => 'Format this as structured meeting notes'
        ];
    }
    
    if (preg_match('/\b(todo|task|reminder)\b/i', $content)) {
        $suggestions[] = [
            'type' => 'todo',
            'title' => 'Task List',
            'description' => 'Convert to organized task list'
        ];
    }
    
    if (preg_match('/\b(idea|brainstorm|creative)\b/i', $content)) {
        $suggestions[] = [
            'type' => 'ideas',
            'title' => 'Expand Ideas',
            'description' => 'Get creative suggestions to expand these ideas'
        ];
    }
    
    return $suggestions;
}

// Authentication functions (same as before)
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
        'ai_enabled' => true
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['username'];
    $_SESSION['email'] = $newUser['email'];
    
    return true;
}

// Note functions (same as before with AI enhancements)
function getAllNotes($user_id) {
    $notes = getNotes();
    $userNotes = [];
    
    foreach ($notes as $note) {
        if ($note['user_id'] === $user_id && empty($note['deleted_at'])) {
            $userNotes[] = $note;
        }
    }
    
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

function createNote($user_id, $title, $content, $category = 'general', $ai_generated = false) {
    $notes = getNotes();
    
    $newNote = [
        'id' => uniqid(),
        'user_id' => $user_id,
        'title' => $title,
        'content' => $content,
        'category' => $category,
        'is_favorite' => false,
        'is_pinned' => false,
        'ai_generated' => $ai_generated,
        'ai_suggestions' => generateAISuggestions($content),
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
            $note['ai_suggestions'] = generateAISuggestions($content);
            $note['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveNotes($notes);
    return true;
}

// ... (other note functions: deleteNote, searchNotes, toggleFavorite, togglePin)

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
        'ai_enabled' => true
    ];
    $users[] = $defaultUser;
    saveUsers($users);
    
    createNote('admin', 'Welcome to AI-Powered NotionNotes!', 
        "## 🤖 Welcome to Your AI-Enhanced Notes App!\n\nThis version includes powerful AI features powered by Grok:\n\n### 🚀 AI Features Available:\n- **Smart Writing Assistant** - Get AI-powered writing suggestions\n- **Auto-Summarization** - Summarize long notes instantly\n- **Content Expansion** - Expand your ideas with AI\n- **Meeting Notes Helper** - Structure your meeting notes\n- **Task List Generator** - Convert text to organized tasks\n- **Creative Brainstorming** - Get creative ideas and suggestions\n\n### 💡 How to Use AI Features:\n1. Click the 'AI Assistant' button in the editor\n2. Choose from various AI tools\n3. Let Grok enhance your writing\n4. Apply suggestions with one click\n\nStart by creating a note and exploring the AI features!",
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
            
        case 'update_ai_settings':
            if (isLoggedIn()) {
                $settings = [
                    'ai_enabled' => isset($_POST['ai_enabled']),
                    'auto_summarize' => isset($_POST['auto_summarize']),
                    'smart_suggestions' => isset($_POST['smart_suggestions']),
                    'writing_assistant' => isset($_POST['writing_assistant'])
                ];
                saveAISettings($settings);
                $message = 'AI settings updated successfully!';
                header('Location: ?action=ai_settings&message=' . urlencode($message));
                exit;
            }
            break;
    }
}

// Handle GET actions
if (isset($_GET['toggle_favorite'])) {
    if (isLoggedIn()) {
        // toggleFavorite function would be here
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
        session_destroy();
        header('Location: ?action=login');
        exit;
    case 'dashboard':
        displayDashboard($message);
        break;
    case 'edit_note':
        displayEditNote($_GET['id'] ?? null);
        break;
    case 'ai_settings':
        displayAISettingsPage($message);
        break;
    default:
        displayDashboard();
}

function displayLoginPage($message = '') {
    // Same login page as before
    include 'login.html';
}

function displayRegisterPage($message = '') {
    // Same register page as before
    include 'register.html';
}

function displayDashboard($message = '') {
    $user_id = $_SESSION['user_id'];
    $search_query = $_GET['search'] ?? '';
    
    if ($search_query) {
        $notes = []; // searchNotes function would be here
    } else {
        $notes = getAllNotes($user_id);
    }
    
    // Display dashboard with AI-enhanced notes
    include 'dashboard.html';
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
    
    // Display enhanced editor with AI features
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note - AI NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.puter.com/v2/"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --ai-color: #10b981;
            --bg-color: #ffffff;
            --sidebar-bg: #f8fafc;
            --text-color: #2d3748;
            --text-light: #718096;
            --border-color: #e2e8f0;
            --radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .editor-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        .editor-main {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .editor-header {
            margin-bottom: 30px;
        }
        
        .editor-title {
            width: 100%;
            font-size: 32px;
            font-weight: 700;
            border: none;
            outline: none;
            padding: 10px 0;
            margin-bottom: 10px;
            background: transparent;
            color: var(--text-color);
        }
        
        .editor-meta {
            display: flex;
            gap: 20px;
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .editor-content {
            margin-bottom: 30px;
        }
        
        .editor-content textarea {
            width: 100%;
            min-height: 400px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .editor-content textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .ai-sidebar {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .ai-section {
            margin-bottom: 25px;
        }
        
        .ai-section h3 {
            color: var(--ai-color);
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ai-tool {
            background: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ai-tool:hover {
            border-color: var(--ai-color);
            transform: translateY(-2px);
        }
        
        .ai-tool h4 {
            color: var(--text-color);
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .ai-tool p {
            color: var(--text-light);
            font-size: 12px;
            line-height: 1.4;
        }
        
        .ai-suggestions {
            background: #f0f9ff;
            border-left: 4px solid var(--ai-color);
            padding: 15px;
            border-radius: var(--radius);
            margin-top: 20px;
        }
        
        .suggestion-item {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .suggestion-item:hover {
            border-color: var(--ai-color);
        }
        
        .editor-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius);
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-ai {
            background: linear-gradient(135deg, var(--ai-color), #059669);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--border-color);
            color: var(--text-color);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .ai-response {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .ai-loading {
            text-align: center;
            color: var(--text-light);
            padding: 20px;
        }
        
        .ai-prompt-input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .editor-container {
                grid-template-columns: 1fr;
            }
            
            .ai-sidebar {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <div class="editor-main">
            <form method="POST" action="" id="noteForm">
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
                    <textarea name="content" placeholder="Start writing..." id="noteContent"><?= htmlspecialchars($note['content']) ?></textarea>
                </div>
                
                <?php if (!empty($note['ai_suggestions'])): ?>
                <div class="ai-suggestions">
                    <h3><i class="fas fa-robot"></i> AI Suggestions</h3>
                    <?php foreach ($note['ai_suggestions'] as $suggestion): ?>
                        <div class="suggestion-item" onclick="useAISuggestion('<?= $suggestion['type'] ?>')">
                            <strong><?= $suggestion['title'] ?></strong>
                            <p><?= $suggestion['description'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="editor-actions">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='?action=dashboard'">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-danger" onclick="deleteNote('<?= $note['id'] ?>')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <div class="ai-sidebar">
            <div class="ai-section">
                <h3><i class="fas fa-robot"></i> AI Writing Assistant</h3>
                
                <div class="ai-tool" onclick="improveWriting()">
                    <h4>✍️ Improve Writing</h4>
                    <p>Enhance clarity, grammar, and style</p>
                </div>
                
                <div class="ai-tool" onclick="summarizeContent()">
                    <h4>📝 Summarize</h4>
                    <p>Create a concise summary</p>
                </div>
                
                <div class="ai-tool" onclick="expandIdeas()">
                    <h4>💡 Expand Ideas</h4>
                    <p>Get creative suggestions</p>
                </div>
                
                <div class="ai-tool" onclick="generateOutline()">
                    <h4>📋 Generate Outline</h4>
                    <p>Create structured outline</p>
                </div>
            </div>
            
            <div class="ai-section">
                <h3><i class="fas fa-magic"></i> Smart Templates</h3>
                
                <div class="ai-tool" onclick="applyMeetingTemplate()">
                    <h4>👥 Meeting Notes</h4>
                    <p>Structured meeting template</p>
                </div>
                
                <div class="ai-tool" onclick="applyTodoTemplate()">
                    <h4>✅ Task List</h4>
                    <p>Organized task template</p>
                </div>
                
                <div class="ai-tool" onclick="applyBrainstormTemplate()">
                    <h4>🎯 Brainstorming</h4>
                    <p>Creative ideas template</p>
                </div>
            </div>
            
            <div class="ai-section">
                <h3><i class="fas fa-comment"></i> Custom AI Prompt</h3>
                <input type="text" class="ai-prompt-input" id="customPrompt" placeholder="Ask AI to help with...">
                <button class="btn btn-ai" onclick="customAIPrompt()" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Ask AI
                </button>
                <div id="aiResponse" class="ai-response" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="delete_note">
        <input type="hidden" name="note_id" id="deleteNoteId">
    </form>
    
    <script>
        function deleteNote(noteId) {
            if (confirm('Are you sure you want to delete this note?')) {
                document.getElementById('deleteNoteId').value = noteId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // AI Functions using Puter.com Grok API
        async function callGrokAI(prompt, context = '') {
            const noteContent = document.getElementById('noteContent').value;
            const fullPrompt = `${prompt}\n\nContext: ${context || noteContent}`;
            
            showAILoading();
            
            try {
                const response = await puter.ai.chat(fullPrompt, {model: 'x-ai/grok-4'});
                hideAILoading();
                return response.message.content;
            } catch (error) {
                hideAILoading();
                return `AI Error: ${error.message}. Please check your connection and try again.`;
            }
        }
        
        function showAILoading() {
            const responseDiv = document.getElementById('aiResponse');
            responseDiv.innerHTML = '<div class="ai-loading"><i class="fas fa-spinner fa-spin"></i> AI is thinking...</div>';
            responseDiv.style.display = 'block';
        }
        
        function hideAILoading() {
            // Loading will be replaced by actual content
        }
        
        async function improveWriting() {
            const improved = await callGrokAI(
                "Please improve this text for better clarity, grammar, and style while preserving the original meaning and tone:"
            );
            document.getElementById('aiResponse').innerHTML = `<strong>Improved Version:</strong><br>${improved}`;
        }
        
        async function summarizeContent() {
            const summary = await callGrokAI(
                "Please provide a concise summary of the main points in this text:"
            );
            document.getElementById('aiResponse').innerHTML = `<strong>Summary:</strong><br>${summary}`;
        }
        
        async function expandIdeas() {
            const expansion = await callGrokAI(
                "Please expand on these ideas with creative suggestions and additional perspectives:"
            );
            document.getElementById('aiResponse').innerHTML = `<strong>Expanded Ideas:</strong><br>${expansion}`;
        }
        
        async function generateOutline() {
            const outline = await callGrokAI(
                "Please create a well-structured outline for this content with clear sections and subpoints:"
            );
            document.getElementById('aiResponse').innerHTML = `<strong>Outline:</strong><br>${outline}`;
        }
        
        function applyMeetingTemplate() {
            const template = `Meeting: [Topic]
Date: ${new Date().toLocaleDateString()}
Attendees: 
- 
- 

Agenda:
1. 
2. 
3. 

Discussion Points:
- 
- 
- 

Action Items:
- [ ] 
- [ ] 
- [ ] 

Next Steps:`;
            document.getElementById('noteContent').value = template;
        }
        
        function applyTodoTemplate() {
            const template = `## Tasks for ${new Date().toLocaleDateString()}

### High Priority
- [ ] 

### Medium Priority  
- [ ] 

### Low Priority
- [ ] 

### Completed
- [x] `;
            document.getElementById('noteContent').value = template;
        }
        
        function applyBrainstormTemplate() {
            const template = `## Brainstorming Session
Date: ${new Date().toLocaleDateString()}

### Core Idea


### Related Concepts
- 
- 
- 

### Potential Applications
- 
- 
- 

### Questions to Explore
- 
- 
- 

### Next Actions
- [ ] Research 
- [ ] Discuss with 
- [ ] Create prototype`;
            document.getElementById('noteContent').value = template;
        }
        
        async function customAIPrompt() {
            const prompt = document.getElementById('customPrompt').value;
            if (!prompt) {
                alert('Please enter a prompt for the AI');
                return;
            }
            
            const response = await callGrokAI(prompt);
            document.getElementById('aiResponse').innerHTML = `<strong>AI Response:</strong><br>${response}`;
        }
        
        function useAISuggestion(type) {
            const content = document.getElementById('noteContent').value;
            let prompt = '';
            
            switch(type) {
                case 'summary':
                    prompt = "Please provide a concise summary of this text:";
                    break;
                case 'meeting':
                    applyMeetingTemplate();
                    return;
                case 'todo':
                    applyTodoTemplate();
                    return;
                case 'ideas':
                    prompt = "Please expand on these ideas with creative suggestions:";
                    break;
            }
            
            if (prompt) {
                callGrokAI(prompt, content).then(response => {
                    document.getElementById('aiResponse').innerHTML = `<strong>AI Suggestion:</strong><br>${response}`;
                });
            }
        }
        
        // Auto-save functionality
        let saveTimeout;
        const noteContent = document.getElementById('noteContent');
        
        noteContent.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                // Here you could implement auto-save
                console.log('Content changed - ready for auto-save');
            }, 2000);
        });
    </script>
</body>
</html>
<?php
}

function displayAISettingsPage($message = '') {
    $ai_settings = getAISettings();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Settings - NotionNotes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --ai-color: #10b981;
            --bg-color: #ffffff;
            --text-color: #2d3748;
            --text-light: #718096;
            --border-color: #e2e8f0;
            --radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), #764ba2);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .settings-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .settings-header p {
            opacity: 0.9;
        }
        
        .settings-content {
            padding: 40px;
        }
        
        .ai-feature {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .feature-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 15px;
        }
        
        .feature-header h3 {
            color: var(--text-color);
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--ai-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .feature-description {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .message {
            padding: 15px;
            background: #c6f6d5;
            color: #276749;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #276749;
        }
        
        .ai-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 30px;
        }
        
        .ai-info h3 {
            color: #0369a1;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <h1><i class="fas fa-robot"></i> AI Settings</h1>
            <p>Configure your AI assistant powered by Grok</p>
        </div>
        
        <div class="settings-content">
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_ai_settings">
                
                <div class="ai-feature">
                    <div class="feature-header">
                        <h3><i class="fas fa-brain"></i> AI Assistant</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="ai_enabled" <?= $ai_settings['ai_enabled'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">Enable the AI writing assistant for smart suggestions and content generation.</p>
                </div>
                
                <div class="ai-feature">
                    <div class="feature-header">
                        <h3><i class="fas fa-file-contract"></i> Auto-Summarization</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_summarize" <?= $ai_settings['auto_summarize'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">Automatically generate summaries for long notes.</p>
                </div>
                
                <div class="ai-feature">
                    <div class="feature-header">
                        <h3><i class="fas fa-lightbulb"></i> Smart Suggestions</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="smart_suggestions" <?= $ai_settings['smart_suggestions'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">Get intelligent writing suggestions based on your content.</p>
                </div>
                
                <div class="ai-feature">
                    <div class="feature-header">
                        <h3><i class="fas fa-magic"></i> Writing Assistant</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="writing_assistant" <?= $ai_settings['writing_assistant'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">Real-time writing improvements and style suggestions.</p>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save AI Settings
                    </button>
                    <a href="?action=dashboard" class="btn" style="background: #e2e8f0; color: #4a5568; margin-left: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
            
            <div class="ai-info">
                <h3><i class="fas fa-info-circle"></i> About Grok AI Integration</h3>
                <p>This app uses Grok AI through Puter.com's free API to provide intelligent writing assistance, content generation, and smart suggestions.</p>
                <p><strong>Features include:</strong> Writing improvement, summarization, idea expansion, template generation, and custom AI prompts.</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
?>
