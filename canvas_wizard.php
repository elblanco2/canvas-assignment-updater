<?php
/**
 * Canvas Assignment Due Date Updater - Wizard Interface
 * 
 * Clean multi-step wizard with AI chatbot assistant
 * 
 * Steps:
 * 1. API Key Setup
 * 2. Assignment Matching
 * 3. Canvas Update
 * 
 * @version 3.0
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Embedded AI Assignment Matcher Class
class AIAssignmentMatcher {
    private $gemini_api_key;
    private $claude_api_key;
    private $preferred_provider;
    
    public function __construct($gemini_key = null, $claude_key = null, $provider = 'gemini') {
        $this->gemini_api_key = $gemini_key;
        $this->claude_api_key = $claude_key;
        $this->preferred_provider = $provider;
    }
    
    private function callGeminiAPI($prompt) {
        if (!$this->gemini_api_key) {
            throw new Exception("Gemini API key required");
        }
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $this->gemini_api_key;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2048,
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Gemini API error: $response");
        }
        
        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
    
    private function callClaudeAPI($prompt) {
        if (!$this->claude_api_key) {
            throw new Exception("Claude API key required");
        }
        
        $url = "https://api.anthropic.com/v1/messages";
        
        $data = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 2048,
            'temperature' => 0.1,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->claude_api_key,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claude API error: $response");
        }
        
        $result = json_decode($response, true);
        return $result['content'][0]['text'] ?? '';
    }
    
    private function callAI($prompt) {
        try {
            if ($this->preferred_provider === 'claude' && $this->claude_api_key) {
                return $this->callClaudeAPI($prompt);
            } else if ($this->gemini_api_key) {
                return $this->callGeminiAPI($prompt);
            } else {
                throw new Exception("No API keys configured");
            }
        } catch (Exception $e) {
            if ($this->preferred_provider === 'claude' && $this->gemini_api_key) {
                return $this->callGeminiAPI($prompt);
            } else if ($this->preferred_provider === 'gemini' && $this->claude_api_key) {
                return $this->callClaudeAPI($prompt);
            } else {
                throw $e;
            }
        }
    }
    
    public function aiMatchAssignments($assignment_list, $due_date_schedule) {
        $prompt = "You are an expert at matching Canvas assignment lists with due date schedules for academic courses.

CANVAS ASSIGNMENTS LIST:
$assignment_list

DUE DATE SCHEDULE:
$due_date_schedule

Please analyze both lists and create intelligent matches between assignments and their intended due dates. 

IMPORTANT INSTRUCTIONS:
1. Look for semantic similarities, not just exact text matches
2. Consider abbreviations, partial names, and context
3. Match assignments with the most appropriate due dates based on content and sequence
4. If an assignment appears to be a quiz, match it with quiz due dates
5. If an assignment is clearly a project, match it with project due dates

Please return your matches in this EXACT JSON format:
{
  \"matches\": [
    {
      \"assignment_name\": \"Exact Canvas assignment name\",
      \"matched_due_date\": \"YYYY-MM-DDTHH:MM:SS-04:00\",
      \"confidence\": \"high|medium|low\",
      \"reasoning\": \"Brief explanation of why this match was made\"
    }
  ]
}

Only return valid JSON. Do not include any other text or formatting.";

        $response = $this->callAI($prompt);
        
        $response = trim($response);
        if (strpos($response, '```json') !== false) {
            $response = preg_replace('/```json\s*/', '', $response);
            $response = preg_replace('/\s*```/', '', $response);
        }
        
        $matches = json_decode($response, true);
        
        if (!$matches || !isset($matches['matches'])) {
            throw new Exception("AI response was not valid JSON: " . substr($response, 0, 200) . "...");
        }
        
        return $matches['matches'];
    }
    
    public function chatAssist($user_question, $assignment_list = '', $due_date_schedule = '', $matches = []) {
        $context = "";
        if (!empty($assignment_list)) {
            $context .= "\n\nCANVAS ASSIGNMENTS:\n$assignment_list";
        }
        if (!empty($due_date_schedule)) {
            $context .= "\n\nDUE DATE SCHEDULE:\n$due_date_schedule";
        }
        if (!empty($matches)) {
            $context .= "\n\nCURRENT MATCHES:\n";
            foreach ($matches as $i => $match) {
                $formatted_date = date('l, F j, Y \a\t g:i A T', strtotime($match['matched_due_date']));
                $context .= ($i + 1) . ". " . $match['assignment_name'] . " → " . $formatted_date . " (Confidence: " . $match['confidence'] . ")\n";
                $context .= "   Reasoning: " . $match['reasoning'] . "\n\n";
            }
        }
        
        $prompt = "You are a helpful AI assistant for Canvas assignment management. Today is August 21, 2025, and the user is preparing for Fall 2025 semester (typically August-December 2025).

Your role is to:
1. Help generate typical Fall 2025 academic schedules when requested
2. Suggest realistic due dates for Fall 2025 semester assignments
3. Help match existing assignments with new due date schedules
4. Recommend when they should proceed to update Canvas
5. Consider typical Fall semester calendar (Late August start, Thanksgiving break, Finals in December)

When users ask for schedule generation:
- Assume Fall 2025 semester runs August 26 - December 13, 2025
- Include typical academic breaks (Labor Day, Fall Break, Thanksgiving week)
- Space assignments appropriately throughout the semester
- Use realistic due times (usually 11:59 PM)

CONTEXT:$context

USER QUESTION: $user_question

Please provide helpful, specific advice for Fall 2025 scheduling. Always use the format: 2025-09-15T23:59:00-04:00 for dates.

If the user asks you to generate a schedule, create one with appropriate spacing for Fall 2025.";

        return $this->callAI($prompt);
    }
    
    public function suggestMatches($assignment_list, $due_date_schedule) {
        $prompt = "You are helping a user match Canvas assignments with due dates. Please suggest 3-5 good matches they should consider and explain why.

CANVAS ASSIGNMENTS:
$assignment_list

DUE DATE SCHEDULE:
$due_date_schedule

Please provide conversational suggestions like:
'I recommend matching [Assignment X] with [Date Y] because...'
'You might want to consider...'
'The assignment [Z] looks like it should be due on...'

Be helpful and specific.";

        return $this->callAI($prompt);
    }
}

