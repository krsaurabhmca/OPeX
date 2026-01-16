<?php
require_once('op_config.php');
use PHPMailer\PHPMailer\PHPMailer;
global $db_name;
$con = mysqli_connect($host_name, $db_user, $db_password, $db_name)
	or die("Unable to Connect, Check the Connection Parameter. " . mysqli_error($con));

// === OFFERPLANT MASTER FUNTION FOR EVERY WHERE ==== //

//  INSERT ( insert_row, insert_data, insert_html )
// 	UPDATE (update_date, update_multi_data)
// 	REMOVE (remove_data, remove_multi_data)
// 	DELETE (delete_data, delete_multi_data)
//	FETCH	(get_data, get_all, get_multi_data, get_not, direct_sql)
//	CRYPTO (encode, decode)
//	STRING (rnd_str, add_space, remove_space)
//	SECURITY (xss_clean, post_clean)r
//	ACCESS	(verify, verify_request)
//	EXCEL 	(csv_import, csv_export)
//	YOUTUBE ( ytid, get_vid)
// 	COMM	(send_msg, send_sms, rtfmail ,wasend )
//	API 	(api_call)
//	QRcode	(qrcode)
//	IMAGE 	(uploadimg, remote_file_size, remote_file_exists)
// 	DATABASE Sturucture (table_list, Create_table, direct_sql_file,add_column, remove_column)
// 	CONFIG 	(set_config, update_config,delete_config, all_config,all_back_images, get_config)
//	HTML 	(input_text, input_date, btn_view, btn_about, btn_edit, btn_delete, create_data_table, create_form,  ) 
//	UI DROPDOWN (dropdown, dropdown_list, dropdown_list_multiple, dropdown_list_where,  create_list)

// Create Table with Basic Structure  

function create_table($table_name)
{
    global $con; // Use the global $con variable to interact with the database

    // Start a MySQL transaction
    mysqli_begin_transaction($con);
    try {
        // Step 1: Create the table if it doesn't exist
        $sql1 = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `id` INT(11) NOT NULL,
            `status` VARCHAR(25) DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_by` INT(11) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($con, $sql1)) {
            throw new Exception("Error in creating table: " . mysqli_error($con));
        }

        // Step 2: Add a primary key to the 'id' column
        $sql2 = "ALTER TABLE `$table_name` ADD PRIMARY KEY (`id`)";
        if (!mysqli_query($con, $sql2)) {
            throw new Exception("Error in assigning primary key: " . mysqli_error($con));
        }

        // Step 3: Modify the 'id' column to be AUTO_INCREMENT
        $sql3 = "ALTER TABLE `$table_name` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT";
        if (!mysqli_query($con, $sql3)) {
            throw new Exception("Error in setting AUTO_INCREMENT for ID: " . mysqli_error($con));
        }

        // Step 4: Modify the 'updated_at' column to update automatically on row update
        $sql4 = "ALTER TABLE `$table_name` CHANGE `updated_at` `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        if (!mysqli_query($con, $sql4)) {
            throw new Exception("Error in setting default value for 'updated_at': " . mysqli_error($con));
        }

        // If everything is successful, commit the transaction
        mysqli_commit($con);
        return "Table created and modified successfully.";

    } catch (Exception $e) {
        // If any error occurs, rollback the transaction
        mysqli_rollback($con);
        return "Transaction failed: " . $e->getMessage();
    }
}


// Convert Any Table into Opex table 

