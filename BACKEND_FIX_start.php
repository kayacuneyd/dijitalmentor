<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

// Require authentication
$user = requireAuth(['student', 'parent']);

try {
    $userId = $user['id'];
    $userRole = $user['role'];

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['other_user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'other_user_id is required'
        ]);
        exit();
    }

    $otherUserId = (int)$input['other_user_id'];

    // Prevent messaging yourself
    if ($otherUserId === $userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot start conversation with yourself'
        ]);
        exit();
    }

    // Get other user info and validate they exist
    $stmt = $pdo->prepare("
        SELECT id, full_name, role, is_active, approval_status
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$otherUserId]);
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otherUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit();
    }

    // Validate user roles (one must be teacher, other must be parent)
    $isValidPair = (
        ($userRole === 'student' && $otherUser['role'] === 'parent') ||
        ($userRole === 'parent' && $otherUser['role'] === 'student')
    );

    if (!$isValidPair) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Conversations can only be started between teachers and parents',
            'debug' => [
                'current_user_role' => $userRole,
                'other_user_role' => $otherUser['role']
            ]
        ]);
        exit();
    }

    // Check if other user is active and approved
    if (!$otherUser['is_active'] || $otherUser['approval_status'] !== 'approved') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot start conversation with this user'
        ]);
        exit();
    }

    // Determine teacher_id and parent_id
    $teacherId = ($userRole === 'student') ? $userId : $otherUserId;
    $parentId = ($userRole === 'parent') ? $userId : $otherUserId;

    // ENHANCED ERROR LOGGING
    error_log("Creating conversation: teacher_id=$teacherId, parent_id=$parentId");

    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations
        WHERE teacher_id = ? AND parent_id = ?
    ");
    $stmt->execute([$teacherId, $parentId]);
    $existingConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingConversation) {
        // Return existing conversation
        echo json_encode([
            'success' => true,
            'data' => [
                'conversation_id' => (int)$existingConversation['id'],
                'is_new' => false
            ],
            'message' => 'Conversation already exists'
        ]);
        exit();
    }

    // Create new conversation with explicit error handling
    try {
        $stmt = $pdo->prepare("
            INSERT INTO conversations (teacher_id, parent_id, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        $result = $stmt->execute([$teacherId, $parentId]);

        if (!$result) {
            throw new Exception("Insert failed but no PDO exception thrown");
        }

        $conversationId = $pdo->lastInsertId();

        if (!$conversationId) {
            throw new Exception("No conversation ID returned after insert");
        }

        error_log("Conversation created successfully: ID=$conversationId");

        echo json_encode([
            'success' => true,
            'data' => [
                'conversation_id' => (int)$conversationId,
                'is_new' => true,
                'other_user' => [
                    'id' => (int)$otherUser['id'],
                    'name' => $otherUser['full_name'],
                    'role' => $otherUser['role']
                ]
            ],
            'message' => 'Conversation created successfully'
        ]);

    } catch (PDOException $insertError) {
        // Log the specific SQL error
        error_log("SQL Error in conversation insert: " . $insertError->getMessage());
        error_log("Error Code: " . $insertError->getCode());
        error_log("SQL State: " . $insertError->errorInfo[0]);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create conversation',
            'debug' => [
                'sql_error' => $insertError->getMessage(),
                'error_code' => $insertError->getCode()
            ]
        ]);
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error in messages/start.php: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in messages/start.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred',
        'debug' => [
            'message' => $e->getMessage()
        ]
    ]);
}