// Generate a typical Fall 2025 schedule
function generateFall2025Schedule() {
    return "Fall 2025 Academic Schedule:

Week 1 (Aug 26): Syllabus Quiz - Due: 2025-08-30T23:59:00-04:00
Week 2 (Sep 2): Introduction Discussion - Due: 2025-09-06T23:59:00-04:00  
Week 3 (Sep 9): Assignment 1 - Due: 2025-09-13T23:59:00-04:00
Week 4 (Sep 16): Quiz 1 - Due: 2025-09-20T23:59:00-04:00
Week 5 (Sep 23): Discussion Post 2 - Due: 2025-09-27T23:59:00-04:00
Week 6 (Sep 30): Assignment 2 - Due: 2025-10-04T23:59:00-04:00
Week 7 (Oct 7): Midterm Project - Due: 2025-10-11T23:59:00-04:00
Week 8 (Oct 14): Quiz 2 - Due: 2025-10-18T23:59:00-04:00
Week 9 (Oct 21): Research Paper Draft - Due: 2025-10-25T23:59:00-04:00
Week 10 (Oct 28): Assignment 3 - Due: 2025-11-01T23:59:00-04:00
Week 11 (Nov 4): Discussion Post 3 - Due: 2025-11-08T23:59:00-04:00
Week 12 (Nov 11): Presentation - Due: 2025-11-15T23:59:00-04:00
Week 13 (Nov 18): Final Paper - Due: 2025-11-22T23:59:00-04:00
Week 14 (Nov 25): Thanksgiving Break - No assignments
Week 15 (Dec 2): Final Project - Due: 2025-12-06T23:59:00-04:00
Finals Week: Final Exam - Due: 2025-12-13T23:59:00-04:00";
}