function check_table($table_name)
{
    global $con;

    // Begin transaction
    mysqli_begin_transaction($con);

    try {
        // Removing Auto Increment Features
        $sql61 = "SHOW COLUMNS FROM $table_name WHERE Extra = 'auto_increment'";
        $res61 = direct_sql($sql61);
        if ($res61['count'] > 0) {
            $col_name = $res61['data'][0]['Field'];
            $sql61_1 = "ALTER TABLE $table_name MODIFY $col_name INT";
            mysqli_query($con, $sql61_1);
        }

        // Removing Primary Key
        $sql60 = "SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'";
        $res60 = direct_sql($sql60);
        if ($res60['count'] > 0) {
            $sql60_1 = "ALTER TABLE $table_name DROP PRIMARY KEY";
            mysqli_query($con, $sql60_1);
        }

        // Define columns to check and their attributes
        $columns = [
            'id' => "INT NOT NULL AUTO_INCREMENT",
            'status' => "VARCHAR(50) DEFAULT 'ACTIVE'",
            'created_at' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'created_by' => "INT NULL",
            'updated_by' => "INT NULL",
        ];

        foreach ($columns as $column => $attributes) {
            $query = "SHOW COLUMNS FROM $table_name LIKE '$column'";
            $res = direct_sql($query, 'set');
            if ($res['count'] == 0) {
                $sql = "ALTER TABLE $table_name ADD COLUMN $column $attributes";
                mysqli_query($con, $sql);
            }
        }

        // Assign Primary Key
        $sql6 = "ALTER TABLE $table_name MODIFY id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)";
        mysqli_query($con, $sql6);

        // Ensure updated_at has the correct attributes
        $sql8 = "ALTER TABLE $table_name CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        mysqli_query($con, $sql8);

        // Commit transaction
        mysqli_commit($con);
        return ['status' => 'success', 'message' => 'Table modified successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($con);
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}



// List of all Table Exist in databse 

function table_list()
{
    global $con; // Global database connection
    global $db_name; // Global database name

    // Ensure the database connection exists
    if (!$con) {
        die("Database connection failed. Please check the connection settings.");
    }

    // Initialize the result array
    $result = array();

    try {
        // Query to list all tables in the current database
        $res = mysqli_query($con, "SHOW TABLES") or throw new Exception("Error fetching table list: " . mysqli_error($con));

        $ct = mysqli_num_rows($res); // Get the count of tables

        // If there are tables in the database
        if ($ct >= 1) {
            while ($row = mysqli_fetch_assoc($res)) {
                // Dynamically get the table names based on the database name
                $data[] = $row['Tables_in_' . $db_name];
            }

            // Return success with table count and table data
            $result['count'] = $ct;
            $result['status'] = 'success';
            $result['data'] = $data;
        } else {
            // No tables found
            $result['count'] = 0;
            $result['status'] = 'error';
            $result['data'] = null;
        }

    } catch (Exception $e) {
        // Handle any exceptions during the query execution
        $result['count'] = 0;
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();
        $result['data'] = null;
    }

    return $result; // Return the result array
}


function column_list($table_name = 'users')
{
    global $con; // Global database connection
    global $db_name; // Global database name

    // Ensure the database connection exists
    if (!$con) {
        die("Database connection failed. Please check the connection settings.");
    }

    // Initialize the result array
    $result = array();
    $data = array();

    try {
        // SQL query to fetch column details from INFORMATION_SCHEMA
        $sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_DEFAULT, EXTRA 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA='$db_name' 
                AND TABLE_NAME='$table_name'";

        // Execute the query
        $res = mysqli_query($con, $sql) or throw new Exception("Error fetching column list: " . mysqli_error($con));

        $ct = mysqli_num_rows($res); // Get the count of columns

        // If columns are found for the table
        if ($ct >= 1) {
            while ($row = mysqli_fetch_assoc($res)) {
                // Collect all column information
                $data[] = $row;
            }

            // Return success with the count of columns and column data
            $result['count'] = $ct;
            $result['status'] = 'success';
            $result['data'] = $data;
        } else {
            // No columns found for the table
            $result['count'] = 0;
            $result['status'] = 'error';
            $result['data'] = null;
        }

    } catch (Exception $e) {
        // Handle any exceptions during the query execution
        $result['count'] = 0;
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();
        $result['data'] = null;
    }

    return $result; // Return the result array
}



function table_key_list($table_name = 'users')
{
	global $con;
	global $db_name;
	$result = array();
	$sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_DEFAULT,  EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name' and COLUMN_KEY in('PRI','UNI')";
	$res = mysqli_query($con, $sql) or die("Error in Creating Table List" . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	if ($ct >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	return $result;
}

function encode($data, $secret_key = 'your-secret-key')
{
    // Define method and initialization vector (IV)
    $method = 'aes-256-cbc';
    $key = hash('sha256', $secret_key); // Hash the secret key
    $iv = substr(hash('sha256', 'some_iv'), 0, 16); // Initialization vector (must be 16 bytes)

    // Convert array to query string if data is an array
    if (is_array($data)) {
        $data = http_build_query($data);
    }

    // Encrypt the data
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

    // Encode the encrypted data for URL safety
    return strtr(base64_encode($encrypted), '+/=', '._-');
}

function decode($input, $secret_key = 'your-secret-key')
{
    // Define method and initialization vector (IV)
    $method = 'aes-256-cbc';
    $key = hash('sha256', $secret_key); // Hash the secret key
    $iv = substr(hash('sha256', 'some_iv'), 0, 16); // Initialization vector (must be 16 bytes)

    // Decode the URL-safe string
    $decoded = base64_decode(strtr($input, '._-', '+/='));

    // Decrypt the data
    $decrypted = openssl_decrypt($decoded, $method, $key, 0, $iv);

    // Parse the decrypted string into an associative array
    parse_str($decrypted, $output);

    return $output;
}


// USE TO CREATE STRING REPLACE SPACE WITH UNDERSCORE FORM STRING 

function remove_space($str)
{
    // Check if input is empty
    if (empty($str)) {
        return ''; // Return empty string if input is empty
    }

    // Trim whitespace and sanitize the string
    $str = trim($str);

    // Replace non-alphanumeric characters (including spaces) with underscores
    $formatted_str = preg_replace("/[^a-zA-Z0-9]+/", "_", $str);

    // Convert to lowercase
    return strtolower($formatted_str);
}

function add_space($str)
{
    // Check if input is empty
    if (empty($str)) {
        return ''; // Return empty string if input is empty
    }

    // Trim any leading/trailing spaces
    $str = trim($str);

    // Replace underscores with spaces
    $formatted_str = str_replace('_', ' ', $str);

    // Capitalize the first letter of each word
    return ucwords($formatted_str);
}

// Function to check if a date is valid
function is_valid_date($date)
{
    $format = 'Y-m-d'; // Define the expected format
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// GET VIDEO ID FROM YOUTUBE LINK 

function rnd_str($length_of_string = 10, $char_set = 'alphanumeric')
{
    // Define different character sets
    $char_sets = [
        'alphanumeric' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        'alphabetic'   => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        'numeric'      => '0123456789',
        'hex'          => '0123456789ABCDEF',
        'symbols'      => '!@#$%^&*()_+-=[]{}|;:,.<>?'
    ];

    // Select the character set based on the provided option, defaulting to alphanumeric
    $selected_char_set = $char_sets[$char_set] ?? $char_sets['alphanumeric'];

    // Shuffle the selected character set and return a substring of the specified length
    return substr(str_shuffle($selected_char_set), 0, $length_of_string);
}

function xss_clean($data, $allowed_tags = '')
{
    // Fix &entity\n;
    $data = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

    // Remove any attributes starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

    // Remove dangerous protocols like javascript:, vbscript:, data:, etc.
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*data[\x00-\x20]*:#iu', '$1=$2nodata...', $data);

    // Remove IE-style expressions and behaviors
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

    // Remove namespaced elements (e.g., <xsl:script>)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    // Optionally remove dangerous tags but allow some basic ones if specified
    do {
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    } while ($old_data !== $data);

    // Strip remaining unwanted tags (if any) unless allowed
    return strip_tags($data, $allowed_tags);
}

function post_clean(&$arr_data)
{
    if (is_array($arr_data)) {
        foreach ($arr_data as &$data) {
            if (is_array($data)) {
                post_clean($data);  // Recursively clean sub-arrays
            } else {
                $data = xss_clean($data);  // Clean individual elements
            }
        }
    } else {
        $arr_data = xss_clean($arr_data);  // Clean non-array values
    }
    return $arr_data;
}

function array_to_string($arr_data)
{
	if (is_array($arr_data)) {
		foreach((array)$arr_data as $data) {

			$key = array_search($data, $arr_data);
			if (is_array($data)) {
				$arr_data[$key] = implode(",",$data);
			}
		}
	}
	return $arr_data;
}

function verify_request()
{
    // Specify allowed referrer hosts
    $allowed_hosts = [
        'yourdomain.com',
        'sub.yourdomain.com', // Add more trusted subdomains as needed
    ];

    // Check if the HTTP_REFERER is set
    if (empty($_SERVER["HTTP_REFERER"])) {
        return false; // No referrer means potential CSRF
    }

    // Parse the referrer URL
    $ref = parse_url($_SERVER["HTTP_REFERER"]);
    
    // Validate the host against allowed hosts
    if (isset($ref['host']) && in_array($ref['host'], $allowed_hosts)) {
        return true; // Request is valid
    }

    return false; // Invalid request origin
}

function verify($user_type)
{
    // Define a base URL for authorization checks
    $actual_link = "http://" . $_SERVER['HTTP_HOST'];
    $current_page = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);

    // Set user roles in a more secure way, possibly fetching from a database or configuration
    $user_roles = [
        'ADMIN' => $admin_role ?? [], // Ensure these are defined somewhere securely
        'CLIENT' => $client_role ?? [],
    ];

    // Validate the user type and fetch permissions
    if (!array_key_exists($user_type, $user_roles)) {
        throw new Exception("Invalid User Type! Don't Have Permission");
    }

    $all_page = $user_roles[$user_type];

    // Check for permission
    if (!in_array($current_page, $all_page)) {
        throw new Exception("Don't have Permission to access this page");
    }

    return true; // Access granted
}


	
function format_interval(DateInterval $interval) {
    $result = "";
    if ($interval->y) { $result .= $interval->format("%yy "); }
    if ($interval->m and $interval->y <1) { $result .= $interval->format("%mmonth "); }
    if ($interval->d and $interval->m <1) { $result .= $interval->format("%dd "); }
    if ($interval->h and $interval->d <1) { $result .= $interval->format("%hh "); }
    if ($interval->i and $interval->h <1) { $result .= $interval->format("%im "); }
    if ($interval->s and $interval->i <1) { $result .= $interval->format("%ss"); }
    
    return $result;
}


function date_difference($startDate, $endDate = null)
{
    // If the end date is not provided, use the current date
    if ($endDate === null) {
        $endDate = date('Y-m-d H:i:s');
    }

    // Create DateTime objects
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);

    // Calculate the difference
    $difference = $start->diff($end);

    // Format the interval into a human-readable string
    return format_interval($difference);
}

// TO ADD COLUMN IN TABLE 

/**
 * Add a column to a specified table if it does not already exist.
 *
 * @param string $table_name The name of the table.
 * @param string $col_name The name of the column to add.
 * @param string $data_type The data type of the column (default is 'varchar(255)').
 * @param mixed $default The default value for the column (optional).
 * @throws Exception Throws an exception if the operation fails.
 */
function add_column($table_name, $col_name, $data_type = 'varchar(255)', $default = null)
{
    global $con;

    // Check if the column already exists
    $sql = "SHOW COLUMNS FROM `$table_name` LIKE '$col_name'";
    $result = direct_sql($sql);

    if ($result['count'] == 0) {
        // Construct the SQL statement for adding the column
        $default_sql = $default !== null ? "DEFAULT '$default'" : "";
        $sql = "ALTER TABLE `$table_name` ADD COLUMN `$col_name` $data_type $default_sql";

        if (mysqli_query($con, $sql)) {
            create_log($sql);
        } else {
            throw new Exception("Error in Adding Column: " . mysqli_error($con));
        }
    }
}

/**
 * Remove a column from a specified table if it exists.
 *
 * @param string $table_name The name of the table.
 * @param string $col_name The name of the column to remove.
 * @throws Exception Throws an exception if the operation fails or if the user does not have permission.
 */
function remove_column($table_name, $col_name)
{
    global $con;
    
    // Check user permissions
    $user_type = $_SESSION['user_type'] ?? null;
    if (! in_array($user_type, ["ADMIN","DEV"])) {
        throw new Exception("Permission Denied: Only ADMIN can delete columns.");
    }

    // Construct the SQL statement for removing the column
    $sql = "ALTER TABLE `$table_name` DROP COLUMN `$col_name`";

    if (mysqli_query($con, $sql)) {
        create_log($sql);
    } else {
        throw new Exception("Error in Removing Column: " . mysqli_error($con));
    }
}


// Function to check and update column settings in a table
function update_column($table_name, $col_name, $data_type = 'VARCHAR(255)', $default = null)
{
    global $con;

    // Prepare the default value SQL
    $default_sql = ($default === null) ? "" : " DEFAULT '$default'";
    
    // Check if the column exists
    $exist = mysqli_query($con, "SHOW COLUMNS FROM `$table_name` LIKE '$col_name'");
    
    if (mysqli_num_rows($exist) > 0) {
        // Column exists - Alter column settings
        $sql = "ALTER TABLE `$table_name` MODIFY `$col_name` $data_type $default_sql";
    } else {
        // Column does not exist - Add new column
        $sql = "ALTER TABLE `$table_name` ADD `$col_name` $data_type $default_sql";
    }
    
    // Execute the query with error handling
    if (mysqli_query($con, $sql)) {
       $res['status'] ='success'; 
       $res['msg'] = "Column '$col_name' in table '$table_name' has been updated/added successfully.";
    } else {
        $res['status'] ='error';
        $res['msg'] = "Error in updating/adding column '$col_name': " . mysqli_error($con);
    }
    return $res;
}


/**
 * Inserts a blank row into the specified table and returns the ID of the inserted or last existing row.
 *
 * @param string $table_name The name of the table to insert into.
 * @return array An associative array containing the table name and ID of the row.
 * @throws Exception Throws an exception if the operation fails.
 */
function insert_row($table_name)
{
    global $con;
    global $user_id;
    global $current_date_time;

    // Get the most recent entry with status 'AUTO' created by the user
    $result = get_multi_data($table_name, ['created_by' => $user_id, 'status' => 'AUTO'], ' ORDER BY id DESC LIMIT 1');

    if ($result['count'] < 1) {
        // If no existing row, insert a new one
        $insert_result = insert_data($table_name, [
            'status' => 'AUTO',
            'created_by' => $user_id, // Assuming you want to track the creator
            'created_at' => $current_date_time
        ]);

        if ($insert_result['status'] !== 'success') {
            throw new Exception("Error inserting data into $table_name: " . $insert_result['sql']);
        }
        
        $id = $insert_result['id'];
    } else {
        // If an existing row is found, use its ID
        $id = $result['data'][0]['id'];
    }

    return [
        'table' => $table_name,
        'id' => $id
    ];
}



/**
 * Inserts data into the specified table and returns the result.
 *
 * @param string $table_name The name of the table to insert data into.
 * @param array $ArrayData Associative array of column names and their corresponding values.
 * @return array An associative array containing the ID of the inserted row and status message.
 * @throws Exception Throws an exception if the operation fails.
 */
function insert_data($table_name, $ArrayData)
{
    global $con;
    global $user_id;
    global $current_date_time;

    // Prepare the data to be inserted
    $ArrayData['created_by'] = $user_id;
    $ArrayData['created_at'] = $current_date_time;

    // Build the SQL query
    $columns = implode(", ", array_keys($ArrayData));
    $values = implode(", ", array_map(function($value) {
        return "'" . post_clean($value) . "'"; // Ensure values are cleaned
    }, array_values($ArrayData)));

    $sql = "INSERT IGNORE INTO `$table_name` ($columns) VALUES ($values)";

    // Execute the query
    if (!mysqli_query($con, $sql)) {
        throw new Exception("Error in Inserting Data: " . mysqli_error($con));
    }

    // Check if the insert was successful
    $id = mysqli_insert_id($con);
    if ($id > 0) {
        return [
            'id' => $id,
            'status' => 'success',
            'msg' => "Data Added Successfully"
        ];
    } else {
        return [
            'id' => 0,
            'status' => 'error',
            'msg' => "No rows affected. " . mysqli_error($con)
        ];
    }
}


/**
 * Updates records in a specified table.
 *
 * @param string $table_name The name of the table to update.
 * @param array $ArrayData Associative array of column names and their corresponding values to update.
 * @param mixed $identifier The ID or condition for updating records (could be a single ID or an associative array for multiple conditions).
 * @param string $pkey Primary key name (default is 'id').
 * @return array An associative array with the status of the operation.
 * @throws Exception Throws an exception if the operation fails.
 */
function update_data($table_name, $ArrayData, $identifier, $pkey = 'id')
{
    global $con;
    global $user_id;
    global $current_date_time;

    // Add updated_by and updated_at fields to ArrayData
    $ArrayData['updated_at'] = $current_date_time;
    $ArrayData['updated_by'] = $user_id;

    // Filter out empty values and clean input
    foreach ($ArrayData as $key => $value) {
        if ($value === '') {
            unset($ArrayData[$key]);
        } else {
            $value = (is_array($value))?implode(",", $value):$value;
            $ArrayData[$key] = post_clean($value); // Ensure values are cleaned
        }
    }

    // Build the SET clause
    $set_clause = [];
    foreach ($ArrayData as $key => $value) {
        $set_clause[] = "$key = '$value'";
    }

    // Build the WHERE clause for single or multiple conditions
    $where_clause = '';
    if (is_array($identifier)) {
        $where_conditions = [];
        foreach ($identifier as $key => $value) {
            $where_conditions[] = "$key = '" . post_clean($value) . "'";
        }
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    } else {
        $where_clause = "WHERE $pkey = '" . post_clean($identifier) . "'";
    }

    // Prepare the SQL statement for update
    $sql = "UPDATE $table_name SET " . implode(', ', $set_clause) . " $where_clause";

    // Execute the query
    if (!mysqli_query($con, $sql)) {
        throw new Exception("Error in Updating Data: " . mysqli_error($con));
    }

    // Check affected rows
    $num = mysqli_affected_rows($con);
    if ($num > 0) {
        return [
            'sql'=>$sql,
            'status' => 'success',
            'msg' => "$num Record(s) Updated Successfully",
            'count' => $num
        ];
    } else {
        return [
            'sql'=>$sql,
            'status' => 'error',
            'msg' => "Sorry! No Update Found. " . mysqli_error($con)
        ];
    }
}

/**
 * Soft delete single or multiple records from a table by updating their status to 'DELETED'.
 *
 * @param string $table_name The name of the table.
 * @param mixed $condition Can be either a single ID (for single record) or an associative array of conditions (for multiple records).
 * @param string $pkey The primary key field (default is 'id'), used for single record deletion.
 * @return array An associative array with the result of the operation.
 */
function remove_data($table_name, $condition, $pkey = 'id')
{
    global $con;
    global $user_id;
    global $current_date_time;

    // Check if $condition is an array (for multiple records) or not (for single record)
    if (is_array($condition)) {
        // For multiple records (condition is an array)
        $where_clause = [];
        foreach ($condition as $key => $value) {
            $clean_value = post_clean($value); // Sanitize the values
            $where_clause[] = "$key = '$clean_value'";
        }
        // Build the WHERE clause from the array
        $where_sql = implode(' AND ', $where_clause);
    } else {
        // For single record (condition is an ID)
        $clean_id = post_clean($condition); // Sanitize the ID
        $where_sql = "$pkey = '$clean_id'";
    }

    // Prepare the SQL query for soft delete
    $sql = "UPDATE $table_name SET status = 'DELETED', updated_by = '$user_id', updated_at = '$current_date_time' WHERE $where_sql";
    
    // Execute the query
    $res = mysqli_query($con, $sql) or die("Error in Deleting Data: " . mysqli_error($con));

    // Check how many rows were affected
    $num = mysqli_affected_rows($con);
    
    if ($num >= 1) {
        return [
            'status' => 'success',
            'msg' => "$num Record(s) removed successfully"
        ];
    } else {
        return [
            'status' => 'error',
            'msg' => "Sorry! No records found to delete"
        ];
    }

    create_log($sql); // Optional: Log the SQL query
}


/**
 * Hard delete single or multiple records from a table.
 *
 * @param string $table_name The name of the table.
 * @param mixed $condition Can be either a single ID (for single record) or an associative array of conditions (for multiple records).
 * @param string $pkey The primary key field (default is 'id'), used for single record deletion.
 * @return array An associative array with the result of the operation.
 */
function delete_data($table_name, $condition, $pkey = 'id')
{
    global $con;

    // Check if $condition is an array (for multiple records) or not (for single record)
    if (is_array($condition)) {
        // For multiple records (condition is an array)
        $where_clause = [];
        foreach ($condition as $key => $value) {
            $clean_value = post_clean($value); // Sanitize the values
            $where_clause[] = "$key = '$clean_value'";
        }
        // Build the WHERE clause from the array
        $where_sql = implode(' AND ', $where_clause);
    } else {
        // For single record (condition is an ID)
        $clean_id = post_clean($condition); // Sanitize the ID
        $where_sql = "$pkey = '$clean_id'";
    }

    // Prepare the SQL query for hard delete
    $sql = "DELETE FROM $table_name WHERE $where_sql";
    
    // Execute the query
    $res = mysqli_query($con, $sql) or die("Error in Deleting Data: " . mysqli_error($con));

    // Check how many rows were affected
    $num = mysqli_affected_rows($con);
    
    if ($num >= 1) {
        return [
            'status' => 'success',
            'msg' => "$num Record(s) deleted successfully"
        ];
    } else {
        return [
            'status' => 'error',
            'msg' => "Sorry! No records found to delete"
        ];
    }

    create_log($sql); // Optional
}


function get_all($table_name, $column_list = '*', $whereArr = null, $orderby = 'id DESC',  $conditionType = '=' )
{
	global $con;
	$orderby = ' ORDER BY ' . $orderby;

	// Prepare the column list for the SELECT query
	$columns = is_array($column_list) ? implode(', ', $column_list) : $column_list;

	// Construct WHERE clause based on condition type
	$where = [];
	if ($whereArr) {
		foreach ($whereArr as $key => $value) {
			$key = trim($key);
			$safeValue = preg_replace('/[^A-Za-z.@,:+0-9\-_]/', ' ', $value);
			$where[] = "$key $conditionType '$safeValue'";
		}
		$whereClause = "WHERE " . implode(' AND ', $where);
	} else {
		$whereClause = $conditionType === '=' 
            ? "WHERE status NOT IN ('AUTO', 'DELETED')" 
            : "WHERE status <> 'AUTO'";
	}

	// Build the complete SQL query
	$sql = "SELECT $columns FROM $table_name $whereClause $orderby";
	$res = mysqli_query($con, $sql) or die("Error loading data: " . mysqli_error($con));

	// Fetch and structure the results
	$data = [];
	while ($row = mysqli_fetch_assoc($res)) {
		$data[] = $row;
	}

	return [
		'count' => mysqli_num_rows($res),
		'status' => mysqli_num_rows($res) > 0 ? 'success' : 'error',
		'data' => $data ?: null
	];
}

// EXECUTE ANY SQL STATMENT DIRECTLY AND GET FORMATED RESULT

function direct_sql($sql, $type = 'get')
{
	global $con;
	global $user_id;
	$data = null;
	$res = mysqli_query($con, $sql) or die("Error In Loding Data : " . mysqli_error($con));
	if ($type == 'set') // 
	{
		$ct = mysqli_affected_rows($con);
	} else {  // FOR SELECT COMMAND 
		$ct = mysqli_num_rows($res);
		if ($ct >= 1) {
			while ($row = mysqli_fetch_assoc($res)) {
				$data[] = $row;
			}
		}
	}
	if ($ct >= 1) {
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	$result['sql'] = $sql;
	return $result;
}

// function import_sql_file($filename)
function direct_sql_file($filename)
{
	global $con;

	// Check if the file exists
	if (!file_exists($filename)) {
		return ['status' => 'error', 'msg' => "File '$filename' does not exist."];
	}

	// Read the file
	$lines = file($filename);
	$templine = ''; // Stores the current SQL query

	// Loop through each line
	foreach ($lines as $line) {
		// Skip comments and empty lines
		$line = trim($line);
		if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
			continue;
		}

		// Add this line to the current query
		$templine .= " $line";

		// If line ends with a semicolon, execute the query
		if (substr($line, -1) === ';') {
			if (!$con->query($templine)) {
				return [
					'status' => 'error',
					'msg' => "Error executing query: " . $con->error,
					'query' => $templine
				];
			}
			$templine = ''; // Reset the query buffer
		}
	}

	return [
		'status' => 'success',
		'msg' => "File '$filename' imported successfully."
	];
}

// GET SINGLE DATA FORM TABLE BASED ON CONDITION

function get_data($table_name, $id, $field_name = null, $pkey = 'id')
{
	global $con;
	$result['count'] = 0;
	$result['status'] = 'error';
	$sql = "SELECT * FROM $table_name where $pkey ='$id' ";
	$res = mysqli_query($con, $sql) or die(" Data Information Error : " . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	$result['count'] = $ct;
	if ($ct >= 1) {
		$row = mysqli_fetch_assoc($res);
		extract($row);
		if ($field_name) {
			$result['status'] = 'success';
			$result['data'] = $row[$field_name];
		} else {
			$result['status'] = 'success';
			$result['data'] = $row;
		}
	} else {
		$result['count'] = 0;
		$result['status'] = 'success';
		$result['data'] = null;
	}
	$result['sql'] = $sql;
	return $result;
}

// GET DATA FORM TABLE BASED ON MULTIPLE CONDITION

function get_multi_data($table_name, $whereArr, $order = null)
{
	global $con;

	foreach((array)$whereArr as $key => $value) {
		if($value!='')
		{
		$newvalue = preg_replace('/[^A-Za-z.@_,:+0-9\-_]/', ' ', $value);
		$where[] = "$key = '$newvalue'";
		}
	}

	$sql = "select * from " . $table_name . " WHERE " . implode(' and ', $where) . $order;
	$res = mysqli_query($con, $sql) or mysqli_error($con);
	$num = mysqli_num_rows($res);
	if ($num > 0) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['status'] = 'success';
		$result['count'] = $num;
		$result['data'] = $data;
	} else {
		$result['status'] = 'error';
		$result['count'] = 0;
		$result['data'] = mysqli_error($con);
	}
	$result['sql'] = $sql;
	return $result;
}

function upload_img($file_name, $imgkey = 'rand', $target_dir = "upload", $size ='10000')
{
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0755, true);
	}
	if ($imgkey == 'rand') {
		$imgkey = rand(10000, 99999);
	}
	$target_file = $imgkey . "_" . basename($_FILES[$file_name]["name"]);
	$target_file = strtolower(preg_replace("/[^a-zA-Z0-9._]+/", "", $target_file));
	$uploadOk = 1;

	$res['id'] = 0;
	$res['status'] = 'error';
	$res['msg'] = '';
	$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
	// Check if image file is a actual image or fake image


	// Check if file already exists
	if (file_exists($target_file)) {
		unlink($target_file);
		$res['msg'] = "Sorry, file already exists.";
		$uploadOk = 1;
	}
	// Check file size
	$file_in_kb = round($_FILES[$file_name]["size"]/1024);
	if ($file_in_kb > $size) {
	    
	    $res['msg']= "Sorry, your file is greater than $size KB File Size : $file_in_kb KB";
	    $uploadOk = 0;
	}
	// Allow certain file formats
	$valid_extension = array("png","jpg","jpeg","pdf","zip","xls","doc","rar","xlsx","docx","pptx","ppt","gif","rtf","txt","csv","mp3","mp4","ogg","wav","amr","dat","vob");
	if(!in_array($imageFileType, $valid_extension)){
	//if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "pdf") {
		$res['msg'] = "Sorry, files formated are allowed.";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$msg = "Sorry, your file was not uploaded.";
		// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES[$file_name]["tmp_name"], $target_dir . "/" . $target_file)) {
			$res['msg'] = "The file " . basename($_FILES[$file_name]["name"]) . " has been uploaded.";
			$res['id'] = $target_file;
			$res['status'] = 'success';
		} else {
			$res['msg'] = "Sorry, there was an error uploading your file.";
		}
	}
	return $res;
}


