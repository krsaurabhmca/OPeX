<?php require_once("all_header.php"); 
$folder = "../upload/"; // specify the folder where files are stored
?>

<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3">Document Manager</h1>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-7">
                            <!-- Upload Form -->
                            <form action="" method="post" enctype="multipart/form-data">
                                <b>Upload New File</b><br>
                                <input type="file" name="file" class="form-control-file" required>
                                <button type="submit" name="upload" class="btn btn-success btn-sm mt-2">Upload</button>
                            </form>
                        </div>

                        <div class="col-md-5">
                            <!-- View Toggle Buttons -->
                            <div class="view-toggle">
                                <button class="btn btn-info btn-sm" id="listView">List View</button>
                                <button class="btn btn-info btn-sm" id="gridView">Grid View</button>
                            </div>
                            <!-- Rename Form -->
                            <?php
                            if (isset($_GET['action']) && $_GET['action'] == 'rename' && isset($_GET['file'])) {
                                $fileToRename = $_GET['file'];
                                echo '<b>Rename File: ' . $fileToRename . '</b>';
                            ?>
                                <form action="?action=rename&file=' . $fileToRename . '" method="post" class="form-inline">
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control input-sm" name="newName" placeholder="Enter new file name" required>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">Rename</button>
                                        </div>
                                    </div>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="file-container" class="list-view">
                        <?php
                        // Display files
                        function displayFiles($folder) {
                            global $base_url;
                            $files = scandir($folder);
                            foreach ($files as $file) {
                                if ($file != '.' && $file != '..') {
                                    echo '<div class="file-item">';
                                    echo '<div class="thumbnail">';
                                    echo '<img src="' . $folder . $file . '" alt="' . $file . '">';
                                    echo '</div>';
                                    echo '<div class="file-info">';
                                    echo '<div class="file-name">' . $file . '</div>';
                                    echo '<div class="file-actions">
                                        <span data-url="' . $base_url . 'upload/' . $file . '" class="docs_link btn btn-dark btn-sm" title="Copy">
                                            <i class="fa fa-link"></i>
                                        </span>
                                        <a href="?action=rename&file=' . $file . '" class="btn btn-primary btn-sm" title="Rename">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <a href="?action=delete&file=' . $file . '" class="btn btn-danger btn-sm" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                      </div>';
                                    echo '</div></div>';
                                }
                            }
                        }

                        displayFiles($folder);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once("footer.php"); ?>

<script>
// JavaScript to toggle between grid and list view
document.getElementById("gridView").addEventListener("click", function() {
    document.getElementById("file-container").classList.add("grid-view");
    document.getElementById("file-container").classList.remove("list-view");
});

document.getElementById("listView").addEventListener("click", function() {
    document.getElementById("file-container").classList.add("list-view");
    document.getElementById("file-container").classList.remove("grid-view");
});

$(document).on("click",".docs_link",function(){
	var x = $(this).data('url');
	navigator.clipboard.writeText(x);
	notyf("URL Copied Successfully ","success");
});
</script>

<style>
/* Common styles for both views */
#file-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    margin-bottom: 10px;
}

.thumbnail img {
    width: 100px;
    height: 100px;
    object-fit: cover;
}

.file-info {
    display: flex;
    flex-direction: column;
}

.file-actions {
    display: flex;
    gap: 5px;
}

/* Grid view specific styles */
.grid-view .file-item {
    flex-direction: column;
    align-items: center;
    width: calc(25% - 20px);
}

/* List view specific styles */
.list-view .file-item {
    flex-direction: row;
    width: 100%;
}

</style>
