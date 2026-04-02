<?php
require_once '../config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = (int) $_SESSION['user_id'];
$current_user = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    if ($action === 'list') {
        echo json_encode(['success' => true, 'projects' => listDiagramProjects($user_id)]);
        exit;
    }

    if ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $schema = (string) ($_POST['schema'] ?? '');
        $project_id = trim((string) ($_POST['project_id'] ?? ''));

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Tên project không được để trống']);
            exit;
        }

        $saved = saveDiagramProject($user_id, $name, $schema, $project_id);
        echo json_encode(['success' => true, 'project' => $saved]);
        exit;
    }

    if ($action === 'delete') {
        $project_id = trim((string) ($_POST['project_id'] ?? ''));
        if ($project_id === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu project_id']);
            exit;
        }

        $deleted = deleteDiagramProject($user_id, $project_id);
        echo json_encode(['success' => $deleted]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Diagram Builder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dbdiagram.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="diagram-page">
        <section class="toolbar">
            <div>
                <h1>DB Diagram Builder</h1>
                <p>Mô phỏng chức năng cơ bản kiểu dbdiagram.io: nhập schema, xem preview, lưu JSON.</p>
            </div>
            <div class="toolbar-actions">
                <input id="projectName" type="text" placeholder="Tên project..." maxlength="120">
                <button id="newBtn" class="btn secondary">New</button>
                <button id="saveBtn" class="btn primary">Save JSON</button>
                <button id="deleteBtn" class="btn danger" disabled>Delete</button>
            </div>
        </section>

        <section class="workspace">
            <aside class="left-panel">
                <div class="panel-title">Schema (DBML-lite)</div>
                <textarea id="schemaInput" spellcheck="false">Table users {
  id int [pk]
  email varchar [unique]
  full_name varchar
  created_at datetime
}

Table posts {
  id int [pk]
  user_id int [ref: > users.id]
  title varchar
  content text
}

Ref: posts.user_id > users.id</textarea>
                <div id="statusText" class="status-text">Sẵn sàng.</div>
            </aside>

            <section class="right-panel">
                <div class="panel-title">Diagram preview</div>
                <div id="diagramCanvas" class="diagram-canvas"></div>
                <div class="panel-title relation-title">Relations</div>
                <ul id="relationList" class="relation-list"></ul>
            </section>

            <aside class="projects-panel">
                <div class="panel-title">Projects đã lưu</div>
                <ul id="projectList" class="project-list"></ul>
            </aside>
        </section>
    </main>

    <script>
        window.DB_DIAGRAM_USER_ID = <?= json_encode($user_id) ?>;
    </script>
    <script src="../assets/js/dbdiagram.js"></script>
</body>

</html>