function multi_upload($fileArr='uploadimg',$target_dir = "../upload"){
      $img_name='';
	  $files = $_FILES[$fileArr];
	  if($files['name'] != ''){
	  	$file_names = '';
	  	$total = count($files['name']);
	  	for($i=0; $i<$total; $i++){
	  		$file_name = $files['name'][$i];
	  		$extention = pathinfo($file_name, PATHINFO_EXTENSION);
	  		$valid_extension = array("png","jpg","jpeg","pdf","zip","xls","doc","rar","xlsx","docx","pptx","ppt","gif","rtf","txt","csv","mp3","mp4","ogg","wav","amr","dat","vob");
	  if(in_array($extention, $valid_extension)){
	  	$new_name = rand(100,999).remove_only_space($file_name);
	  	$path = "$target_dir/" . $new_name;
	  	$resp = move_uploaded_file($files['tmp_name'][$i],$path);
	  	 if ($resp ==1){
	  	 	// $file_names .= $new_name . ",";
	  	// $name_arr = array($img_for_field=>$id,$img_field=>$new_name,'type'=>$type);
	  	$img_name .= $new_name . ",";
	  	// $res = insert_data($tbl,$name_arr);
	    $res['img_name'] = rtrim($img_name,',');
	    $res['id'] = explode(',', rtrim($img_name, ","));
	  	
	  }else{
	  	$res = $resp;
	  }
    	}
        }
    	}else {
    		$res['id'] =0;
		    $res['status'] ='error';
		    $res['msg'] ='';
     	 }
    	return $res;
     }

function send_mail($to, $subject, $msg, $att_arr='', $name='')
{
   require 'vendor/autoload.php';
   global $inst_name;
   global $inst_email;
   global $noreply_email;
   global $inst_email_password;
   $mail = new PHPMailer;
   $mail->isSMTP();
   $mail->SMTPDebug = 0;// 2 for debug
   $mail->Host = 'smtp.hostinger.com';
   $mail->Port = 587;
   $mail->SMTPAuth = true;
   $mail->Username = $inst_email;
   $mail->Password = $inst_email_password;
   $mail->setFrom($inst_email, $inst_name);
   $mail->addReplyTo($noreply_email, $inst_name);
   $mail->addAddress($to, $name);
   $mail->Subject = $subject;
   $mail->isHTML(true);
   $mail->Body = $msg;
       foreach((array)$att_arr as $att)
       {
            $mail->addAttachment($att);
       }
   if (!$mail->send()) {
       echo 'Mailer Error: ' . $mail->ErrorInfo;
       return false;
   } else {
       return true;
   }
}

function api_call($api_url)
{
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	return $result;
}

function csv_export($table_name, $col_list = '*')
{
	global $con;
	global $db_name;
	$filename = $table_name . ".csv";
	$fp = fopen('php://output', 'w');

	if ($col_list == '*') {
		$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name'";
		$result = mysqli_query($con, $query);
		while ($row = mysqli_fetch_row($result)) {
			$header[] = $row[0];
		}
	} else {
		$header = explode(',', $col_list);
	}

	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename=' . $filename);
	fputcsv($fp, $header);

	$query = "SELECT $col_list FROM $table_name";
	$result = mysqli_query($con, $query);
	while ($row = mysqli_fetch_row($result)) {
		fputcsv($fp, $row);
	}
	//exit;
}


function csv_import($table, $pkey = 'id') // Import CSV FILE to Table
{
	// Allowed mime types
	$csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
	$change = $new = 0;
	// Validate whether selected file is a CSV file
	if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)) {
		
		if (is_uploaded_file($_FILES['file']['tmp_name'])) {

			// Open uploaded CSV file with read-only mode
			$csvFile = fopen($_FILES['file']['tmp_name'], 'r');
			echo $col_list = array_map('trim', fgetcsv($csvFile));
			print_r($col_list);
			while (($line = fgetcsv($csvFile)) !== FALSE) {
				$all_data = array_combine($col_list, $line);
				//$search[$pkey] =trim($all_data[$pkey]);
				//$search_result = get_all($table,'*', $search, $pkey);
				$search_result = get_data($table, $all_data[$pkey], null, $pkey);
				echo "<pre>";
				print_r($search_result);
				if ($search_result['count'] < 1) {
					$res = insert_data($table, $all_data);
					if ($res['id'] != 0) {
						$new++;
					}
				} else {
					//echo $all_data[$pkey];
					$res = update_data($table, $all_data, $all_data[$pkey], $pkey);
					if ($res['status'] == 'success') {
						$change++;
					}
				}
				$res = array('status' => 'success', 'change' => $change, 'new' => $new, 'msg' => " $new New Data and $change change found and updated.");
			}
		}
	} else {
		$res = array('status' => 'error', 'change' => $change, 'new' => $new, 'msg' => 'Please upload a valid CSV file.');
	}
	return  $res;
}


function get_bal_msg()
{
	global $auth_key_msg;
	$api_url = 'http://mysms.msgclub.net/rest/services/sendSMS/getClientRouteBalance?AUTH_KEY=' . $auth_key_msg;
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	$data  = json_decode($result, true);
	return $data[0]['routeBalance'];
}


function get_bal_sms()
{
	global $auth_key_sms;
	$api_url = 'http://sms.morg.in/api/balance.php?&type=4&authkey=' . $auth_key_sms;
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	$data  = json_decode($result, true);
	return $data;
}

function send_msg($number,$sms,$templateid)
		{
			$res =null;
			$numarr = explode(',', $number);
		foreach((array) $numarr as $num)
			{
		    global $user_id;
			global $sender_id;
			global $auth_key;
			global $current_date_time;
			$ctype ="English";
			
			$no ='91'.urlencode($num);
			$msg = substr(urlencode($sms),0,2000);
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$url="http://sms.morg.in/api/sendhttp.php?authkey=$auth_key&mobiles=$no&message=$msg&sender=$sender_id&route=4&country=91&DLT_TE_ID=$templateid";
			
	        curl_setopt($ch,CURLOPT_URL, $url);
	    	$res= curl_exec($ch);
	    	$data =json_decode($res, true);
	    	curl_close($ch);
			}
			return $res;
		}	


function date_range($gap = 15)
{

	$startDate = date('Y-m-d');
	$endDate = date("Y-m-d", strtotime("+$gap days", strtotime($startDate)));
	$startStamp = strtotime($startDate);
	$endStamp   = strtotime($endDate);

	if ($endStamp > $startStamp) {
		while ($endStamp >= $startStamp) {

			$data['dv'] = date('Y-m-d', $startStamp);
			$data['dd'] = date('d M D', $startStamp);
			$data['day'] = date('D', $startStamp);
			$dateArr[] = $data; // date( 'Y-m-d', $startStamp );

			$startStamp = strtotime(' +1 day ', $startStamp);
		}
		return $dateArr;
	} else {
		return $startDate;
	}
}

// HTML UI CREATE

function create_input($name, $type, $value = null, $display_name, $extra = '', $size = 'col-md-4')
{
    $value =($value=='')?'':$value;
    if ($type == 'hidden') {
        $str = "<input type='hidden' class='form-control' value='$value' name='$name' id='$name' $extra>";
    } elseif ($type == 'number') {
        $str = "<div class='form-group $size'>
            <label class='op-label'>$display_name</label>
            <input type='text' class='form-control' value='$value' name='$name' id='$name'  
                oninput='this.value = this.value.replace(/[^0-9.]/g, \"\").replace(/(\..*?)\..*/g, \"\$1\");'
                $extra >
        </div>";
    } else {
        $str = "<div class='form-group $size'>
                <label class='op-label' >$display_name</label>
                <input type='$type' class='form-control' value='$value' name='$name' id='$name' $extra>
            </div>";
    }
    return $str;
}



function display_img($photo, $width = '100px', $height = '100px')
{
	global $base_url;
	$str = "<img src='$base_url/upload/$photo' width='$width'  height='$height'  class='img-thumbnail d-self-centered'>";
	return $str;
}


function dropdown($data_source, $selected = [])
{
    global $con;
    $output = '<option value="">Select Value</option>';

    // Ensure `$selected` is always treated as an array
    $selected = is_array($selected) ? $selected : [$selected];

    // Generate <option> elements
    foreach ($data_source as $key => $value) {
        // Determine option value and display text
        $option_value = is_int($key) ? $value : $key;
        $display_text = is_array($value) ? implode(' ', $value) : $value;

        // Add `selected` attribute if option value is in `$selected`
        $selected_attr = in_array($option_value, $selected) ? ' selected' : '';
        $output .= "<option value='$option_value'$selected_attr>$display_text</option>";
    }

    return $output;
}



// function dropdown_list( string $data_source,    string $value_key,    array|string $display_keys = [],    array|string|null $selected = [],    array|null $conditions = []) {
//     global $con;
//     $output = '<option value="">Select Value</option>';

//     // Sanitize and validate the table name
//     $data_source = mysqli_real_escape_string($con, $data_source);

