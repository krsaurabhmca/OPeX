
<?php require_once("function.php");

// Path to JSON file
$jsonFile = 'data.json';

// Function to get data from JSON file
function getData()
{
    global $jsonFile;
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        return json_decode($jsonData, true);
    }
    return [];
}

// Function to save data to JSON file
function saveData($data)
{
    global $jsonFile;
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($jsonFile, $jsonData);
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    // Fetch data from file
    $data = getData();

    // CREATE
    if ($action == 'create') {
        $newData = ['id' => uniqid()] + $_POST['fields']; // Merge new unique ID
        array_push($data, $newData);
        saveData($data);
        header('Location: ' . $_SERVER['PHP_SELF']);
    }

    // UPDATE
    if ($action == 'update') {
        $id = $_POST['id'];
        foreach ($data as &$record) {
            if ($record['id'] == $id) {
                $record = ['id' => $id] + $_POST['fields'];
                saveData($data);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }

    // DELETE
    if ($action == 'delete') {
        $id = $_POST['id'];
        foreach ($data as $key => $record) {
            if ($record['id'] == $id) {
                unset($data[$key]);
                saveData($data);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

$data = getData();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generic CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <h1>Generic CRUD Operations</h1>
    <hr>

    <!-- Data Table -->
    <h3>Current Data</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <?php if (!empty($data)) : ?>
                    <?php foreach (array_keys($data[0]) as $key): ?>
                        <th><?= ucfirst($key) ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                <?php else : ?>
                    <th>No Data Available</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $record): ?>
                <tr>
                    <?php foreach ($record as $value): ?>
                        <td><?= htmlspecialchars($value) ?></td>
                    <?php endforeach; ?>
                    <td>
                        <!-- Edit Button triggers the modal -->
                        <button class="btn btn-primary" onclick="openEditModal('<?= htmlspecialchars(json_encode($record)) ?>')">Edit</button>
                        <!-- Delete form -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $record['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Create New Record -->
    <h3>Create New Record</h3>
    <form method="POST">
        <div id="createForm" class="row">
            <?php if (!empty($data)): ?>
                <?php foreach (array_keys($data[0]) as $key): ?>
                    <?php if ($key != 'id'): ?>
                        <div class="col-md-4">
                            <label><?= ucfirst($key) ?>:</label>
                            <input class="form-control" type="text" name="fields[<?= $key ?>]" required>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <input type="hidden" name="action" value="create">
        <button class="btn btn-success mt-3" type="submit">Add Record</button>
    </form>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div id="editForm"></div>
                        <input type="hidden" name="id" id="editId">
                        <input type="hidden" name="action" value="update">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(record) {
            const recordData = JSON.parse(record);
            const formContainer = document.getElementById('editForm');
            formContainer.innerHTML = '';
            document.getElementById('editId').value = recordData.id;

            for (const [key, value] of Object.entries(recordData)) {
                if (key !== 'id') {
                    formContainer.innerHTML += `
                        <div class="mb-3">
                            <label>${key.charAt(0).toUpperCase() + key.slice(1)}:</label>
                            <input class="form-control" type="text" name="fields[${key}]" value="${value}" required>
                        </div>
                    `;
                }
            }
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }
    </script>
</body>
</html>