// Canvas API Integration Function
function updateCanvasAssignments($canvas_url, $api_token, $course_id, $matches) {
    $updated_count = 0;
    $errors = [];
    
    foreach ($matches as $match) {
        try {
            // Get assignment ID first
            $assignment_name = $match['assignment_name'];
            $new_due_date = $match['matched_due_date'];
            
            // Search for assignment by name
            $search_url = rtrim($canvas_url, '/') . "/courses/$course_id/assignments";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $search_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $errors[] = "Failed to fetch assignments: HTTP $httpCode";
                continue;
            }
            
            $assignments = json_decode($response, true);
            $assignment_id = null;
            
            // Find assignment by name (flexible matching)
            foreach ($assignments as $assignment) {
                if (stripos($assignment['name'], $assignment_name) !== false || 
                    stripos($assignment_name, $assignment['name']) !== false) {
                    $assignment_id = $assignment['id'];
                    break;
                }
            }
            
            if (!$assignment_id) {
                $errors[] = "Assignment not found: $assignment_name";
                continue;
            }
            
            // Update the assignment
            $update_url = rtrim($canvas_url, '/') . "/courses/$course_id/assignments/$assignment_id";
            $update_data = [
                'assignment' => [
                    'due_at' => $new_due_date
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $update_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($update_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $updated_count++;
            } else {
                $errors[] = "Failed to update $assignment_name: HTTP $httpCode";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error updating $assignment_name: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['canvas_errors'] = $errors;
    }
    
    return $updated_count;
}

// Handle AJAX date update requests
if (isset($_POST['ajax']) && $_POST['ajax'] === 'update_date') {
    header('Content-Type: application/json');
    
    try {
        $index = (int)($_POST['index'] ?? -1);
        $newDate = $_POST['date'] ?? '';
        
        if ($index >= 0 && !empty($newDate) && isset($_SESSION['matches'][$index])) {
            // Convert to Canvas format
            $formattedDate = date('Y-m-d\TH:i:s-04:00', strtotime($newDate));
            $_SESSION['matches'][$index]['matched_due_date'] = $formattedDate;
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid parameters']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX chat requests
if (isset($_POST['ajax']) && $_POST['ajax'] === 'chat') {
    header('Content-Type: application/json');
    
    try {
        $gemini_key = $_SESSION['gemini_key'] ?? '';
        $claude_key = $_SESSION['claude_key'] ?? '';
        $provider = $_SESSION['ai_provider'] ?? 'gemini';
        
        if (empty($gemini_key) && empty($claude_key)) {
            echo json_encode(['error' => 'No API keys configured']);
            exit;
        }
        
        $ai_matcher = new AIAssignmentMatcher($gemini_key, $claude_key, $provider);
        $response = $ai_matcher->chatAssist(
            $_POST['question'] ?? '',
            $_SESSION['assignment_list'] ?? '',
            $_SESSION['due_dates'] ?? '',
            $_SESSION['matches'] ?? []
        );
        
        echo json_encode(['response' => $response]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Handle form submissions
$error = null;
$success = null;
$debug_info = "";

// Initialize session step if not set
if (!isset($_SESSION['current_step'])) {
    $_SESSION['current_step'] = '1';
}

$current_step = $_SESSION['current_step'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        // Ensure we maintain the current step from the form
        if (isset($_POST['current_step']) && !empty($_POST['current_step'])) {
            $current_step = $_POST['current_step'];
            $_SESSION['current_step'] = $current_step;
        }
        
        switch ($action) {
            case 'setup_api':
                $_SESSION['gemini_key'] = $_POST['gemini_api_key'] ?? '';
                $_SESSION['claude_key'] = $_POST['claude_api_key'] ?? '';
                $_SESSION['ai_provider'] = $_POST['ai_provider'] ?? 'gemini';
                
                if (empty($_SESSION['gemini_key']) && empty($_SESSION['claude_key'])) {
                    throw new Exception("Please provide at least one API key");
                }
                
                $_SESSION['current_step'] = '2';
                $current_step = '2';
                $success = "API keys configured successfully!";
                break;
                
            case 'match_assignments':
                $_SESSION['assignment_list'] = $_POST['assignment_list'] ?? '';
                
                if (empty($_SESSION['assignment_list'])) {
                    throw new Exception("Please paste your Canvas assignment list");
                }
                
                $gemini_key = $_SESSION['gemini_key'] ?? '';
                $claude_key = $_SESSION['claude_key'] ?? '';
                $provider = $_SESSION['ai_provider'] ?? 'gemini';
                
                if (empty($gemini_key) && empty($claude_key)) {
                    throw new Exception("No AI API keys configured. Please go back to Step 1.");
                }
                
                // Generate Fall 2025 schedule automatically
                $fall_2025_schedule = generateFall2025Schedule();
                $_SESSION['due_dates'] = $fall_2025_schedule;
                
                $ai_matcher = new AIAssignmentMatcher($gemini_key, $claude_key, $provider);
                $matches = $ai_matcher->aiMatchAssignments($_SESSION['assignment_list'], $fall_2025_schedule);
                
                $_SESSION['matches'] = $matches;
                $_SESSION['current_step'] = '2';
                $current_step = '2';
                
                $success = "Fall 2025 schedule generated! " . count($matches) . " assignments matched with dates.";
                break;
                
            case 'proceed_to_canvas':
                if (empty($_SESSION['matches'])) {
                    throw new Exception("No matches available. Please complete the matching step first.");
                }
                $_SESSION['current_step'] = '3';
                $current_step = '3';
                break;
                
            case 'update_canvas':
                $canvas_url = $_POST['canvas_url'] ?? '';
                $api_token = $_POST['api_token'] ?? '';
                $course_id = $_POST['course_id'] ?? '';
                
                if (empty($canvas_url) || empty($api_token) || empty($course_id)) {
                    throw new Exception("Please provide Canvas URL, API token, and Course ID");
                }
                
                // Actual Canvas API integration
                $updated_count = updateCanvasAssignments($canvas_url, $api_token, $course_id, $_SESSION['matches']);
                $success = "Canvas update completed! Updated $updated_count assignments successfully.";
                break;
                
            case 'reset_wizard':
                // Clear all session data and start over
                session_destroy();
                session_start();
                $_SESSION['current_step'] = '1';
                $current_step = '1';
                $success = "Wizard reset. Starting fresh!";
                break;
                
            case 'back_to_step':
                $target_step = $_POST['target_step'] ?? '1';
                if (in_array($target_step, ['1', '2', '3'])) {
                    $_SESSION['current_step'] = $target_step;
                    $current_step = $target_step;
                    $success = "Moved back to step $target_step";
                }
                break;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Preserve the current step even when there's an error
        // Don't let exceptions reset us back to step 1
        if ($action === 'match_assignments') {
            $_SESSION['current_step'] = '2';
            $current_step = '2';
        } elseif ($action === 'proceed_to_canvas') {
            $_SESSION['current_step'] = '3'; 
            $current_step = '3';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canvas Assignment Updater - Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            height: 100vh;
            display: flex;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .ai-sidebar {
            width: 350px;
            background: #2c3e50;
            color: white;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #34495e;
        }
        
        .ai-header {
            padding: 20px;
            background: #34495e;
            border-bottom: 1px solid #4a5f7a;
        }
        
        .ai-chat {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .ai-input {
            padding: 15px;
            background: #34495e;
            border-top: 1px solid #4a5f7a;
        }
        
        .chat-messages {
            flex: 1;
            margin-bottom: 20px;
            overflow-y: auto;
            max-height: 300px;
        }
        
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }
        
        .chat-message.user {
            background: #3498db;
            margin-left: 20px;
        }
        
        .chat-message.ai {
            background: #27ae60;
            margin-right: 20px;
        }
        
        .chat-input-container {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #4a5f7a;
            border-radius: 5px;
            background: #2c3e50;
            color: white;
        }
        
        .chat-send {
            padding: 10px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .container {
            max-width: 800px;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .wizard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        .step.active .step-number {
            background: #3498db;
        }
        
        .step.completed .step-number {
            background: #27ae60;
        }
        
        .step.pending .step-number {
            background: #bdc3c7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .matches-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .matches-table th, .matches-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e6ed;
        }
        
        .matches-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .confidence-high { color: #27ae60; }
        .confidence-medium { color: #f39c12; }
        .confidence-low { color: #e74c3c; }
        
        .ai-suggestions {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .preview-section {
            border: 2px solid #3498db;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.1);
        }
        
        .preview-section h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .preview-section .matches-table {
            margin-top: 15px;
            background: white;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .preview-section .matches-table th {
            background: #34495e;
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <div class="wizard-header">
                <h1>🎨 Canvas Assignment Updater</h1>
                <p>AI-powered assignment due date management</p>
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="reset_wizard">
                    <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 5px 15px;">🔄 Start Over</button>
                </form>
            </div>
            
            <div class="step-indicator">
                <div class="step <?php echo $current_step == '1' ? 'active' : ($current_step > '1' ? 'completed' : 'pending'); ?>">
                    <div class="step-number">1</div>
                    <span>API Setup</span>
                </div>
                <div class="step <?php echo $current_step == '2' ? 'active' : ($current_step > '2' ? 'completed' : 'pending'); ?>">
                    <div class="step-number">2</div>
                    <span>Match Assignments</span>
                </div>
                <div class="step <?php echo $current_step == '3' ? 'active' : 'pending'; ?>">
                    <div class="step-number">3</div>
                    <span>Update Canvas</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($debug_info): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-family: monospace; font-size: 12px; white-space: pre-wrap; max-height: 300px; overflow-y: auto;">
                    <strong>🐛 DEBUG LOG:</strong><br>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION['canvas_errors'])): ?>
                <div class="alert alert-error">
                    <strong>Canvas API Errors:</strong>
                    <ul>
                        <?php foreach ($_SESSION['canvas_errors'] as $canvas_error): ?>
                            <li><?php echo htmlspecialchars($canvas_error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['canvas_errors']); ?>
            <?php endif; ?>
            
            <?php if ($current_step == '1'): ?>
                <!-- Step 1: API Setup -->
                <h2>Step 1: Configure AI Assistant</h2>
                <p>Choose your AI provider to enable intelligent assignment matching.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="setup_api">
                    
                    <div class="form-group">
                        <label>🆓 Google Gemini API Key (Free - Recommended)</label>
                        <input type="password" name="gemini_api_key" 
                               placeholder="Your Gemini API key" 
                               value="<?php echo htmlspecialchars($_SESSION['gemini_key'] ?? ''); ?>">
                        <small>Get free key: <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a></small>
                    </div>
                    
                    <div class="form-group">
                        <label>💰 Claude API Key (Optional - More Accurate)</label>
                        <input type="password" name="claude_api_key" 
                               placeholder="Your Claude API key (optional)" 
                               value="<?php echo htmlspecialchars($_SESSION['claude_key'] ?? ''); ?>">
                        <small>Get key: <a href="https://console.anthropic.com/" target="_blank">https://console.anthropic.com/</a></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred AI Provider</label>
                        <select name="ai_provider">
                            <option value="gemini" <?php echo ($_SESSION['ai_provider'] ?? 'gemini') == 'gemini' ? 'selected' : ''; ?>>Google Gemini (Free)</option>
                            <option value="claude" <?php echo ($_SESSION['ai_provider'] ?? 'gemini') == 'claude' ? 'selected' : ''; ?>>Claude (More Accurate)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Continue to Assignment Matching →</button>
                </form>
                
            <?php elseif ($current_step == '2'): ?>
                <!-- Step 2: Copy Assignment List -->
                <h2>Step 2: Copy Your Canvas Assignment List</h2>
                <p>Go to Canvas → Assignments → Copy the list and paste it below.</p>
                
                <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3>📋 Instructions:</h3>
                    <ol style="margin-left: 20px; line-height: 1.6;">
                        <li>Open your Canvas course</li>
                        <li>Go to <strong>Assignments</strong></li>
                        <li>Select all assignment names (Ctrl+A or Cmd+A)</li>
                        <li>Copy them (Ctrl+C or Cmd+C)</li>
                        <li>Paste the list in the box below</li>
                    </ol>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="match_assignments">
                    <input type="hidden" name="current_step" value="2">
                    
                    <div class="form-group">
                        <label>Canvas Assignment List</label>
                        <textarea name="assignment_list" 
                                  placeholder="Example:
Syllabus Quiz (Graded)
Discussion: Introduction  
Assignment 1: Research Paper
Quiz 1: Chapter 1-3
Project Proposal
..."
                                  style="min-height: 200px;"><?php echo htmlspecialchars($_SESSION['assignment_list'] ?? ''); ?></textarea>
                        <small>Paste your Canvas assignment names here, one per line.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Generate Fall 2025 Schedule →</button>
                </form>
                
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="back_to_step">
                    <input type="hidden" name="target_step" value="1">
                    <button type="submit" class="btn btn-secondary">← Back to API Setup</button>
                </form>
                
                <?php if (!empty($_SESSION['matches'])): ?>
                    <h3>📊 AI Matching Results</h3>
                    <table class="matches-table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Due Date</th>
                                <th>Confidence</th>
                                <th>AI Reasoning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['matches'] as $match): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($match['assignment_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($match['matched_due_date'])); ?></td>
                                    <td class="confidence-<?php echo $match['confidence']; ?>">
                                        <?php echo ucfirst($match['confidence']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($match['reasoning']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="proceed_to_canvas">
                        <button type="submit" class="btn btn-success">Proceed to Canvas Update →</button>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($current_step == '3'): ?>
                <!-- Step 3: Canvas Update -->
                <h2>Step 3: Update Canvas Assignments</h2>
                <p>Review the changes below and enter your Canvas credentials to apply them.</p>
                
                <?php if (!empty($_SESSION['matches'])): ?>
                    <div class="preview-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3>📋 Update Preview</h3>
                        <p><strong><?php echo count($_SESSION['matches']); ?> assignments</strong> will be updated with new due dates:</p>
                        
                        <table class="matches-table">
                            <thead>
                                <tr>
                                    <th>Assignment Name</th>
                                    <th>New Due Date</th>
                                    <th>Time</th>
                                    <th>Confidence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['matches'] as $index => $match): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($match['assignment_name']); ?></strong></td>
                                        <td>
                                            <span class="date-display"><?php echo date('l, F j, Y', strtotime($match['matched_due_date'])); ?></span>
                                            <input type="date" class="date-edit" style="display: none;" 
                                                   value="<?php echo date('Y-m-d', strtotime($match['matched_due_date'])); ?>"
                                                   data-index="<?php echo $index; ?>">
                                        </td>
                                        <td>
                                            <span class="time-display"><?php echo date('g:i A T', strtotime($match['matched_due_date'])); ?></span>
                                            <input type="time" class="time-edit" style="display: none;"
                                                   value="<?php echo date('H:i', strtotime($match['matched_due_date'])); ?>"
                                                   data-index="<?php echo $index; ?>">
                                        </td>
                                        <td>
                                            <span class="confidence-<?php echo $match['confidence']; ?>">
                                                <?php echo ucfirst($match['confidence']); ?>
                                            </span>
                                            <br><button type="button" class="btn btn-secondary" style="font-size: 11px; padding: 2px 8px; margin-top: 5px;" 
                                                        onclick="toggleEdit(<?php echo $index; ?>)">✏️ Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                            <strong>⚠️ Important:</strong> This will overwrite existing due dates in Canvas. Make sure these dates are correct before proceeding!
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_canvas">
                    
                    <div class="form-group">
                        <label>Canvas API URL</label>
                        <input type="text" name="canvas_url" 
                               placeholder="https://mdc.instructure.com/api/v1" 
                               value="<?php echo htmlspecialchars($_POST['canvas_url'] ?? 'https://mdc.instructure.com/api/v1'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Canvas API Token</label>
                        <input type="password" name="api_token" 
                               placeholder="Your Canvas API token">
                    </div>
                    
                    <div class="form-group">
                        <label>Course ID</label>
                        <input type="text" name="course_id" 
                               placeholder="453" 
                               value="<?php echo htmlspecialchars($_POST['course_id'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-success">🚀 Update Canvas Assignments</button>
                </form>
                
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="back_to_step">
                    <input type="hidden" name="target_step" value="2">
                    <button type="submit" class="btn btn-secondary">← Back to Matching</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="ai-sidebar">
        <div class="ai-header">
            <h3>🤖 AI Assistant</h3>
            <p>Ask me anything about assignment matching!</p>
        </div>
        
        <div class="ai-chat">
            <div class="chat-messages" id="chatMessages">
                <?php if ($current_step == '1'): ?>
                    <div class="chat-message ai">
                        <strong>AI:</strong> Hi! Set up your API key above to get started. I recommend the free Google Gemini option.
                    </div>
                <?php elseif ($current_step == '2'): ?>
                    <div class="chat-message ai">
                        <strong>AI:</strong> Copy your Canvas assignment list and I'll automatically generate Fall 2025 due dates for you.
                        <?php if (!empty($_SESSION['matches'])): ?>
                            <br><br>✅ I've matched <?php echo count($_SESSION['matches']); ?> assignments with Fall 2025 dates. 
                            Ask me: "Are these dates good?" or "Should I proceed to Canvas?"
                        <?php endif; ?>
                    </div>
                <?php elseif ($current_step == '3'): ?>
                    <div class="chat-message ai">
                        <strong>AI:</strong> Perfect! Your assignments are matched and ready for Canvas update. I can see <?php echo count($_SESSION['matches'] ?? []); ?> assignments will be updated for the current term. Review the preview above and enter your Canvas credentials to apply the changes.
                        <br><br><strong>Try asking me:</strong>
                        <br>• "Where do I find my Canvas API token?"
                        <br>• "What's my course ID for this term?"
                        <br>• "Will this overwrite existing due dates in Canvas?"
                        <br>• "Explain what each assignment will be changed to"
                        <br>• "Are these dates reasonable for my current term?"
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ai-input">
            <div class="chat-input-container">
                <input type="text" class="chat-input" id="chatInput" placeholder="Ask me anything...">
                <button class="chat-send" onclick="sendChatMessage()">Send</button>
            </div>
        </div>
    </div>
    
    <script>
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const messages = document.getElementById('chatMessages');
            const question = input.value.trim();
            
            if (!question) return;
            
            // Add user message
            const userMsg = document.createElement('div');
            userMsg.className = 'chat-message user';
            userMsg.innerHTML = '<strong>You:</strong> ' + question;
            messages.appendChild(userMsg);
            
            // Add loading message
            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'chat-message ai';
            loadingMsg.innerHTML = '<strong>AI:</strong> Thinking...';
            messages.appendChild(loadingMsg);
            
            input.value = '';
            messages.scrollTop = messages.scrollHeight;
            
            // Send AJAX request
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=chat&question=' + encodeURIComponent(question)
            })
            .then(response => response.json())
            .then(data => {
                messages.removeChild(loadingMsg);
                
                const aiMsg = document.createElement('div');
                aiMsg.className = 'chat-message ai';
                
                if (data.error) {
                    aiMsg.innerHTML = '<strong>AI:</strong> Error: ' + data.error;
                } else {
                    aiMsg.innerHTML = '<strong>AI:</strong> ' + data.response.replace(/\n/g, '<br>');
                }
                
                messages.appendChild(aiMsg);
                messages.scrollTop = messages.scrollHeight;
            })
            .catch(error => {
                messages.removeChild(loadingMsg);
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'chat-message ai';
                errorMsg.innerHTML = '<strong>AI:</strong> Sorry, I encountered an error. Please try again.';
                messages.appendChild(errorMsg);
            });
        }
        
        // Allow Enter key to send message
        document.getElementById('chatInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
        
        // Toggle edit mode for due dates
        function toggleEdit(index) {
            const row = document.querySelector(`[data-index="${index}"]`).closest('tr');
            const dateDisplay = row.querySelector('.date-display');
            const timeDisplay = row.querySelector('.time-display');
            const dateEdit = row.querySelector('.date-edit');
            const timeEdit = row.querySelector('.time-edit');
            const editBtn = row.querySelector('button');
            
            if (dateEdit.style.display === 'none') {
                // Switch to edit mode
                dateDisplay.style.display = 'none';
                timeDisplay.style.display = 'none';
                dateEdit.style.display = 'block';
                timeEdit.style.display = 'block';
                editBtn.innerHTML = '💾 Save';
                editBtn.style.background = '#27ae60';
            } else {
                // Save and switch back to display mode
                const newDate = dateEdit.value;
                const newTime = timeEdit.value;
                
                if (newDate && newTime) {
                    // Update the display
                    const combinedDate = new Date(newDate + 'T' + newTime);
                    dateDisplay.textContent = combinedDate.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    timeDisplay.textContent = combinedDate.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        timeZoneName: 'short'
                    });
                    
                    // Update session data via AJAX
                    updateMatchDate(index, combinedDate.toISOString());
                }
                
                dateDisplay.style.display = 'block';
                timeDisplay.style.display = 'block';
                dateEdit.style.display = 'none';
                timeEdit.style.display = 'none';
                editBtn.innerHTML = '✏️ Edit';
                editBtn.style.background = '#95a5a6';
            }
        }
        
        // Update match date in session
        function updateMatchDate(index, newDate) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=update_date&index=${index}&date=${encodeURIComponent(newDate)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Date updated successfully');
                } else {
                    alert('Failed to update date: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating date:', error);
            });
        }
    </script>
</body>
</html>