//     // Ensure `$selected` is an array for consistent processing
//     $selected = is_array($selected) || is_string($selected) ? $selected : [];

//     // Ensure `$display_keys` is an array for consistent processing
//     $display_keys = is_array($display_keys) ? $display_keys : [$display_keys];

//     // Base query
//     $query = "SELECT * FROM `$data_source` WHERE status NOT IN('AUTO', 'BLOCK', 'DELETED')";

//     // Add conditions if provided
//     if (!empty($conditions)) {
//         $condition_parts = [];
//         foreach ($conditions as $column => $condition_value) {
//             $column = mysqli_real_escape_string($con, $column);
//             $clean_value = mysqli_real_escape_string($con, post_clean($condition_value));
//             $condition_parts[] = "`$column` = '$clean_value'";
//         }
//         $query .= " AND " . implode(' AND ', $condition_parts);
//     }

//     // Add ORDER BY clause if `$display_keys` is not empty
//     if (!empty($display_keys)) {
//         $escaped_keys = array_map(fn($key) => "`" . mysqli_real_escape_string($con, $key) . "`", $display_keys);
//       // $query .= " ORDER BY " . implode(', ', $escaped_keys);
//     }

//     // Execute the query
//     $res = mysqli_query($con, $query) or die("Dropdown Options Error: " . mysqli_error($con));

//     // Generate <option> tags
//     while ($row = mysqli_fetch_assoc($res)) {
//         $key = $row[$value_key];
//         $display = '';

//         // Concatenate display keys for option text
//         foreach ($display_keys as $display_key) {
//             if (isset($row[$display_key])) {
//                 $display .= $row[$display_key] . ' ';
//             }
//         }
//         $display = trim($display);

//         // Add `selected` attribute if the key matches
        
//         $selected_attr = (is_array($selected) and in_array($key, $selected)) ? ' selected' : '';
//         $output .= "<option value='$key'$selected_attr>$display</option>";
//     }

//     return $output;
// }

function dropdown_list(
    string $data_source, 
    string $value_key, 
    array|string $display_keys = [], 
    array|string|null $selected = null, 
    array|null $conditions = null
) {
    global $con;

    $output = '<option value="">Select Value</option>';

    // Sanitize and validate the table name
    $data_source = mysqli_real_escape_string($con, $data_source);

    // Ensure `$selected` is an array for consistent processing
    if (!is_array($selected)) {
        $selected = $selected !== null ? [$selected] : [];
    }

    // Ensure `$display_keys` is an array for consistent processing
    $display_keys = is_array($display_keys) ? $display_keys : [$display_keys];

    // Build the base query
    $query = "SELECT * FROM `$data_source` WHERE status NOT IN('AUTO', 'BLOCK', 'DELETED')";

    // Add conditions if provided
    if (!empty($conditions)) {
        $condition_parts = [];
        foreach ($conditions as $column => $condition_value) {
            $column = mysqli_real_escape_string($con, $column);
            $clean_value = mysqli_real_escape_string($con, post_clean($condition_value));
            $condition_parts[] = "`$column` = '$clean_value'";
        }
        $query .= " AND " . implode(' AND ', $condition_parts);
    }

    // Add ORDER BY clause if `$display_keys` is not empty
    if (!empty($display_keys)) {
        $escaped_keys = array_map(fn($key) => "`" . mysqli_real_escape_string($con, $key) . "`", $display_keys);
        $query .= " ORDER BY " . implode(', ', $escaped_keys);
    }

    // Execute the query
    $res = mysqli_query($con, $query) or die("Dropdown Options Error: " . mysqli_error($con));

    // Generate <option> tags
    while ($row = mysqli_fetch_assoc($res)) {
        $key = $row[$value_key];
        $display = '';

        // Concatenate display keys for option text
        foreach ($display_keys as $display_key) {
            if (isset($row[$display_key])) {
                $display .= $row[$display_key] . ' ';
            }
        }
        $display = trim($display);

        // Add `selected` attribute if the key matches
        $selected_attr = in_array($key, $selected) ? ' selected' : '';
        $output .= "<option value='$key'$selected_attr>$display</option>";
    }

    return $output;
}


function check_list($name, $array_list, $selected_str = null, $height = '160px') {
    // Convert the selected string into an array (if it's not null)
    $selected = explode(',', $selected_str); 
    $str = '';
    
    // Start the scrollable container div with a nice shadow and padding
    $str .= "<div style='overflow-y: auto; height: $height;' class='mt-1 shadow-sm p-3 mb-4 bg-light rounded'>";
    
    // Add "Select All" functionality with Bootstrap styling
    $str .= "<div class='d-flex align-items-center mb-3'>
                <input type='checkbox' id='select_all_$name' onclick=\"selectAll('$name')\" class='form-check-input'>
                <label for='select_all_$name' class='form-check-label ms-2 text-primary text-bold'>Select All</label>
             </div>";

    // Iterate through the provided list and generate checkboxes
    foreach ((array)array_filter($array_list) as $list) {
        // Default checked state is empty
        $checked = '';

        // Check if the current list item is in the selected array
        if (in_array(trim($list), array_map('trim', $selected))) {
            $checked = 'checked';
        }

        // Escape the list item to prevent HTML injection
        $list_safe = htmlspecialchars($list, ENT_QUOTES, 'UTF-8');

        // Add the checkbox HTML with Bootstrap styles
        $str .= "<div class='form-check'>
                    <input type='checkbox' value='$list_safe' id='Checkbox_$list_safe' $checked name='{$name}[]' class='form-check-input'>
                    <label class='op-label'  for='Checkbox_$list_safe' class='form-check-label'>$list_safe</label>
                 </div>";
    }

    // Close the scrollable container div
    $str .= '</div>';

    // Add the JavaScript to handle "Select All" functionality
    $str .= "<script>
                function selectAll(name) {
                    let checkboxes = document.querySelectorAll('input[name=\"' + name + '[]\"]');
                    let selectAllBox = document.getElementById('select_all_' + name);
                    checkboxes.forEach(cb => cb.checked = selectAllBox.checked);
                }
             </script>";

    // Return the generated HTML string
    return $str;
}



function create_list($table_name, $field, $whereArr = null)
{
    global $con;

    // Initialize an array to hold conditions and values
    $conditions = [];
    $values = [];

    // Build the WHERE clause if $whereArr is provided
    if (!is_null($whereArr) && is_array($whereArr)) {
        foreach ($whereArr as $key => $value) {
            // Sanitize input value
            $newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-]/', ' ', $value);
            $conditions[] = "$key = ?";
            $values[] = $newvalue; // Add sanitized value to the array
        }
    }

    // Build the SQL query
    $sql = "SELECT DISTINCT $field FROM $table_name";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    // Prepare the statement
    $stmt = mysqli_prepare($con, $sql);
    
    // Bind parameters if any conditions were provided
    if (!empty($values)) {
        // Generate the types string (s for string)
        $types = str_repeat('s', count($values));
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }

    // Execute the statement
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Initialize the list array
    $list = [];
    if (mysqli_num_rows($result) >= 1) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row[$field];
        }
    }

    // Free result set and close statement
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return !empty($list) ? $list : null; // Return null if the list is empty
}


function state_list()
{
	return create_list('op_sdb', 'state');
}

function district_list($state = 'bihar')
{
	return create_list('op_sdb', 'district', ['state' => $state]);
}

function block_list($district = 'saran')
{
	return create_list('op_sdb', 'block', ['district' => $district]);
}



// GET REMOTE FILE SIZE
function remote_file_size($url)
{
    // Assume failure.
    $result = -1;

    $curl = curl_init($url);

    // Issue a HEAD request and follow any redirects.
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    $data = curl_exec($curl);

    if ($data === false) {
        // Handle cURL error
        curl_close($curl);
        return -1; // Error during execution
    }

    $status = null;
    $content_length = null;

    // Extract HTTP status code
    if (preg_match("/^HTTP\/1\.[01] (\d{3})/", $data, $matches)) {
        $status = (int)$matches[1];
    }

    // Extract Content-Length header
    if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
        $content_length = (int)$matches[1];
    }

    // Close the cURL handle
    curl_close($curl);

    // Check if the file exists based on the status code
    if ($status === 200 || ($status >= 300 && $status <= 308)) {
        // If the content length is known, return it
        $result = $content_length !== null ? $content_length : 0; // 0 for empty file
    } elseif ($status === 404) {
        // File not found
        return -1; // File does not exist
    }

    // Convert bytes to megabytes
    return round($result / (1024 * 1024), 2); // Return size in MB
}


/*=============== CONFIG MANAGAMENT ===========*/

function string_to_array($inputString) {
    // Check if the input string is in JSON object format
    $isObject = is_object(json_decode($inputString));

    // Check if the input string is in JSON array format
    $isArray = is_array(json_decode($inputString));

    // Check if the input string is a comma-separated value
    $isCSV = strpos($inputString, ',') !== false;

    // Initialize the result array
    $resultArray = [];

    if ($isObject || $isArray) {
        // If it's a JSON object or array, directly decode it
        $resultArray = json_decode($inputString, true);
    } elseif ($isCSV) {
        // If it's a comma-separated value, explode and trim the values
        $csvArray = explode(',', $inputString);
        $resultArray = array_map('trim', $csvArray);
    } else {
        // If none of the above conditions match, return an empty array
        $resultArray = [];
    }

    return $resultArray;
}


function update_config()
{
	global $_CONFIG;
	foreach((array)$_CONFIG as $key => $value) {
		$arr['option_name'] = $key;
		if (is_array($value)) {
			$arr['option_type'] = 'LIST';
			$arr['option_value'] = implode(",", $value);
		} else {
			$arr['option_type'] = 'SINGLE';
			$arr['option_value'] = $value;
		}

		$rescheck = get_data('op_config', $key, null, 'option_name');

		if ($rescheck['count'] == 0) {
			$res = insert_data('op_config', $arr);
		} else {
			$res = update_data('op_config', $arr, $key, 'option_name');
		}
	}
	//print_r($res);
	//return $res;
}

function set_config($key, $value = null)
{
	$arr['option_name'] = $key;
	if (is_array($value)) {
		$arr['option_value'] = json_encode($value);
	} else {
		$arr['option_value'] = $value;
	}
	$rescheck = get_data('op_config', $key, null, 'option_name');
	if ($rescheck['count'] == 0) {
		$res = insert_data('op_config', $arr);
	} else {
		$res = update_data('op_config', $arr, $key, 'option_name');
	}
	return $res;
}

function get_config($key)
{
	$res = get_data('op_config', $key, 'option_value', 'option_name');
	if ($res['count'] > 0) {
		return $res['data'];
	} else {
		return null;
	}
}

function delete_config($key)
{
	$res = delete_data('op_config', $key, 'option_name');
	return $res;
}

function all_config()
{
    $tbls = table_list();
    
    if($tbls['count']==0)
    {
       $res = direct_sql_file("system/opex_db.sql");
    }
	$vardata=[];
	$res = get_all('op_config');
	foreach((array)$res['data'] as $data) {
		$key  = $data['option_name'];
		$value  = $data['option_value'];
		
    	if ($data['option_type'] == 'LIST') {
 			// $value = explode(",", $data['option_value']); //Old Concept
            $vardata[$key] = string_to_array($value);
		}
		else{
		    $vardata[$key] = $value;
		}
	}
	return $vardata;
}

function create_log($arMsg)
{
    global $user_name;
    global $base_url;

    // Define empty string for the log entry
    $stEntry = "";

    // Capture event date-time, client IP, request URI, and request method
    $event_datetime = '[' . date('D Y-m-d h:i:s A') . ']';
    $client_ip = '[client ' . $_SERVER['REMOTE_ADDR'] . ']';
    $request_uri = '[URI ' . $_SERVER['REQUEST_URI'] . ']';
    $request_method = '[Method ' . $_SERVER['REQUEST_METHOD'] . ']';
    $user_agent = '[Agent ' . $_SERVER['HTTP_USER_AGENT'] . ']';
    
    // Build the base log information (datetime, IP, URL, etc.)
    $log_base_info = $event_datetime . ' ' . $client_ip . ' ' . $request_uri . ' ' . $request_method . ' ' . $user_agent;

    // Process the message (if it's an array, loop through the messages)
    if (is_array($arMsg)) {
        foreach ($arMsg as $msg) {
            $stEntry .= $log_base_info . " by " . $user_name . " " . $msg . "\r\n";
            $notice = $msg . " by " . $user_name;
        }
    } else {
        $stEntry .= $log_base_info . " by " . $user_name . " " . $arMsg . "\r\n";
        $notice = $arMsg . " by " . $user_name;
    }

    // Set the log file path and name based on the current date
    $log_directory = 'logs/';
    $log_file_name = 'log_' . date('Ym') . '.txt';
    $log_file_path = $log_directory . $log_file_name;

    // Ensure the logs directory exists, create if not
    if (!is_dir($log_directory)) {
        mkdir($log_directory, 0777, true);
    }

    // Open the file in append mode, create it if it doesn't exist
    if ($fHandler = fopen($log_file_path, 'a+')) {
        // Write the log entry into the file
        fwrite($fHandler, $stEntry);

        // Close the file handler
        fclose($fHandler);
    } else {
        // If log file can't be opened, handle the error (optional logging to a system log)
        error_log("Could not open log file: $log_file_path");
    }
    
    return $notice; // Optional: Return the notice message for further use
}



