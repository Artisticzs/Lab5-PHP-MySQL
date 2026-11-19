<?php
require_once 'db.php';

$errors = [];
$editId = null;
$formName = '';
$formDescription = '';

function validateTask($name, $description) {
    $errors = [];

    if ($name === '') {
        $errors[] = "Task Name cannot be empty.";
    } elseif (strlen($name) < 3) {
        $errors[] = "Task Name must be at least 3 characters long.";
    }

    if ($description === '') {
        $errors[] = "Task Description is required.";
    } elseif (strlen($description) > 255) {
        $errors[] = "Task Description cannot exceed 255 characters.";
    }

    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];

        if (ctype_digit($id)) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([(int)$id]);
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['toggle_id'])) {
        $id = $_POST['toggle_id'];

        if (ctype_digit($id)) {
            $stmt = $pdo->prepare(
                "UPDATE tasks SET status = IF(status = 'pending', 'completed', 'pending') WHERE id = ?"
            );
            $stmt->execute([(int)$id]);
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $taskName = trim($_POST['task_name'] ?? '');
    $taskDescription = trim($_POST['task_description'] ?? '');
    $errors = validateTask($taskName, $taskDescription);

    if (isset($_POST['update_id'])) {
        $id = $_POST['update_id'];

        if (!ctype_digit($id)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE tasks SET task_name = ?, task_description = ? WHERE id = ?");
            $stmt->execute([$taskName, $taskDescription, (int)$id]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        $editId = (int)$id;
    }
    else {
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO tasks (task_name, task_description) VALUES (?, ?)");
            $stmt->execute([$taskName, $taskDescription]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    $formName = $taskName;
    $formDescription = $taskDescription;

} elseif (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    if (ctype_digit($id)) {
        $stmt = $pdo->prepare("SELECT task_name, task_description FROM tasks WHERE id = ?");
        $stmt->execute([(int)$id]);
        $task = $stmt->fetch();

        if ($task) {
            $editId = (int)$id;
            $formName = $task['task_name'];
            $formDescription = $task['task_description'];
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM tasks ORDER BY created_at DESC, id DESC");
$stmt->execute();
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>To-Do List</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1>My To-Do List</h1>
  </header>

  <main>
    <section>
      <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php endforeach; ?>

      <form method="POST" id="task-form">
        <?php if ($editId !== null): ?>
          <input type="hidden" name="update_id" value="<?= $editId ?>">
        <?php endif; ?>

        <label for="task_name">Task Name:</label>
        <input type="text" id="task_name" name="task_name"
               value="<?= htmlspecialchars($formName, ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="task_description">Task Description:</label>
        <input type="text" id="task_description" name="task_description"
               value="<?= htmlspecialchars($formDescription, ENT_QUOTES, 'UTF-8') ?>" required>

        <button type="submit" name="submit">
          <?= $editId !== null ? 'Save Changes' : 'Add Task' ?>
        </button>

        <?php if ($editId !== null): ?>
          <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="cancel-link">Cancel</a>
        <?php endif; ?>
      </form>
    </section>

    <section>
      <h2 class="list-title">Your Tasks (<?= count($tasks) ?>)</h2>

      <?php if (empty($tasks)): ?>
        <p class="empty">No tasks yet. Add your first one! 🐱</p>
      <?php endif; ?>

      <ul id="task-list">
        <?php foreach ($tasks as $task): ?>
          <?php $isDone = $task['status'] === 'completed'; ?>
          <li class="task-item<?= $isDone ? ' completed' : '' ?>">
            <span>
              <strong><?= htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8') ?></strong>
              <?= htmlspecialchars($task['task_description'], ENT_QUOTES, 'UTF-8') ?>
              <em class="status <?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(ucfirst($task['status']), ENT_QUOTES, 'UTF-8') ?>
              </em>
            </span>
            <div class="actions">
              <form method="POST" class="toggle-form">
                <input type="hidden" name="toggle_id" value="<?= (int)$task['id'] ?>">
                <button type="submit" class="toggle-btn"><?= $isDone ? 'Undo' : 'Done' ?></button>
              </form>
              <a href="?edit=<?= (int)$task['id'] ?>" class="edit-btn">Edit</a>
              <form method="POST" class="delete-form">
                <input type="hidden" name="delete_id" value="<?= (int)$task['id'] ?>">
                <button type="submit" class="delete-btn">Delete</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>

  <script src="script.js"></script>
</body>
</html>
