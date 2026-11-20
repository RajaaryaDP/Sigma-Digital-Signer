<?php

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    exit('Forbidden: CLI only.');
}

$name = $argv[1] ?? null;

if (!$name || !preg_match('/^[A-Za-z]+$/', $name)) {
    fwrite(STDERR, "Usage: composer makeMVC <Name>\n");
    fwrite(STDERR, "Example: composer makeMVC Categories\n");
    exit(1);
}

$name  = ucfirst($name);
$lower = strtolower($name);

$root = dirname(__DIR__);

$modelPath      = "$root/models/{$name}Model.php";
$controllerPath = "$root/controllers/{$name}.php";
$viewDir        = "$root/views/{$lower}";
$indexViewPath  = "$viewDir/index.php";
$formViewPath   = "$viewDir/form.php";

$conflicts = [];
if (is_file($modelPath))      $conflicts[] = $modelPath;
if (is_file($controllerPath)) $conflicts[] = $controllerPath;
if (is_dir($viewDir))         $conflicts[] = $viewDir;

if (!empty($conflicts)) {
    fwrite(STDERR, "Stopped -- these already exist:\n");
    foreach ($conflicts as $path) {
        fwrite(STDERR, "  - $path\n");
    }
    fwrite(STDERR, "Delete them manually first if you want to regenerate.\n");
    exit(1);
}

$modelTemplate = <<<'EOT'
<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class {{NAME}}Model
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM {{LOWER}} ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {{LOWER}} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create($data)
    {
        $id = Uuid::uuid4()->toString();

        $stmt = $this->db->prepare("INSERT INTO {{LOWER}} (id, name) VALUES (?, ?)");
        $stmt->execute([$id, $data['name']]);

        return $id;
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE {{LOWER}} SET name = ? WHERE id = ?");
        return $stmt->execute([$data['name'], $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {{LOWER}} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

EOT;

$controllerTemplate = <<<'EOT'
<?php

require_once __DIR__ . '/../models/{{NAME}}Model.php';

class {{NAME}}
{
    private $model;
    private $load;

    public function __construct()
    {
        $this->model = new {{NAME}}Model();
        $this->load  = new Loader();
    }

    private function validate($data)
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Name is required.';
        }

        return $errors;
    }

    public function index()
    {
        ${{LOWER}} = $this->model->getAll();
        $this->load->view('views/{{LOWER}}/index.php', [
            '{{LOWER}}' => ${{LOWER}},
        ]);
    }

    public function create()
    {
        $isEdit = false;
        $this->load->view('views/{{LOWER}}/form.php', [
            'isEdit' => $isEdit,
        ]);
    }

    public function store()
    {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $isEdit = false;
            $this->load->view('views/{{LOWER}}/form.php', [
                'isEdit' => $isEdit,
                'errors' => $errors,
            ]);
            return;
        }

        $this->model->create([
            'name' => trim($_POST['name']),
        ]);

        header('Location: ' . BASE_URL . '/{{LOWER}}');
        exit;
    }

    public function edit($id)
    {
        ${{LOWER}} = $this->model->getById($id);

        if (${{LOWER}} === null) {
            http_response_code(404);
            echo '404 - {{NAME}} not found';
            return;
        }

        $isEdit = true;
        $this->load->view('views/{{LOWER}}/form.php', [
            'isEdit'    => $isEdit,
            'id'        => $id,
            '{{LOWER}}' => ${{LOWER}},
        ]);
    }

    public function update($id)
    {
        if ($this->model->getById($id) === null) {
            http_response_code(404);
            echo '404 - {{NAME}} not found';
            return;
        }

        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $isEdit = true;
            ${{LOWER}} = $this->model->getById($id);
            $this->load->view('views/{{LOWER}}/form.php', [
                'isEdit'    => $isEdit,
                'id'        => $id,
                '{{LOWER}}' => ${{LOWER}},
                'errors'    => $errors,
            ]);
            return;
        }

        $this->model->update($id, [
            'name' => trim($_POST['name']),
        ]);

        header('Location: ' . BASE_URL . '/{{LOWER}}');
        exit;
    }

    public function delete($id)
    {
        if ($this->model->getById($id) === null) {
            http_response_code(404);
            echo '404 - {{NAME}} not found';
            return;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/{{LOWER}}');
        exit;
    }
}

EOT;

$indexViewTemplate = <<<'EOT'
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{NAME}}</h4>
    <a href="<?= BASE_URL ?>/{{LOWER}}/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add {{NAME}}
    </a>
</div>

<?php if (empty(${{LOWER}})): ?>
    <div class="card text-center p-5">
        <i class="bi bi-inbox text-muted" style="font-size: 2.5rem;"></i>
        <p class="mt-3 mb-3 text-muted">No {{LOWER}} found yet.</p>
        <a href="<?= BASE_URL ?>/{{LOWER}}/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add {{NAME}}
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (${{LOWER}} as $row): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark font-monospace fw-normal"><?= substr($row['id'], 0, 8) ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/{{LOWER}}/<?= $row['id'] ?>/edit" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="<?= BASE_URL ?>/{{LOWER}}/<?= $row['id'] ?>/delete" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure?')">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

EOT;

$formViewTemplate = <<<'EOT'
<?php
$values = ${{LOWER}} ?? [];

require_once __DIR__ . '/../layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Form {{NAME}}</h4>
            </div>

            <div class="card-body">
                <form action="<?= $isEdit ? BASE_URL . '/{{LOWER}}/' . $id . '/update' : BASE_URL . '/{{LOWER}}/store' ?>" method="POST">

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($values['name'] ?? '') ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Save' ?></button>
                        <a href="<?= BASE_URL ?>/{{LOWER}}" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

EOT;

function render($template, $name, $lower)
{
    return str_replace(['{{NAME}}', '{{LOWER}}'], [$name, $lower], $template);
}

mkdir($viewDir, 0755, true);

file_put_contents($modelPath, render($modelTemplate, $name, $lower));
file_put_contents($controllerPath, render($controllerTemplate, $name, $lower));
file_put_contents($indexViewPath, render($indexViewTemplate, $name, $lower));
file_put_contents($formViewPath, render($formViewTemplate, $name, $lower));

echo "Created:\n";
echo "  - models/{$name}Model.php\n";
echo "  - controllers/{$name}.php\n";
echo "  - views/{$lower}/index.php\n";
echo "  - views/{$lower}/form.php\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Create the '{$lower}' table: CREATE TABLE {$lower} (id VARCHAR(36) PRIMARY KEY, name VARCHAR(150) NOT NULL);\n";
echo "  2. Add a nav link in views/layout/header.php if you want it in the sidebar.\n";