function amount_in_word($number)
{
    // Separate the whole number and decimal parts
    $number = number_format($number, 2, '.', '');
    $wholeNumber = floor($number);
    $decimalPart = round(($number - $wholeNumber) * 100);
    
    $words = array(
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
        80 => 'eighty', 90 => 'ninety'
    );
    
    $placeValue = array('', 'hundred', 'thousand', 'lakh', 'crore');

    $result = [];
    $digitsLength = strlen($wholeNumber);
    $i = 0;

    while ($i < $digitsLength) {
        $divider = ($i == 2) ? 10 : 100;
        $numberPart = floor($wholeNumber % $divider);
        $wholeNumber = floor($wholeNumber / $divider);
        $i += $divider == 10 ? 1 : 2;

        if ($numberPart) {
            $plural = (($counter = count($result)) && $numberPart > 9) ? 's' : '';
            $hundred = ($counter == 1 && $result[0]) ? ' and ' : '';
            $result[] = ($numberPart < 21) 
                ? $words[$numberPart] . ' ' . $placeValue[$counter] . $plural . $hundred
                : $words[floor($numberPart / 10) * 10] . ' ' . $words[$numberPart % 10] . ' ' . $placeValue[$counter] . $plural . $hundred;
        }
    }

    // Prepare the final output
    $rupees = implode('', array_reverse($result));
    $paise = ($decimalPart > 0) ? ' ' . ($words[floor($decimalPart / 10)] . ' ' . $words[$decimalPart % 10]) . ' Paise' : '';
    
    // Return the formatted string
    return ucwords(trim($rupees) . ' Rupees' . trim($paise));
}


function expiry($start_date) // service Start Date
{
    $exp_date = date('Y-m-d',strtotime("+365 day", strtotime($start_date)));
    $cur_date= date("Y-m-d");
    $earlier = new DateTime($exp_date);
    $later = new DateTime($cur_date);

    $da = $later->diff($earlier)->format("%r%a");
	if ($da < 0 ) {
		die("Subscription Expired ! Please Contact to Service Provider");
	} else {
		return "$da days";
	}
}


function find_in_string($str, $item)
{
	$parts = explode(',', $str);
	$st = "NO";
	while (($i = array_search(trim($item), $parts)) !== false) {
		$st = "YES";
		break;
	}
	return $st;
}

function add_to_string($str, $item)
{
	if ($str != '') {
		$parts = explode(',', $str);

		if (array_search($item, $parts) > -1) {
			return $str;
		} else {
			array_push($parts, trim($item));
			return implode(',', $parts);
		}
	} else {
		return $item;
	}
}


function remove_from_string($str, $item)
{
	$parts = explode(',', $str);

	while (($i = array_search(trim($item), $parts)) !== false) {
		unset($parts[$i]);
	}

	return implode(',', $parts);
}

function resize_image($file, $w, $h, $crop = FALSE)
{
	list($width, $height) = getimagesize($file);
	$r = $width / $height;
	if ($crop) {
		if ($width > $height) {
			$width = ceil($width - ($width * abs($r - $w / $h)));
		} else {
			$height = ceil($height - ($height * abs($r - $w / $h)));
		}
		$newwidth = $w;
		$newheight = $h;
	} else {
		if ($w / $h > $r) {
			$newwidth = $h * $r;
			$newheight = $h;
		} else {
			$newheight = $w / $r;
			$newwidth = $w;
		}
	}
	$src = imagecreatefromjpeg($file);
	$dst = imagecreatetruecolor($newwidth, $newheight);
	imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

	return $dst;
}


// FORM AAROGYAARTH ROLE & PERMISSION 

function check_role($table_name, $user_type, $role_name = 'can_view')
{
		$result['data'] = '<input type="checkbox" class="update_role" value="remove" data-table="' . $table_name . '"  data-user="' . $user_type . '" data-role="' . $role_name . '" checked>';
		$result['status'] = 'YES';
		return $result;
}



function btn_delete($table, $id,  $disabled = "")
{
	global $user_id;
	global $user_type;
	$str = '';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN'){
		$str = "<button class='delete_btn btn btn-danger btn-sm'  data-table='$table' data-id='$id' data-pkey='id' title='Detete This Permanently' $disabled > <i class='fa fa-trash'></i> </button> ";
	} else {
		if (check_role($table, $user_id, 'can_delete')['status'] == 'YES') {
			$str = "<button class='delete_btn btn btn-danger btn-sm'  data-table='$table' data-id='$id' data-pkey='id' title='Detete This Permanently' $disabled > <i class='fa fa-trash'></i> </button> ";
		}
	}
	return $str;
}


function btn_delete_multiple($table_name)
{
	global $user_id;
	global $user_type;
	$str = '';
	if ($user_type == 'DEV') {
		$str = "<button class='btn btn-danger btn-sm' id='delete_btn' title='Delete Selected' data-table ='$table_name'> <i class='fa fa-trash'></i> </button>";
	} else {
		if (check_role($table_name, $user_id, 'can_delete')['status'] == 'YES') {
			$str = "<button class='btn btn-danger btn-sm' id='delete_btn' title='Delete Selected' data-table ='$table_name'> <i class='fa fa-trash'></i> </button>";
		}
	}
	return $str;
}

function btn_simple($table,$id, $arr)
{
  global $user_type;
  global $user_name;
  $str = '';
    $arr = explode(',',$arr);
  $clname = $arr[0];
  $icon = $arr[1];
  $title = $arr[2];
  $data = json_encode(get_data($table,$id)['data']);
  $res =  get_multi_data('op_role', array('table_id'=>$table, 'role_name'=>$user_type));

  if($res['count']>0)
  {
    $permission =$res['data'][0]; // Check Permission 
  
      if($user_type =='DEV')
        {
          $str = "<a href='javascript:void(0)'  type='button' data-all='$data' class='$clname btn btn-info btn-sm text-light' data-title='$title'><i class='fa fa-$icon'></i></a>&nbsp;";
        }
      else if($permission['can_edit'] =='YES'){
           $str = "<a href='javascript:void(0)'  type='button' data-all='$data' class='$clname btn btn-info btn-sm text-light' data-title='$title'><i class='fa fa-$icon'></i></a>&nbsp;";
        } else {
        $str ='';
      }
  }
  return $str;
}

function btn_remove($table_name, $id,  $disabled = "")
{
	global $user_name;
	global $user_type;

	$str ='';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
	    {
	    $str = "<button class='remove_btn btn btn-dark btn-sm'  data-table='$table_name' data-id='$id' data-pkey='id' title='Sure to Remove Data' $disabled > <i class='fa fa-close'></i> </button> ";
		}
	else{
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
    	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$row =get_data($table_name, $id)['data'];
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    		if($permission['can_remove'] =='YES')
    			{
    		$str = "<button class='remove_btn btn btn-dark btn-sm'  data-table='$table_name' data-id='$id' data-pkey='id' title='Sure to Remove Data' $disabled > <i class='fa fa-close'></i> </button> ";
    			}
    		else{
    		$str ='';
    		}
    	  }
	}
	return $str;
}

function btn_remove_multiple($table_name)
{
	global $user_name;
	global $user_type;

	$str ='';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
	    {
	    $str = "<button class='btn btn-dark btn-sm' id='remove_btn' title='Remove Selected' data-table ='$table_name'> <i class='fa fa-close'></i> </button>";
		}
	else{
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
    	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    		
    		if($permission['can_remove'] =='YES')
    			{
    		$str = "<button class='btn btn-dark btn-sm' id='remove_btn' title='Remove Selected' data-table ='$table_name'> <i class='fa fa-close'></i> </button>";
    			}
    		else{
    		$str ='';
    		}
    	  }
	}
	return $str;
}

function btn_restore($table, $id, $status)
{
	$str = "<button class='active_block btn btn-dark btn-sm'  data-table='$table' data-id='$id' data-pkey='id' data-status='$status' title='Sure to Restore Data'> <i class='fa fa-undo'></i> </button> ";
	return $str;
}

function btn_login_as($table, $id, $status)
{
	$udata = get_data($table,$id)['data'];
	$user_name = $udata['user_name'];
	$user_pass = $udata['user_pass'];
	$str = "<button class='login_as btn btn-warning btn-sm'  data-table='$table' data-id='$user_name' data-pkey='id' data-code='$user_pass' title='Login As This Account'> <i class='fa fa-key'></i> </button> ";
	return $str;
}

function btn_edit($table_name, $id, $page_url='add', $btn = 'btn-info', $icon = 'fa-edit', $title = 'Edit')
{
	global $user_name;
	global $base_url;
	$folder = (get_data('op_table',$table_name,'status','table_id')['data'] =='LOCKED')?"system":"public";
	if($page_url='add')
	{
	  $page_url = $base_url. $folder."/".$table_name.'_add';  
	}
	$link = $page_url . "?link=" . encode("id=" . $id);
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = "<a href='$link' class=' btn $btn btn-sm text-light' title='$title'> <i class='fa $icon'></i></a> ";
		}
	else{
	        $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
        	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
        	$str = '';
        	if($res['count']>0)
        	{
        		$permission =$res['data'][0]; // Check Permission 
        	
        		if($permission['can_edit'] =='YES'){
        			$str = "<a href='$link' class=' btn $btn btn-sm text-light' title='$title'> <i class='fa $icon'></i></a> ";
        		}
        		else{
        		$str = '';
        		}
        	}
	}
	return $str;
}
function btn_add($table_name)
{
	global $user_type;
	global $user_name;
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = '<a href="'.$table_name.'_add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
			//$str = '<a href="add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
		}
	else{
	        $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
        	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
        	$str = '';
        	if($res['count']>0)
        	{
        		$permission =$res['data'][0]; // Check Permission 
        	
        		if($permission['can_add'] =='YES'){
        			//$str = '<a href="'.$table_name.'_add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
        			$str = '<a href="add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
        		}
        		else{
        		$str = '';
        		}
        	}
	}
	return $str;
}

function btn_view($table_name, $id, $title = '')
{
    global $base_url;
	global $user_type;
	global $user_name;
	$view_link = $base_url.'system/view_data.php?link=' . encode('table=' . $table_name . '&id=' . $id);
    if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
		}
	else{
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_view'] =='YES'){
    
    			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
    		} else {
    			$str ='';
    		}
    	}
	}
	return $str;
}
function btn_save($table_name)
{
	global $user_type;
	global $user_name;

	if($user_type =='DEV')
		{
			$str = "<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button> ";
		}
	else{
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_add'] =='YES' || $permission['can_edit'] =='YES'){
    
    			$str = "<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button>";
    		} else {
    			$str ='';
    		}
    	}
	}
	return $str;
}


function btn_about($table, $id ,$title ='About' )
	{
		global $user_type;
		global $user_name;
		$view_link = 'system/view_data.php?link='.encode('table='.$table.'&id='.$id);
		if($user_type =='DEV')
			{
			$str = " <a data-href='$view_link' class='view_data ' title='click to view details' data-title='$title'><i class='fa fa-info-circle'></i></a> ";
			}
		else{
		    $res =  get_multi_data('op_role', array('table_id'=>$table, 'role_name'=>$user_type));
    
        		if($res['count']>0)
        		{
        			$permission =$res['data'][0]; // Check Permission 
        		}
        		else if($permission['can_view'] =='YES'){
        
        			$str = " <a data-href='$view_link' class='view_data' title='click to view details' data-title='$title'><i class='fa fa-info-circle'></i></a> ";
        		} else {
        			$str ='';
        		}
			}
		return $str;
									
	}

function btn_reply($table, $id ,$col, $msg ='Reply Box' )
{
	$view_link = 'view_data.php?link='.encode('table='.$table.'&id='.$id);
	$str ="<button class='reply_btn btn btn-dark btn-sm' data-id='$id' data-table='$table' data-col='$col' ><i class='fa fa-reply'></button>";
	return $str ;										
}
		
		
function all_back_images()
{
	$vardata=[];
	$res = get_all('back_img', '*', ['status' => 'ACTIVE']);
	foreach((array)$res['data'] as $data) {
		$key  = $data['type'];
		$value  = $data['photo'];
		if ($data['status'] == 'ACTIVE') {
			$value = $data['photo'];
		}
		$vardata[$key] = $value;
	}
	return $vardata;
	//	extract($vardata);
}
function remote_file_exists($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); # handles 301/2 redirects
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpCode == 200) {
		return false;
	}else{
		return true;
	}
}


function print_button($link,$icon='file-pdf',$title=null,$bsClr='success'){
    $btn = "<a href='$link' target='_blank' title='$title' class='mx-1'>
    <box-icon type='solid' class='rounded bg-$bsClr' name='$icon' color='#ffffff'></box-icon></a>";
	return $btn;
}


