<?php
// ============================================================
// RESTful API for Task CRUD Operations
// ============================================================
require_once 'config.php';

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db  = getDB();
$sid = $_SESSION['student_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        // ── READ ───────────────────────────────────────
        case 'GET':
            $filter = $_GET['status'] ?? 'All';
            $sql = "SELECT * FROM tasks WHERE student_id = ?";
            $params = [$sid];

            if ($filter !== 'All' && in_array($filter, ['Pending', 'Ongoing', 'Completed'])) {
                $sql .= " AND status = ?";
                $params[] = $filter;
            }

            $sql .= " ORDER BY 
                        CASE status 
                            WHEN 'Ongoing'   THEN 1 
                            WHEN 'Pending'   THEN 2 
                            WHEN 'Completed' THEN 3 
                        END, 
                        deadline ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();

            // Also fetch summary counts
            $cStmt = $db->prepare("
                SELECT 
                    COUNT(*)                   as total,
                    SUM(status = 'Pending')    as pending,
                    SUM(status = 'Ongoing')    as ongoing,
                    SUM(status = 'Completed')  as completed
                FROM tasks WHERE student_id = ?
            ");
            $cStmt->execute([$sid]);
            $counts = $cStmt->fetch();

            echo json_encode([
                'success' => true,
                'tasks'   => $tasks,
                'counts'  => $counts
            ]);
            break;

        // ── CREATE ─────────────────────────────────────
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            $title    = trim($data['title'] ?? '');
            $desc     = trim($data['description'] ?? '');
            $deadline = $data['deadline'] ?? '';
            $status   = $data['status'] ?? 'Pending';

            if (empty($title) || empty($deadline)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and deadline are required.']);
                exit;
            }

            if (!in_array($status, ['Pending', 'Ongoing', 'Completed'])) {
                $status = 'Pending';
            }

            $stmt = $db->prepare("
                INSERT INTO tasks (student_id, title, description, deadline, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sid, $title, $desc, $deadline, $status]);

            echo json_encode([
                'success' => true,
                'message' => 'Task created successfully.',
                'id'      => $db->lastInsertId()
            ]);
            break;

        // ── UPDATE ─────────────────────────────────────
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);

            $id       = intval($data['id'] ?? 0);
            $title    = trim($data['title'] ?? '');
            $desc     = trim($data['description'] ?? '');
            $deadline = $data['deadline'] ?? '';
            $status   = $data['status'] ?? 'Pending';

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid task ID.']);
                exit;
            }

            // If only status is being updated (quick status change)
            if (!empty($data['status_only'])) {
                $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND student_id = ?");
                $stmt->execute([$status, $id, $sid]);
            } else {
                if (empty($title) || empty($deadline)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Title and deadline are required.']);
                    exit;
                }

                $stmt = $db->prepare("
                    UPDATE tasks 
                    SET title = ?, description = ?, deadline = ?, status = ?
                    WHERE id = ? AND student_id = ?
                ");
                $stmt->execute([$title, $desc, $deadline, $status, $id, $sid]);
            }

            echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
            break;

        // ── DELETE ─────────────────────────────────────
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid task ID.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND student_id = ?");
            $stmt->execute([$id, $sid]);

            echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