function curr_url(){
   $url = "HTTP" . (($_SERVER['SERVER_PORT'] == 443) ? "S" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
   return $url;
}


function show_img2($filePath) {
    // Check if the Fileinfo extension is available
    if (function_exists('finfo_open')) {
        // Create a fileinfo resource
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        // Get the MIME type of the file
        $fileType = finfo_file($finfo, $filePath);

        // Close the fileinfo resource
        finfo_close($finfo);
    } else {
        // If Fileinfo extension is not available, use a fallback method to get the MIME type
        $fileType = mime_content_type($filePath);
    }

    // Check if it's an image
    if (strpos($fileType, 'image/') === 0) {
        // Display the image preview
        echo "<img src='$filePath' alt='Image Preview' />";
    }
    // Check if it's an audio
    elseif (strpos($fileType, 'audio/') === 0) {
        // Display the audio player
        echo "<audio controls>
                  <source src='$filePath' type='$fileType'>
                  Your browser does not support the audio element.
              </audio>";
    }
    // Check if it's a video
    elseif (strpos($fileType, 'video/') === 0) {
        // Display the video player with a preview image
        echo "<video width='320' height='240' controls>
                  <source src='$filePath' type='$fileType'>
                  Your browser does not support the video tag.
              </video>";
    }
    // For any other file type, provide a download link
    else {
        echo "<a href='$filePath' download>Download File</a>";
    }
}


function show_img($img_name, $width='50', $height='50')
{
	global $base_url;
	$str ='';
	
	$remote_url = $base_url.'upload/' . $img_name;
	$no_img_url = $base_url.'upload/no_photo.jpg';
	$image_url = (remote_file_exists($remote_url) or $img_name =='')?$no_img_url:$remote_url;
		$extention = pathinfo($img_name, PATHINFO_EXTENSION);

	$str = "<img src='$image_url' width='$width' height='$height'>";
	$finfo = finfo_open(FILEINFO_MIME_TYPE);

        // Get the MIME type of the file
    //$fileType = finfo_file($finfo, '..upload/' . $img_name);
	return $str; //$fileType;
} 

function show_status($status, $type ='badge')
{
    $str ='';
    if($status=='ACTIVE' or $status=='YES' or $status=='PAID' or $status=='INCOME' or $status=='DISPATCHED')
    {
       $str ="<span class='$type badge badge-success-light'> $status </span>"; 
    }
    else if($status=='BLOCK' or $status=='NO' or $status=='UNPAID'  or $status=='EXPENCE' or $status=='REJECTED')
    {
       $str ="<span class='$type badge badge-danger-light'> $status </span>";
    }
    else if($status=='SHOW' or $status=='VERIFIED'  or $status=='CLIENT')
    {
      $str ="<span class='$type badge badge-primary-light'> $status </span>";
    }
    else if($status=='PENDING' or $status=='HIDE' or $status=='RESULT OUT')
    {
       $str ="<span class='$type badge badge-warning-light'> $status </span>";
    }
    else{
        $str ="<span class='$type badge badge-info-light'> $status </span>";
    }
    return "<span class='margin-auto'> $str </span>";
}


function create_form($table_name, $id, $isedit='yes', $action ='master_update_data', $type='master') {
    // Get the column names from the database
    global $user_type;
	global $today;
	global $working_date;
	$farr = [];
	if($_SESSION['user_type'] !='DEV')
		{
	
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$_SESSION['user_type']));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_add'] =='NO' and $isedit =='no')
    		{
				$farr[]='<div class="alert alert-danger " role="alert">
				<div class="alert-icon">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="alert-message">
					<strong>Sorry !</strong> Dont Have permission  to add !
				</div>
			</div>';
    		    return $farr;
    		}
			
    		if($permission['can_edit'] =='NO' and $isedit =='yes')
    		{
				$farr[]='<div class="alert alert-warning " role="alert">
				<div class="alert-icon">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="alert-message">
					<strong>Sorry !</strong> Dont Have permission  to Edit !
				</div>
			</div>';
    		    return $farr;
    		}
    	}
	    }
	
	$res  = get_all('op_master_table', '*', array('table_name' => $table_name,'is_edit'=>'YES','status'=>'ACTIVE'), 'display_id');
		
	$farr[] = "<form action ='$action' method ='post' enctype='multipart/form-data' id='update_frm' type='$type'>";
	$farr[] = "<input type='hidden' name='id' value='$id'>";
	$farr[] = "<input type='hidden' name='table_name' value='$table_name'>";
	$farr[] = "<input type='hidden' name='isedit' value='$isedit'>";
	$farr[]	= '<div class="alert alert-warning alert-dismissible" role="alert">
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	<div class="alert-icon">
		<i class="far fa-fw fa-bell"></i>
	</div>
	<div class="alert-message">
		<strong>Notice</strong> All field with star(*) marks is mandatory !
	</div>
</div>';
	$farr[]	= "<div class='row'>";
	
	if($res['count']>0)
	{
		foreach((array)$res['data'] as $row )
		{
			$value= get_data($row['table_name'], $id,$row['column_name'])['data'];
			$extra = ($row['is_required'] =='YES')? " required ":" ";
			$mark = ($row['is_required'] =='YES')? "<span class='text-danger' title='This field is mendatory'>* </span>":" ";
			$extra .= $row['extra'];
			$display_name = ($row['display_name'] ==null)? add_space($row['column_name']): $row['display_name'];
			$display_name = $display_name . $mark;
			if ($row['is_display'] == 'YES') {
				//echo $row['input_type'];

				switch ($row['input_type']) {
					case 'Date':
						$value = ($value == '0000-00-00' || $value == '' || !is_valid_date($value)) ? date('Y-m-d') : $value;
						$str = create_input($row['column_name'], 'date', $value, $display_name, $extra);
						break;

					case 'Datetime':
						$str= create_input($row['column_name'], 'datetime-local', $value, $display_name);
						break;
					
					case 'Color':
						$str= create_input($row['column_name'], 'Color', $value, $display_name);
						break;

					case 'Time':
						$str=  create_input($row['column_name'], 'time', $value, $display_name);
						break;
						
					case 'Year':
						$str ='<div class="form-group col-md-4">';
						$str .=	"<label class='op-label' >$display_name</label>";
						$str .= '<input type="number" class="form-control" min="1950" max="2099" step="1" name = "'.$row['column_name'].'" value="'.$value.'" />';
						$str .='</div>';
						break;
					
					case 'Month':
						$str=  create_input($row['column_name'], 'month', $value, $display_name);
						break;
					
					case 'Week':
						$str=  create_input($row['column_name'], 'week', $value, $display_name);
						break;
					
					case 'Label':
						$str ='<div class="form-group col-12">';
						$str .=	"<div class='label'><i class='fa $extra'> </i> $display_name</div></div>";
						break;

					case 'Camera':
						$path = ($value=='')?'no_image.jpg':$value;
						$str ='<div class="form-group col-md-4">';
						$str .=	"<label class='op-label' >$display_name</label>";
						$str .="<div id='display'>";
						$str .="<img src='upload/$path' width='150px' height='160px' id='result'></div>";
						$str .="<input type='hidden' name='{$row['column_name']}' id='targetimg' class='form-control' readonly value='$value'>";
						$str .="<span id='uploadarea' class='btn btn-secondary'>UPLOAD /CHANGE PHOTO </span></div></div>";
						break;

					case 'Whatsapp':
					case 'Mobile':
						$extra .= " maxlength='10' minlength='10' ";
						$str = create_input($row['column_name'], 'number', $value, $display_name, $extra);
						break;

					case 'Email':
						$str= create_input($row['column_name'], 'email', $value, $display_name, $extra);
						break;

					case 'Permission':
						$switch_str= show_switch($table_name,  $id, $row['column_name'], $value );
						$str= '<div class="form-group col-md-6">
						<label class="op-label"> ' . $display_name . ' </label>'.
						$switch_str
						.'</div>';
						break;

					case 'Number':
					case 'Rs':
						$extra .= " min =0 ";
						$str= create_input($row['column_name'], 'number', $value, $display_name, $extra);
						break;

					case 'RTF':
					    $value =($value =='')?$value:base64_decode($value);
						$str = '<div class="form-group col-12">
						<label class="op-label"> ' . $display_name . ' </label>' .
								'<textarea class="summernote" style="width:100" ' . $extra . ' id ="'. $row['column_name'].'">' . $value . '</textarea>
						<input type="hidden" class="summerdata" name="' . $row['column_name'] . '">
						</div>';
						break;

					
					case 'Multiline':
						$str = '<div class="form-group mt-2 col-4">
					<label class="op-label" > ' . $display_name . ' </label>
					<textarea  name="'.$row['column_name'] .'" id="'.$row['column_name'] .'" class="form-control ">' .$value. '</textarea>
					</div>';
						break;

    				case 'List-Dynamic':
                        $inputvalue = explode(',', $row['input_value']);
                        // Check if $extra contains the word "multiple"
                        $isMultiple = strpos($extra, 'multiple') !== false;
                        $nameAttribute = $isMultiple ? "{$row['column_name']}[]" : $row['column_name'];
                        $value = ($isMultiple ==1) ? explode(",",$value) : $value;
                        
                        $str = '<div class="form-group mt-2 col-md-4">
                                <label class="op-label" > ' . $display_name . ' </label>';
                        $str .= "<select name='{$nameAttribute}' id='{$row['column_name']}' class='form-select select2' $extra >";
                        $str .= dropdown_list($inputvalue[0], 'id', array_filter([$inputvalue[1] ?? null, $inputvalue[2] ?? null]), $value);
                        $str .= "</select></div>";
                        break;

						
					case 'List-Where':
						$inputvalue = explode(',', $row['input_value']);
						//print_r($inputvalue);
						$str = '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
						$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='form-select select2 required'>";
						
						$ex_arr = explode(",",$extra);
						$str .= dropdown_list($inputvalue[0], 'id', $inputvalue[1], array($ex_arr[0]=>$ex_arr[1]), $value);  
					    
						$str .= "</select></div>";
						break;

					case 'List-Static':
					case 'Status':
						$str ='';
						if ($isedit =='no' and $row['input_type'] =='Status'){
							$str =  create_input($row['column_name'], 'hidden', 'ACTIVE', $display_name, 'Readonly');
						}
						else{
						$option_value 	= get_data('op_config', $row['input_value'], 'option_value')['data'];
						$option_arr 	= explode(',', $option_value);
						$str =  '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
						$str .=  "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='form-select select2' $extra>";
						$str .=  dropdown($option_arr, $value);
						$str .= "</select></div>";
						}
						
						break;
					
					case 'CheckList-Dynamic':
						$inputvalue = explode(',', $row['input_value']);
						$arr_list = create_list($inputvalue[0],$inputvalue[1]);
						$str =  '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
						$str .= check_list($row['column_name'],$arr_list, $value);
						$str .=  '</div>';
						break;
					
					case 'CheckList-Static':
							$option_value 	= get_data('op_config', $row['input_value'], 'option_value')['data'];
							$option_arr 	= explode(',', $option_value);

							$str =  '<div class="form-group mt-2 col-md-4 " style="border:solid 1px #f6f7fcfc;border-radius:10px;background:#f6f7fcfc;">
							<label class="op-label" > ' . $display_name . ' </label>';
							$str .= check_list($row['column_name'],$option_arr, $value);
							$str .=  '</div>';
							break;
					
					case 'Photo':
					case 'Docs':
						$str =  '<div class="form-group  col-md-4 mt-3">
						<label class="op-label" >'. $display_name .'</label>
						<input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
						<input class="upload_img form-control" type="file" id="'.$row['column_name'].'" accept="image" data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
						<small> Only a valid images or Docs file. </small>';
						$str .=  '<div id="' . $row['column_name'] . '_display">';
						if ($isedit == 'yes') {
							//$str .=  show_img($value);
							$str .=  "<a href='../upload/{$value}' class='btn btn-border border-primary'> <i class='fa fa-download'></i> Download </a>";
						}
						$str .=  '</div></div>';
						break;
                    
                    case 'Multi-Photo':
						$str =  '<div class="form-group  col-md-4 mt-3">
						<label class="op-label" >'. $display_name .'</label>
						<input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
						<input class="upload_multi_img form-control" multiple type="file" id="'.$row['column_name'].'" accept="image/png, image/gif, image/jpeg, application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
						<small> Only Jpg and Png image. </small>';
						$str .=  '<div id="' . $row['column_name'] . '_display">';
						if ($isedit == 'yes') {
						    $images = explode(',',$value);
						    foreach($images as $img){
						        	$ext = pathinfo($img, PATHINFO_EXTENSION);
                		        if($ext =='pdf' || $ext=='docs'){
                		             $str .=  "<a href='../upload/$img' download>Download PDF</a>";
                		        }else{
                		             $str .=  show_img($img);
                		        }
						    }
						}
						$str .=  '</div></div>';
						break;
					case 'State':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = state_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='state_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;

					case 'District':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = district_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='district_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;

					case 'Block':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = block_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label class="op-label" > ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='block_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;
					case 'Popup':
						$str = create_input($row['column_name'], 'text', $value, $display_name, 'readonly');
						break;	
					case 'Edit-Box':
					    $str =  create_input($row['column_name'], 'text', $value, $display_name, 'edit_box');
						break;
						
					default:
						$str =  create_input($row['column_name'], 'text', $value, $display_name, $extra);

				}
				$farr[$row['column_name']] = $str;
			}
		}
	}
	$farr[]	= "</div></form>";

	return $farr;
}

function create_data_table($table_name, $res, $btn_arr=['btn_view'=>''], $idName ='data-tbl', $dtclass ='data-tbl')
{

	$str = '<div class="table-responsive1">';
	//$str .= '<table id="data-tbl" class="table table-bordered table-striped">';

	$str .= "<table class='$dtclass table table-bordered table-striped' id='$idName' >";
    	$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name, 'status'=>'ACTIVE','is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	if($dtclass =='data-tbl')
	{
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name, 'status'=>'ACTIVE','is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	else if($dtclass =='simple'){
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name, 'status'=>'ACTIVE','is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	else{
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name,'status'=>'ACTIVE', 'allow_global_search' => 'YES', 'is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	if ($get_cols['count'] > 0) {
		$str .= "<thead><tr><td>#</td>";
		foreach((array) $get_cols['data'] as $col) {
			$col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
			$str .= "<th>" . $col_name . "</th>";
		}
		if($dtclass !=''){
		$str .= "<th> Action </th>";
		}
		$str .= "</tr></thead>";
	}


	$str .= '<tbody>';

	if ($res['count'] > 0) {

		foreach((array)$res['data'] as $row) {
			$id = $row['id'];
			$jdata = json_encode($row);
			$str .= "<tr><td><input type='checkbox' value='$id' class='chk' data-json='$jdata'>";
		
			foreach((array) $get_cols['data'] as $col) {
				$ddata = $row[$col['column_name']]; //Display Data
				if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where')
				{
					$input  = explode(',',$col['input_value']);
					
					$dval1 = get_data($input[0],$ddata,$input[1])['data'];

					$dval2 = '';
					if(isset($input[2]) and $input[2]!='')
					{
					$dval2 = " [". get_data($input[0],$ddata,$input[2])['data']. "]"; 
					}
					$x = $dval1 . $dval2; //.$info;
				} 
				// else if($col['input_type']=='List-Static')
				// {
				// 	$x = get_data('op_config', $ddata,'option_name')['data']; 
			
				// } 
				else if($col['input_type']=='Permission')
				{
					$x = show_switch($table_name, $id, $col['column_name'], $ddata); 
					
				} 
				
				else if($col['input_type']=='Text-Info')
				{
					$x = btn_about($table_name, $id, $ddata); 
				} 
				
				else if($col['input_type']=='Edit-Box')
				{
				    $ddata= ($ddata=='')?'----':$ddata;
				    $x ="<span class='edit_box p-1' title='Double Click to Edit' data-table='$table_name' data-id='$id' data-column='{$col['column_name']}'> $ddata </span>";
				} 
				
				else if($col['input_type']=='RTF')
				{
					$x = display_value($ddata, $col['input_type'],'data-table'); 
			
				} 
				else {
					$x = display_value($ddata, $col['input_type']);
				}
				$str .= "<td>" . $x . "</td>";
			}
			
			//print_r($btn_arr);
			if($dtclass !='')
			{
				$str .= "<td align='right'>";
				foreach((array)$btn_arr as $fn=>$arg)
				{
					$str .= $fn($table_name, $id, $arg);
				}
				$str .= "</td>";
			}
			
			$str .= "</tr>";
		}
	}
	$str .= '</tbody>';

	$str .= '</table>';
	$str .= '</div>';
	return $str;
}



function create_report_table($table_name, $sql, $btn_arr='',  $dtclass ='report-tbl')
{
    global $con;
    $res = direct_sql($sql);
	$str = '<div class="table-responsive1">';
	$str .= "<table id='data-tbl' class='$dtclass table table-bordered table-striped'>";
	
    $get_cols  = mysqli_query($con, $sql);
	    $rct = mysqli_num_rows($get_cols);
	    $cct = mysqli_field_count($con);
	    $str .= "<thead><tr>";
	    for($i = 0; $i < $cct; $i++)
            {
                $field = mysqli_fetch_field_direct($get_cols, $i);
                $col_name = $field->name ;
                if( get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['count']>0)
                {
              	$cols[] = get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['data'][0];
              	$col = get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['data'][0];
              	$col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
                }
                else{
                $cols[] =  $field->name;  
                }
              
              $str .= "<th>" . $col_name . "</th>";
            }
            if($btn_arr !=''){
	        $str .= "<th> Action </th>";
		    }
            $str .= "</tr></thead>";

	$str .= '<tbody>';

	if ($res['count'] > 0) {

		foreach((array)$res['data'] as $row) {
			$row = array_merge($row,['table_name'=>$table_name]);
			$jdata = json_encode($row);
			$id = $row['id'];
			$str .= "<tr>";
			foreach((array) $cols as $col) {
				$ddata = $row[$col['column_name']]; //Display Data
				if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where')
				{
					$input  = explode(',',$col['input_value']);
					
					$dval1 = get_data($input[0],$ddata,$input[1])['data'];
                	$dval2 = '';
					if(isset($input[2]) and $input[2]!='')
					{
					$dval2 = " [". get_data($input[0],$ddata,$input[2])['data']. "]"; 
					}
					$x = $dval1 . $dval2; //.$info;
				} 
				else if($col['column_name']=='id') {
					$x = display_value($ddata, 'Checkbox','report-tbl', $jdata);
				}
				else if($col['input_type']=='Permission')
				{
					$x = show_switch($table_name, $id, $col['column_name'], $ddata); 
					
				} 
				
				else if($col['input_type']=='Text-Info')
				{
					$x = btn_about($table_name, $id, $ddata); 
			
				} 
				else {
					$x = display_value($ddata, $col['input_type'],'report-tbl', $jdata);
				}
				$str .= "<td>" . $x . "</td>";
			}
			
			//print_r($btn_arr);
			if($btn_arr !='')
			{
				$str .= "<td align='right'>";
				foreach((array)$btn_arr as $fn=>$arg)
				{
					$str .= $fn($table_name, $id, $arg);
				}
				$str .= "</td>";
			}
			
			$str .= "</tr>";
		}
	}
	$str .= '</tbody>';

	$str .= '</table>';
	$str .= '</div>';
	return $str;
}


function create_server_table($table_name)
    {
        // Create Basic data Table
        $str ="<table class='table table-bordered table-striped' id='server_table' >";
        $str .="<thead><tr>";
        $str .="<th>#</th>";
        
        // Get Visible Column List from Op_master_table
        $get_cols = get_all('op_master_table', '*', array('table_name' => $table_name,'status'=>'ACTIVE', 'is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
        $jdata[] =array("data"=>'id');
        $cols[] ='ID';
         foreach((array)$get_cols['data'] as $col) 
            {
                $jdata[] = array("data"=>$col['column_name']);
                $cols[] = $col_name= add_space($col['column_name']) ;
                $str .= "<th>". $col_name ."</th>"; // Create Column Header 
        	}
        	
        // completing table structure 
        $str .=  "<th>Action</th>";
        $str .=  "</tr></thead>";
	    $str .=  "<tbody></tbody>";
	    $str .=  "</table>";
                                    	    
        $cols[] ='Action';
        $jdata[] =array("data"=>'action');
        
        $res['json'] = json_encode($jdata);
        $res['html'] = $str;
     return $res;
    }

function display_value($value,$input_type, $container ='data-table')
{
	$str = '';
	switch ($input_type) {
		case 'Date':
			if ($value != '' ) {
				$str = date('d-M-Y', strtotime($value));
			}
			break;

		case 'Datetime':
			if ($value != '') {
			     $pattern = '/^[0-9]+$/';
			    if (preg_match($pattern, $value)) {
				   	$str = date('d-M-Y h:i A', $value);    
			    }
			    else{
			    	 $str = date('d-M-Y h:i A', strtotime($value));
			    }
			}
			break;

		case 'Time':
			if ($value != '') {
				$str = date('h:i A', strtotime($value));
			}
			break;
		
		case 'Email':
			$str = "<a href='mailto:$value'> $value </a>";
			break;
		
		case 'Whatsapp':
			$str = "<a href='https://wa.me/+91$value'> $value </a>";
			break;
			
		case 'RTF':
			if($container =='data-table')
				{
					$xvalue = substr($value, 0, 3)."****".substr($value, -3); 
					$str = " RTF";
				}
				else{
					$str = base64_decode($value);
				}
			break;

				
		case 'Mobile':
			if($container =='data-table' and $value !='')
				{
					$xvalue = substr($value, 0, 3)."****".substr($value, -3); 
					$str = "<a href='tel:$value'> $xvalue </a>";
				}
				else{
					$str = "<a href='tel:$value'> $value </a>";
				}
			
			break;

		case 'Bloodgroup':
			$str = "🩸 $value ";
			break;

		case 'Text-info':
			$str = "<button  data-bs-container='body' data-bs-toggle='popover' data-bs-placement='top' data-bs-content='$container'>$value</button>";
			break;

		case 'Rs':
			
				if($container =='data-table')
				{
					$str = "<span class='float-end'> ₹ ". $value ."</span>";
				}
				else if($container !='data-table' and $value !=''){
					$str = "₹ ". $value . " (". amount_in_word($value).") ";
				}
				else{
					$str = "₹ ";
				}
			break;


		case 'CheckList-Dynamic':
		case 'CheckList-Static':
			$str ='';
			if($container !='data-table' and $value !=''){
				$arr = explode("," , $value);
				foreach($arr as $el)
				{
					$str .="<li>". $el ."</li>";
				}
			}
			else{
				$str = $value ;
			}
		break;
		
		case 'Photo':
		case 'Image':
		case 'Camera':
			$str = show_img($value);
			break;
		
		case 'Multi-Photo':
		    $arr = explode(",", $value);
		    foreach($arr as $img)
		    {
			$ext = pathinfo($img, PATHINFO_EXTENSION);
		       
		         $str .=  "<a href='{$base_url}upload/$img' download>Download </a>";
		    }
		    break;
		
		case 'Status':
			$str = show_status($value);
			break;
			
		case 'Link':
		case 'Url':
		    $path = parse_url($value, PHP_URL_PATH);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); // Normalize extension to lowercase
            $str = ""; // Initialize
            
            // Check if value is a URL
            if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                $file_url = $value;
            } else {
                $file_url = $base_url .'upload/'. ltrim($value, '/'); // Append base URL to local file name
            }
            
            // Generate HTML based on file type
            if ($file_url) {
                // Image types
                if (in_array($ext, ['jpg', 'png', 'jpeg', 'gif'])) {
                    $str = "<a href='$file_url' download><img src='$file_url' width='100' height='60' alt='Image' /></a>";
                } 
                // Audio types
                elseif (in_array($ext, ['mp3', 'wav', 'amr', 'opus'])) {
                    $str = "<audio controls style='width:100px;height:25px;'><source src='$file_url' type='audio/$ext' /></audio>";
                } 
                // Video types
                elseif (in_array($ext, ['mp4', 'avi', 'dat'])) {
                    $str = "<video controls style='width:180px;height:100px;'><source src='$file_url' type='video/$ext' /></video>";
                } 
                // Other files
                else {
                    $str = "<a href='$file_url' class='badge badge-primary-light' target='_blank' download>Download</a>";
                }
            }

    		break;
		
		default:
		    
		    if($container =='data-table' and $value <>'')
				{
		    $link = "<a title='".$value."'>". substr($value,0,20)."..</a>";
		    $str = ($value!='' and strlen($value)<30)?$value:$link;    
				}
			else{
			    $str = $value;
			}

	}
	return $str;
}

function show_switch($table, $id, $name, $status, $activeLabel = 'ON', $inactiveLabel = 'OFF', $additionalAttributes = [])
{
    // Determine if the switch should be checked based on the status
    $isChecked = ($status=='')?'' : in_array(strtoupper($status), ['YES', 'ACTIVE', 'ON']);
    $checkedAttribute = $isChecked ? 'checked' : '';

    // Build additional attributes string
    $attrString = '';
    foreach ($additionalAttributes as $key => $value) {
        $attrString .= "$key='$value' ";
    }

    // Unique ID for the checkbox
    $checkboxId = "{$name}_{$id}";

    // Generate the switch HTML
    $str = "
        <div class='form-check form-switch'>
            <input class='$name yesno form-check-input' 
                   type='checkbox' 
                   role='switch' 
                   id='$checkboxId' 
                   data-table='$table' 
                   data-id='$id' 
                   data-column='$name' 
                   data-status='$status' 
                   $checkedAttribute 
                   $attrString>
            <label class='op-label'  class='form-check-label' for='$checkboxId'>
                " . ($isChecked ? $activeLabel : $inactiveLabel) . "
            </label>
        </div>";

    return $str;
}

function create_check($name, $data_source, $selected = null)
{
    // Convert comma-separated string to an array if necessary
    $data_source = is_string($data_source) ? explode(',', $data_source) : $data_source;

    // Convert selected values to an array if necessary
    $selected = is_string($selected) ? explode(',', $selected) : (array)$selected;

    // Prepare an array to hold checkbox data
    $checkboxes = [];

    // Build the checkbox data
    foreach (array_filter($data_source) as $item) {
        $item = trim($item);
        $checked = in_array($item, array_map('trim', $selected)) ? 'checked' : '';
        $checkboxes[] = [
            'value' => htmlspecialchars($item),
            'id' => "Checkbox_" . htmlspecialchars($item),
            'checked' => $checked,
            'label' => htmlspecialchars($item),
            'name' => htmlspecialchars($name . '[]')
        ];
    }

    // Generate HTML for Bootstrap checkboxes
    $output = '';
    foreach ($checkboxes as $checkbox) {
        $output .= "<div class='form-check'>";
        $output .= "<input class='form-check-input' type='checkbox' value='{$checkbox['value']}' id='{$checkbox['id']}' {$checkbox['checked']} name='{$checkbox['name']}'>";
        $output .= "<label class='form-check-label' for='{$checkbox['id']}'>{$checkbox['label']}</label>";
        $output .= "</div>";
    }

    // Handle form submission to retrieve selected values
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$name])) {
        $selected_values = implode(',', $_POST[$name]);
        $output .= "<div class='alert alert-success'>Selected Values: $selected_values</div>";
    }

    return $output;
}
// Menu Creator 

function create_menu()
{
	global $base_url;
	global $user_type;
	global $user_name;
	$str = '';
	$main = get_all('op_menu', '*', array('type' => 'MAIN', 'status' => 'ACTIVE'), 'display_id');
	foreach ((array) $main['data'] as $menu) {

		if ($main['count'] > 0) {
	
			// Checking Submenu Exist or Not 
			if($_SESSION['user_type']=='DEV' or $_SESSION['user_type']=='ADMIN' )
			{
			    $sub = get_all('op_menu', '*', array('type' => 'SUB', 'parent' => $menu['id'], 'status' => 'ACTIVE'), 'display_id');
			}
			else{
			    $sub = get_all('op_menu', '*', array('type' => 'SUB', 'parent' => $menu['id'], 'status' => 'ACTIVE',remove_space($_SESSION['user_type'])=>'YES'), 'display_id');
			}
			
			if ( $sub['count'] > 0) {
				$str .= "<li class='sidebar-item'>";
				$str .= "<a data-bs-target='#" . remove_space($menu['title']) . "' data-bs-toggle='collapse' class='sidebar-link collapsed'>";

				$str .= "<i class='fa fa-{$menu['icon']}'></i> <span class='align-middle'>{$menu['title']}</span></a>";

				if ($sub['count'] > 0 ) {
					$str .= "<ul id='" . remove_space($menu['title']) . "' class='sidebar-dropdown list-unstyled collapse' data-bs-parent='#sidebar'>";
					foreach ((array) $sub['data'] as $submenu) {
					    $permission = $submenu[remove_space($_SESSION['user_type'])];
						if ($_SESSION['user_type'] == 'DEV') {
							$str .= "<li class='sidebar-item'><a class='sidebar-link' href='$base_url{$submenu['link']}'>{$submenu['title']} <span class='badge bg-warning float-end'>{$submenu['extra']}</span></a></li>";
							
						} else if ($permission == 'YES') {
							$str .= "<li class='sidebar-item'><a class='sidebar-link' href='$base_url{$submenu['link']}'>{$submenu['title']} <span class='badge bg-warning float-end'>{$submenu['extra']}</span></a></li>";
						}
						
					}
					$str .= "</ul>";
				}
				$str .= "</li>";
			}
		}
	}
	return $str;
}


function sync_table($table_id)
{
	$table_name  = get_data('op_table',$table_id,'table_id')['data'];
	//check_table($table_name);
	
	$column = column_list($table_name);
	foreach((array)$column['data'] as $cname)
	{
		$data['table_name'] = $table_name;
		$data['column_name'] = $cname['COLUMN_NAME'];
	

		$fres = get_all('op_master_table', '*', $data );
		if ($fres['count'] == 0) {


			if($cname['COLUMN_NAME'] =='status')
			{
				$data['input_type'] = 'Status';
				$data['input_value'] = '32';
				$data['default_value'] = 'ACTIVE';
			}
			elseif($cname['DATA_TYPE'] =='date')
			{
				$data['input_type'] = 'Date';
			}
			elseif($cname['DATA_TYPE'] =='time')
			{
				$data['input_type'] = 'Time';
			}
			// elseif($cname['DATA_TYPE'] =='int')
			// {
			// 	$data['input_type'] = 'number';
			// }
			elseif($cname['DATA_TYPE'] =='timestamp')
			{
				$data['input_type'] = 'Datetime';
			}
			else{
				$data['input_type'] = 'Text';
			}
			insert_data('op_master_table', $data);
		} else {
			$id = $fres['data'][0]['id'];
			update_data('op_master_table', $data, $id);
		}
	}
	$sql = "UPDATE op_master_table set is_edit='NO' where column_name='created_at' or column_name='updated_at' or column_name='created_by' or column_name='updated_by' or column_name='id'";
	$res0 = direct_sql($sql, 'set');

	return $res0;
}


function add_in_menu($table_id)
{
	$table_name  = get_data('op_table',$table_id,'table_id')['data'];
	create_add_page(remove_space($table_name), );
	create_manage_page(remove_space($table_name), );

			$data0['type'] ='MAIN';
			$data0['parent'] =0;
			$data0['title'] = add_space($table_name);
			$data0['link'] = "#";
			$data0['status'] ='ACTIVE';
			$data0['icon'] ='table';
			$data0['table_id'] =$table_id;

			$mainmenu = insert_data('op_menu',$data0);
			$page_id =$mainmenu['id'];

			$data1['type'] ='SUB';
			$data1['parent'] =$page_id;
			$data1['title'] ='Add '.add_space($table_name);
			//$data1['link'] =remove_space($table_name).'/add'; // Using Folder
			$data1['link'] ='public/'.remove_space($table_name).'_add';
			$data1['status'] ='ACTIVE';
			$data1['table_id'] =$table_id;
			insert_data('op_menu', $data1);

			$data2['type'] ='SUB';
			$data2['parent'] =$page_id;
			$data2['title'] ='Manage '.add_space($table_name);
			$data2['link'] ='public/'.remove_space($table_name).'_manage';
			$data2['status'] ='ACTIVE';
			$data2['table_id'] =$table_id;
			insert_data('op_menu', $data2);
			sync_table($table_name);
			create_role('DEV');
}


function create_add_page($table_name, $create_folder ="NO")
	{
		global $user_name;
		global $user_type;
		
		if(strtoupper($create_folder) =="YES")
	    {
	        // Check Folder Exist or Create
    	    if (!file_exists($table_name)) { 
    		    mkdir("../".$table_name, 0755, true);
    	    }
    		// With Seperate Folder
    		$myFile = "../".$table_name."/add.php"; // or .php  
	    }
	    else{
    		// With Prefix Table Name 
    		$myFile = "../public/".$table_name."_add.php"; // or .php 
	    }
	    
		$fh = fopen($myFile, "w"); // or die("error");  
		$stringData = '<?php require_once("../system/all_header.php"); 

        $table_name = "'.$table_name.'";
        
        if (isset($_GET["link"]) and $_GET["link"] != "") {
            $branch = decode($_GET["link"]);
            $id = $branch["id"];
            $isedit ="yes";
        } else {
        
            $branch = insert_row($table_name);
            $id = $branch["id"];
            $isedit ="no";
        }
        
        if ($id != "") {
            $res = get_data($table_name, $id);
            if ($res["count"] > 0 and $res["status"] == "success") {
                extract($res["data"]);
            }
        }
        ?>
        
            <main class="content">
                <div class="container-fluid p-0">
        
                    <h1 class="h3 mb-3" >'.add_space($table_name).'</h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> '.add_space($table_name) .' Details
                                     <?= btn_save($table_name); ?>
                                   
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php $form  = create_form($table_name, $id, $isedit); 
									$table_id = get_data("op_table", $table_name, "id","table_id")["data"];
                                    $res =  get_multi_data("op_role", array("table_id"=>$table_id, "role_name"=>$user_type));

									if($res["count"]>0)
									{
										
										foreach((array)$form as $el)
										{
											echo $el;
										}
										
									}
									
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
        
                </div>
            </main>
        
        <?php 
        require_once("../system/footer.php"); ?>';   
		fwrite($fh, $stringData);
		fclose($fh);
	}

function create_manage_page( $table_name, $create_folder="NO")	
	{
	    if(strtoupper($create_folder) =="YES")
	    {
	        // Check Folder Exist or Create
    	    if (!file_exists($table_name)) { 
    		    mkdir("../".$table_name, 0755, true);
    	    }
    		// With Seperate Folder
    		$myFile = "../".$table_name."/manage.php"; // or .php  
	    }
	    else{
    		// With Prefix Table Name 
    		$myFile = "../public/".$table_name."_manage.php"; // or .php 
	    }
    	$fh = fopen($myFile, "w"); // or die("error"); 
    
		$stringData = '<?php require_once("../system/all_header.php"); 
        $table_name = "'.$table_name.'";
        $res= create_server_table($table_name);

        ?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3"><?= add_space($table_name) ?></h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">All <?= add_space($table_name) ?> <?= btn_add($table_name) ?>

                            <span class="float-end">
                            <div class="float-end">
								<button class="btn btn-warning btn-sm"> <input type="checkbox" title="select All" id="selectAll" class="btn btn-dark btn-sm"> </button>
								<?= btn_remove_multiple($table_name) ?>
								<?= btn_delete_multiple($table_name) ?>
						
								<button class="btn btn-primary btn-sm my-1"   title="Show /Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="fa fa-columns"></i></button>
								<button class="btn btn-info btn-sm" title="Download XLS" onclick="exportxls()"> <i class="fa fa-file-excel"></i> </button>
							</div>
                            </span>
							</h5>
						</div>
						<div class="card-body">
                            <?php
							
                            echo $res["html"]; 
                            
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>
<?php 
require_once("../system/footer.php"); ?>';
fwrite($fh, $stringData);
fclose($fh);	
    
}


function create_role($role_name)
{
	$ct=0;
	$res = get_all('op_table');
	foreach((array)$res['data'] as $table)
	{
		$find_role  =get_all('op_role','*',array('table_id'=>$table['id'], 'role_name'=>$role_name));

		if($find_role['count']==0)
		{
			insert_data('op_role', array('table_id'=>$table['id'], 'role_name'=>$role_name, 'show_menu'=>'YES','can_view'=>'YES', 'can_add'=>'YES','can_edit'=>'NO','can_remove'=>'NO','status'=>$table['status']));
			$ct++;
		}
		
	}
	return $ct; 
}


function create_widget($table_name, $status = 'ACTIVE')
{
    global $user_type;
    global $branch_id;
    $table_id = get_data('op_table',$table_name,'id','table_id')['data'];
    if($user_type=='ADMIN' or $user_type=='DEV')
    {
	    $ct = get_all($table_name, '*', array('status' => $status))['count'];
    }
    else{
        $ct = get_all($table_name, '*', array('status' => $status,'branch_id'=>$branch_id))['count'];
    }
	$iconres = get_multi_data('op_menu', array('type' => 'MAIN', 'table_id' => $table_id));
	if ($iconres['count'] > 0) {
		$icon = $iconres['data'][0]['icon'];
	} else {
		$icon = 'table';
	}

	$str = '<div class="col-sm-6 col-xl-3">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col mt-0">
						<h5 class="card-title">' . add_space($table_name) . '</h5>
					</div>

					<div class="col-auto">
						<div class="stat text-warning">
							<i class="fa fa-' . $icon . '"></i>
						</div>
					</div>
				</div>
				<h1 class="mt-1 mb-3">' . get_all($table_name)['count'] . '</h1>
				<div class="mb-0">
					<a href="../public/' . $table_name . '_manage.php" class="badge badge-warning-light"> <i class="mdi mdi-arrow-bottom-right"></i>' . $ct . '</a>
					<span class="text-muted">' . $status . '</span>
				</div>
			</div>
		</div>
	</div>';

	return $str;
}

function recent_widget($table_name)
	{
		
		$cols = create_list('op_master_table','column_name',array('table_name'=> $table_name, 'allow_global_search'=>'YES'));
		
		if(is_array($cols))
		{
		$cols_list = implode(',',$cols);
		
		$sql ="select id, $cols_list from $table_name where status not in ('AUTO','DELETED') order by id desc limit 10";
		$res = direct_sql($sql);

		
		$str ='<div class="col-12 col-lg-6 d-flex">
		<div class="card flex-fill w-100">
			<div class="card-header">
				
				<h5 class="card-title mb-0">'. add_space($table_name).'</h5>
			</div>
			<div class="card-body pt-2 pb-3">'.
				create_data_table($table_name,$res, [], '')
			.'</div>
		</div>
		</div>';

		return $str;
		}
	}
	

function create_backup($tables = '*')
	{
	global $con;
	$return ='';
		//get all of the tables
		if($tables == '*')
		{
			$tables = array();
			$result = mysqli_query($con, 'SHOW TABLES');
			while($row = mysqli_fetch_array($result))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}

		//cycle through
		foreach($tables as $table)
		{
			global $inst_name;
			$site_name= strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $inst_name));
			$result = mysqli_query($con, 'SELECT * FROM '.$table);
			$num_fields = mysqli_num_fields($result);

			$return.= 'DROP TABLE '.$table.';';
			$row2 = mysqli_fetch_array(mysqli_query($con, 'SHOW CREATE TABLE '.$table));
			$return.= "\n\n".$row2[1].";\n\n";

			for ($i = 0; $i < $num_fields; $i++)
			{
				while($row = mysqli_fetch_array($result))
				{
					$return.= 'INSERT INTO '.$table.' VALUES(';
					for($j=0; $j<$num_fields; $j++)
					{
						//$row[$j] = addslashes($row[$j]);
						//$row[$j] = preg_replace("/\\n/","\\n",$row[$j]);
						if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
						if ($j<($num_fields-1)) { $return.= ','; }
					}
					$return.= ");\n";
				}
			}
			$return.="\n\n\n";
		}

		//save file
		
		if (!file_exists('../backup')) {
		    mkdir('../backup', 0777, true);
		}
		
		
		$filename = '../backup/db-'.$site_name .'-'.date('ymd').'.sql.gz';
		$handle = fopen($filename,'w+');
		$gzdata = gzencode($return, 9);
		fwrite($handle,$gzdata);
		fclose($handle);
		return $filename;
	}


function push_msg($message, $title='', $link='') {
    global $base_url;
    global $push_token;
    global $inst_name ;
    $url = 'https://api.truepush.com/api/v1/createCampaign'; // Replace with the actual API endpoint URL
    
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjcmVhdGVkRGF0ZSI6MTY4NTYyMTk4OTI5MCwiaWQiOiI2NDc4OGM0NWY0NWRjYWZlODA1NzRiN2UiLCJ1c2VySWQiOiI2NDc3OWNlZmYzZjMyNjJkODNhOTExMTAiLCJpYXQiOjE2ODU2MjE5ODl9.bfb_S1J2Tkbj6ZAyN4n57JyPD4jb3i46j_K0CG6qxhI'; // Replace with your REST API token

    $headers = array(
        'Authorization: ' . $push_token,
        'Content-Type: application/json'
    );

    $data = array(
        'title' => ($title=='')?$inst_name:$title,
        'message' => 'Notification with API message',
        'link' => $base_url,
        'image' => $base_url.'img/push.jpg',
        'icon' =>  $base_url.'img/logo.png',
        'scheduled' => false,
        'tag'=> array("op_user_name"),
        'buttons' => array(
            array(
                'text' => 'Show Details',
                'link' => ($link=='')?$base_url:$link,
            ),
            array(
                'text' => 'WhatsApp',
                'link' => "https://wa.me/919431426600?text=$inst_name"
            )
        )
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}


	
function get_filesize($filename) {

    $bytes = filesize($filename);
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }
        return $bytes;
}

// Activate All VARIIABLE ans Settings 
//++++++++++++++++++++++++++++++++++++++++++++++++//

$a = all_config();
extract($a);
extract(get_data('op_settings',1)['data']);

//++++++++++++++++++++++++++++++++++++++++++++++++//

?>