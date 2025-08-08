Here is the PHP code for the backend of your Pharmacy Management System, structured into separate files as requested.

db.php

This file handles the database connection using PDO.

<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'hrm_db';
$username = 'root';
$password = 'mysql';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // echo "Database connection successful!"; // For testing purposes, remove in production
} catch (PDOException $e) {
    // If connection fails, output error message and terminate
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

?>

create_user.php

This script handles the creation of a new user in the tbl_crm_user table.

<?php

require_once 'db.php'; // Include the database connection file

header('Content-Type: application/json'); // Set content type to JSON

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true); // Decode JSON data into an associative array

    // Validate and sanitize input
    $user_id = filter_var($data['user_id'] ?? '', FILTER_SANITIZE_STRING);
    $user_pass = $data['user_pass'] ?? ''; // Password will be hashed, no direct sanitization
    $user_department = filter_var($data['user_department'] ?? '', FILTER_SANITIZE_STRING);
    $user_type = filter_var($data['user_type'] ?? '', FILTER_SANITIZE_STRING);
    $user_status = filter_var($data['user_status'] ?? '', FILTER_SANITIZE_STRING);

    // Basic validation
    if (empty($user_id) || empty($user_pass) || empty($user_department) || empty($user_type) || empty($user_status)) {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'All fields are required.']);
        exit();
    }

    // Hash the password for security
    $hashed_password = password_hash($user_pass, PASSWORD_DEFAULT);

    try {
        // Prepare the SQL statement to insert a new user
        $stmt = $pdo->prepare("INSERT INTO tbl_crm_user (user_id, user_pass, user_department, user_type, user_status) VALUES (:user_id, :user_pass, :user_department, :user_type, :user_status)");

        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_pass', $hashed_password);
        $stmt->bindParam(':user_department', $user_department);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':user_status', $user_status);

        // Execute the statement
        if ($stmt->execute()) {
            http_response_code(201); // Created
            echo json_encode(['message' => 'User created successfully.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'Failed to create user.']);
        }
    } catch (PDOException $e) {
        // Check for duplicate entry error (e.g., user_id is a primary key or unique)
        if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation
            http_response_code(409); // Conflict
            echo json_encode(['message' => 'User ID already exists.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Method not allowed. Only POST requests are accepted.']);
}

?>

products.php

This script handles CRUD operations for products.
Assumed table: tbl_products (product_id INT PRIMARY KEY AUTO_INCREMENT, product_name VARCHAR(255), manufacturer_id INT, price DECIMAL(10,2), description TEXT, active_ingredient_id INT, stock_quantity INT)

<?php

require_once 'db.php'; // Include the database connection file

header('Content-Type: application/json'); // Set content type to JSON

$method = $_SERVER['REQUEST_METHOD']; // Get the HTTP method

switch ($method) {
    case 'GET':
        // Handle GET request (Read)
        $product_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

        if ($product_id) {
            // Fetch a single product
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_products WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->execute();
                $product = $stmt->fetch();

                if ($product) {
                    http_response_code(200);
                    echo json_encode($product);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Product not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            // Fetch all products
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_products");
                $products = $stmt->fetchAll();

                http_response_code(200);
                echo json_encode($products);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        // Handle POST request (Create)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate and sanitize input
        $product_name = filter_var($data['product_name'] ?? '', FILTER_SANITIZE_STRING);
        $manufacturer_id = filter_var($data['manufacturer_id'] ?? '', FILTER_VALIDATE_INT);
        $price = filter_var($data['price'] ?? '', FILTER_VALIDATE_FLOAT);
        $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_STRING);
        $active_ingredient_id = filter_var($data['active_ingredient_id'] ?? '', FILTER_VALIDATE_INT);
        $stock_quantity = filter_var($data['stock_quantity'] ?? '', FILTER_VALIDATE_INT);

        if (empty($product_name) || $manufacturer_id === false || $price === false || $active_ingredient_id === false || $stock_quantity === false) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing or invalid required fields.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_products (product_name, manufacturer_id, price, description, active_ingredient_id, stock_quantity) VALUES (:product_name, :manufacturer_id, :price, :description, :active_ingredient_id, :stock_quantity)");
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':manufacturer_id', $manufacturer_id, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':active_ingredient_id', $active_ingredient_id, PDO::PARAM_INT);
            $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Product created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create product.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Handle PUT request (Update)
        $product_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$product_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Product ID is required for update.']);
            exit();
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Prepare fields for update
        $updateFields = [];
        $bindParams = [':product_id' => $product_id];

        if (isset($data['product_name'])) {
            $updateFields[] = 'product_name = :product_name';
            $bindParams[':product_name'] = filter_var($data['product_name'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['manufacturer_id'])) {
            $updateFields[] = 'manufacturer_id = :manufacturer_id';
            $bindParams[':manufacturer_id'] = filter_var($data['manufacturer_id'], FILTER_VALIDATE_INT);
        }
        if (isset($data['price'])) {
            $updateFields[] = 'price = :price';
            $bindParams[':price'] = filter_var($data['price'], FILTER_VALIDATE_FLOAT);
        }
        if (isset($data['description'])) {
            $updateFields[] = 'description = :description';
            $bindParams[':description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['active_ingredient_id'])) {
            $updateFields[] = 'active_ingredient_id = :active_ingredient_id';
            $bindParams[':active_ingredient_id'] = filter_var($data['active_ingredient_id'], FILTER_VALIDATE_INT);
        }
        if (isset($data['stock_quantity'])) {
            $updateFields[] = 'stock_quantity = :stock_quantity';
            $bindParams[':stock_quantity'] = filter_var($data['stock_quantity'], FILTER_VALIDATE_INT);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);

        try {
            $stmt = $pdo->prepare("UPDATE tbl_products SET $setClause WHERE product_id = :product_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Product updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Product not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update product.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Handle DELETE request (Delete)
        $product_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

        if (!$product_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Product ID is required for deletion.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Product deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Product not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete product.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        // Method Not Allowed
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

manufacturers.php

This script handles CRUD operations for manufacturers.
Assumed table: tbl_manufacturers (manufacturer_id INT PRIMARY KEY AUTO_INCREMENT, manufacturer_name VARCHAR(255), contact_person VARCHAR(255), phone VARCHAR(50), email VARCHAR(255))

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $manufacturer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($manufacturer_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_manufacturers WHERE manufacturer_id = :manufacturer_id");
                $stmt->bindParam(':manufacturer_id', $manufacturer_id, PDO::PARAM_INT);
                $stmt->execute();
                $manufacturer = $stmt->fetch();
                if ($manufacturer) {
                    http_response_code(200);
                    echo json_encode($manufacturer);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Manufacturer not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_manufacturers");
                $manufacturers = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($manufacturers);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $manufacturer_name = filter_var($data['manufacturer_name'] ?? '', FILTER_SANITIZE_STRING);
        $contact_person = filter_var($data['contact_person'] ?? '', FILTER_SANITIZE_STRING);
        $phone = filter_var($data['phone'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if (empty($manufacturer_name)) {
            http_response_code(400);
            echo json_encode(['message' => 'Manufacturer name is required.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_manufacturers (manufacturer_name, contact_person, phone, email) VALUES (:manufacturer_name, :contact_person, :phone, :email)");
            $stmt->bindParam(':manufacturer_name', $manufacturer_name);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Manufacturer created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create manufacturer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $manufacturer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$manufacturer_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Manufacturer ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':manufacturer_id' => $manufacturer_id];
        if (isset($data['manufacturer_name'])) {
            $updateFields[] = 'manufacturer_name = :manufacturer_name';
            $bindParams[':manufacturer_name'] = filter_var($data['manufacturer_name'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['contact_person'])) {
            $updateFields[] = 'contact_person = :contact_person';
            $bindParams[':contact_person'] = filter_var($data['contact_person'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['phone'])) {
            $updateFields[] = 'phone = :phone';
            $bindParams[':phone'] = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['email'])) {
            $updateFields[] = 'email = :email';
            $bindParams[':email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_manufacturers SET $setClause WHERE manufacturer_id = :manufacturer_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Manufacturer updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Manufacturer not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update manufacturer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $manufacturer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$manufacturer_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Manufacturer ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_manufacturers WHERE manufacturer_id = :manufacturer_id");
            $stmt->bindParam(':manufacturer_id', $manufacturer_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Manufacturer deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Manufacturer not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete manufacturer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

active_ingredients.php

This script handles CRUD operations for active ingredients.
Assumed table: tbl_active_ingredients (ingredient_id INT PRIMARY KEY AUTO_INCREMENT, ingredient_name VARCHAR(255), description TEXT)

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $ingredient_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($ingredient_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_active_ingredients WHERE ingredient_id = :ingredient_id");
                $stmt->bindParam(':ingredient_id', $ingredient_id, PDO::PARAM_INT);
                $stmt->execute();
                $ingredient = $stmt->fetch();
                if ($ingredient) {
                    http_response_code(200);
                    echo json_encode($ingredient);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Active Ingredient not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_active_ingredients");
                $ingredients = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($ingredients);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $ingredient_name = filter_var($data['ingredient_name'] ?? '', FILTER_SANITIZE_STRING);
        $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_STRING);

        if (empty($ingredient_name)) {
            http_response_code(400);
            echo json_encode(['message' => 'Ingredient name is required.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_active_ingredients (ingredient_name, description) VALUES (:ingredient_name, :description)");
            $stmt->bindParam(':ingredient_name', $ingredient_name);
            $stmt->bindParam(':description', $description);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Active Ingredient created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create active ingredient.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $ingredient_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$ingredient_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Active Ingredient ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':ingredient_id' => $ingredient_id];
        if (isset($data['ingredient_name'])) {
            $updateFields[] = 'ingredient_name = :ingredient_name';
            $bindParams[':ingredient_name'] = filter_var($data['ingredient_name'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['description'])) {
            $updateFields[] = 'description = :description';
            $bindParams[':description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_active_ingredients SET $setClause WHERE ingredient_id = :ingredient_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Active Ingredient updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Active Ingredient not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update active ingredient.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $ingredient_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$ingredient_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Active Ingredient ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_active_ingredients WHERE ingredient_id = :ingredient_id");
            $stmt->bindParam(':ingredient_id', $ingredient_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Active Ingredient deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Active Ingredient not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete active ingredient.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

inventory.php

This script handles CRUD operations for inventory.
Assumed table: tbl_inventory (inventory_id INT PRIMARY KEY AUTO_INCREMENT, product_id INT, batch_number VARCHAR(255), expiry_date DATE, quantity INT, location VARCHAR(255))

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $inventory_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($inventory_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_inventory WHERE inventory_id = :inventory_id");
                $stmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
                $stmt->execute();
                $item = $stmt->fetch();
                if ($item) {
                    http_response_code(200);
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Inventory item not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_inventory");
                $items = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($items);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $product_id = filter_var($data['product_id'] ?? '', FILTER_VALIDATE_INT);
        $batch_number = filter_var($data['batch_number'] ?? '', FILTER_SANITIZE_STRING);
        $expiry_date = filter_var($data['expiry_date'] ?? '', FILTER_SANITIZE_STRING); // YYYY-MM-DD
        $quantity = filter_var($data['quantity'] ?? '', FILTER_VALIDATE_INT);
        $location = filter_var($data['location'] ?? '', FILTER_SANITIZE_STRING);

        if ($product_id === false || empty($batch_number) || empty($expiry_date) || $quantity === false) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing or invalid required fields.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_inventory (product_id, batch_number, expiry_date, quantity, location) VALUES (:product_id, :batch_number, :expiry_date, :quantity, :location)");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':batch_number', $batch_number);
            $stmt->bindParam(':expiry_date', $expiry_date);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':location', $location);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Inventory item created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create inventory item.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $inventory_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$inventory_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Inventory ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':inventory_id' => $inventory_id];
        if (isset($data['product_id'])) {
            $updateFields[] = 'product_id = :product_id';
            $bindParams[':product_id'] = filter_var($data['product_id'], FILTER_VALIDATE_INT);
        }
        if (isset($data['batch_number'])) {
            $updateFields[] = 'batch_number = :batch_number';
            $bindParams[':batch_number'] = filter_var($data['batch_number'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['expiry_date'])) {
            $updateFields[] = 'expiry_date = :expiry_date';
            $bindParams[':expiry_date'] = filter_var($data['expiry_date'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['quantity'])) {
            $updateFields[] = 'quantity = :quantity';
            $bindParams[':quantity'] = filter_var($data['quantity'], FILTER_VALIDATE_INT);
        }
        if (isset($data['location'])) {
            $updateFields[] = 'location = :location';
            $bindParams[':location'] = filter_var($data['location'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_inventory SET $setClause WHERE inventory_id = :inventory_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Inventory item updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Inventory item not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update inventory item.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $inventory_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$inventory_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Inventory ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_inventory WHERE inventory_id = :inventory_id");
            $stmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Inventory item deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Inventory item not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete inventory item.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

suppliers.php

This script handles CRUD operations for suppliers.
Assumed table: tbl_suppliers (supplier_id INT PRIMARY KEY AUTO_INCREMENT, supplier_name VARCHAR(255), contact_person VARCHAR(255), phone VARCHAR(50), email VARCHAR(255), address TEXT)

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $supplier_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($supplier_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_suppliers WHERE supplier_id = :supplier_id");
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
                $supplier = $stmt->fetch();
                if ($supplier) {
                    http_response_code(200);
                    echo json_encode($supplier);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Supplier not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_suppliers");
                $suppliers = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($suppliers);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $supplier_name = filter_var($data['supplier_name'] ?? '', FILTER_SANITIZE_STRING);
        $contact_person = filter_var($data['contact_person'] ?? '', FILTER_SANITIZE_STRING);
        $phone = filter_var($data['phone'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $address = filter_var($data['address'] ?? '', FILTER_SANITIZE_STRING);

        if (empty($supplier_name)) {
            http_response_code(400);
            echo json_encode(['message' => 'Supplier name is required.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_suppliers (supplier_name, contact_person, phone, email, address) VALUES (:supplier_name, :contact_person, :phone, :email, :address)");
            $stmt->bindParam(':supplier_name', $supplier_name);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Supplier created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create supplier.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $supplier_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$supplier_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Supplier ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':supplier_id' => $supplier_id];
        if (isset($data['supplier_name'])) {
            $updateFields[] = 'supplier_name = :supplier_name';
            $bindParams[':supplier_name'] = filter_var($data['supplier_name'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['contact_person'])) {
            $updateFields[] = 'contact_person = :contact_person';
            $bindParams[':contact_person'] = filter_var($data['contact_person'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['phone'])) {
            $updateFields[] = 'phone = :phone';
            $bindParams[':phone'] = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['email'])) {
            $updateFields[] = 'email = :email';
            $bindParams[':email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        }
        if (isset($data['address'])) {
            $updateFields[] = 'address = :address';
            $bindParams[':address'] = filter_var($data['address'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_suppliers SET $setClause WHERE supplier_id = :supplier_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Supplier updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Supplier not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update supplier.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $supplier_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$supplier_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Supplier ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_suppliers WHERE supplier_id = :supplier_id");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Supplier deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Supplier not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete supplier.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

purchase_orders.php

This script handles CRUD operations for purchase orders.
Assumed table: tbl_purchase_orders (po_id INT PRIMARY KEY AUTO_INCREMENT, supplier_id INT, order_date DATE, total_amount DECIMAL(10,2), status VARCHAR(50))

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $po_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($po_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_purchase_orders WHERE po_id = :po_id");
                $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
                $stmt->execute();
                $po = $stmt->fetch();
                if ($po) {
                    http_response_code(200);
                    echo json_encode($po);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Purchase Order not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_purchase_orders");
                $pos = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($pos);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $supplier_id = filter_var($data['supplier_id'] ?? '', FILTER_VALIDATE_INT);
        $order_date = filter_var($data['order_date'] ?? '', FILTER_SANITIZE_STRING); // YYYY-MM-DD
        $total_amount = filter_var($data['total_amount'] ?? '', FILTER_VALIDATE_FLOAT);
        $status = filter_var($data['status'] ?? '', FILTER_SANITIZE_STRING);

        if ($supplier_id === false || empty($order_date) || $total_amount === false || empty($status)) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing or invalid required fields.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_purchase_orders (supplier_id, order_date, total_amount, status) VALUES (:supplier_id, :order_date, :total_amount, :status)");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(':order_date', $order_date);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':status', $status);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Purchase Order created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create purchase order.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $po_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$po_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Purchase Order ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':po_id' => $po_id];
        if (isset($data['supplier_id'])) {
            $updateFields[] = 'supplier_id = :supplier_id';
            $bindParams[':supplier_id'] = filter_var($data['supplier_id'], FILTER_VALIDATE_INT);
        }
        if (isset($data['order_date'])) {
            $updateFields[] = 'order_date = :order_date';
            $bindParams[':order_date'] = filter_var($data['order_date'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['total_amount'])) {
            $updateFields[] = 'total_amount = :total_amount';
            $bindParams[':total_amount'] = filter_var($data['total_amount'], FILTER_VALIDATE_FLOAT);
        }
        if (isset($data['status'])) {
            $updateFields[] = 'status = :status';
            $bindParams[':status'] = filter_var($data['status'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_purchase_orders SET $setClause WHERE po_id = :po_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Purchase Order updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Purchase Order not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update purchase order.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $po_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$po_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Purchase Order ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_purchase_orders WHERE po_id = :po_id");
            $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Purchase Order deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Purchase Order not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete purchase order.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

sales.php

This script handles CRUD operations for sales.
Assumed table: tbl_sales (sale_id INT PRIMARY KEY AUTO_INCREMENT, customer_id INT, sale_date DATE, total_amount DECIMAL(10,2), status VARCHAR(50))

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sale_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($sale_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_sales WHERE sale_id = :sale_id");
                $stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
                $stmt->execute();
                $sale = $stmt->fetch();
                if ($sale) {
                    http_response_code(200);
                    echo json_encode($sale);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Sale not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_sales");
                $sales = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($sales);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $customer_id = filter_var($data['customer_id'] ?? '', FILTER_VALIDATE_INT);
        $sale_date = filter_var($data['sale_date'] ?? '', FILTER_SANITIZE_STRING); // YYYY-MM-DD
        $total_amount = filter_var($data['total_amount'] ?? '', FILTER_VALIDATE_FLOAT);
        $status = filter_var($data['status'] ?? '', FILTER_SANITIZE_STRING);

        if ($customer_id === false || empty($sale_date) || $total_amount === false || empty($status)) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing or invalid required fields.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_sales (customer_id, sale_date, total_amount, status) VALUES (:customer_id, :sale_date, :total_amount, :status)");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':sale_date', $sale_date);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':status', $status);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Sale created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create sale.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $sale_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$sale_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Sale ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':sale_id' => $sale_id];
        if (isset($data['customer_id'])) {
            $updateFields[] = 'customer_id = :customer_id';
            $bindParams[':customer_id'] = filter_var($data['customer_id'], FILTER_VALIDATE_INT);
        }
        if (isset($data['sale_date'])) {
            $updateFields[] = 'sale_date = :sale_date';
            $bindParams[':sale_date'] = filter_var($data['sale_date'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['total_amount'])) {
            $updateFields[] = 'total_amount = :total_amount';
            $bindParams[':total_amount'] = filter_var($data['total_amount'], FILTER_VALIDATE_FLOAT);
        }
        if (isset($data['status'])) {
            $updateFields[] = 'status = :status';
            $bindParams[':status'] = filter_var($data['status'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_sales SET $setClause WHERE sale_id = :sale_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Sale updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Sale not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update sale.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $sale_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$sale_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Sale ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_sales WHERE sale_id = :sale_id");
            $stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Sale deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Sale not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete sale.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

customers.php

This script handles CRUD operations for customers.
Assumed table: tbl_customers (customer_id INT PRIMARY KEY AUTO_INCREMENT, customer_name VARCHAR(255), phone VARCHAR(50), email VARCHAR(255), address TEXT)

<?php

require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $customer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if ($customer_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbl_customers WHERE customer_id = :customer_id");
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
                $customer = $stmt->fetch();
                if ($customer) {
                    http_response_code(200);
                    echo json_encode($customer);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Customer not found.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            try {
                $stmt = $pdo->query("SELECT * FROM tbl_customers");
                $customers = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($customers);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $customer_name = filter_var($data['customer_name'] ?? '', FILTER_SANITIZE_STRING);
        $phone = filter_var($data['phone'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $address = filter_var($data['address'] ?? '', FILTER_SANITIZE_STRING);

        if (empty($customer_name)) {
            http_response_code(400);
            echo json_encode(['message' => 'Customer name is required.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_customers (customer_name, phone, email, address) VALUES (:customer_name, :phone, :email, :address)");
            $stmt->bindParam(':customer_name', $customer_name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Customer created successfully.', 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create customer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $customer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$customer_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Customer ID is required for update.']);
            exit();
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $updateFields = [];
        $bindParams = [':customer_id' => $customer_id];
        if (isset($data['customer_name'])) {
            $updateFields[] = 'customer_name = :customer_name';
            $bindParams[':customer_name'] = filter_var($data['customer_name'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['phone'])) {
            $updateFields[] = 'phone = :phone';
            $bindParams[':phone'] = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        }
        if (isset($data['email'])) {
            $updateFields[] = 'email = :email';
            $bindParams[':email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        }
        if (isset($data['address'])) {
            $updateFields[] = 'address = :address';
            $bindParams[':address'] = filter_var($data['address'], FILTER_SANITIZE_STRING);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields provided for update.']);
            exit();
        }

        $setClause = implode(', ', $updateFields);
        try {
            $stmt = $pdo->prepare("UPDATE tbl_customers SET $setClause WHERE customer_id = :customer_id");
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Customer updated successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Customer not found or no changes made.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update customer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $customer_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$customer_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Customer ID is required for deletion.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_customers WHERE customer_id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'Customer deleted successfully.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Customer not found.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete customer.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed.']);
        break;
}

?>

How to use these files:

1.  Save each block of code into its respective .php file (e.g., db.php, create_user.php, products.php, etc.) in your web server's document root or a subdirectory.
2.  Ensure your MySQL database 'hrm_db' exists and the 'root' user with password 'mysql' has appropriate permissions.
3.  Create the necessary tables in your 'hrm_db' database. Here are example SQL schemas based on the assumptions made in the code:

    CREATE TABLE tbl_crm_user (
        user_id VARCHAR(255) PRIMARY KEY,
        user_pass VARCHAR(255) NOT NULL,
        user_department VARCHAR(100),
        user_type VARCHAR(50),
        user_status VARCHAR(50)
    );

    CREATE TABLE tbl_manufacturers (
        manufacturer_id INT AUTO_INCREMENT PRIMARY KEY,
        manufacturer_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(255)
    );

    CREATE TABLE tbl_active_ingredients (
        ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
        ingredient_name VARCHAR(255) NOT NULL,
        description TEXT
    );

    CREATE TABLE tbl_products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(255) NOT NULL,
        manufacturer_id INT,
        price DECIMAL(10,2),
        description TEXT,
        active_ingredient_id INT,
        stock_quantity INT,
        FOREIGN KEY (manufacturer_id) REFERENCES tbl_manufacturers(manufacturer_id),
        FOREIGN KEY (active_ingredient_id) REFERENCES tbl_active_ingredients(ingredient_id)
    );

    CREATE TABLE tbl_inventory (
        inventory_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        batch_number VARCHAR(255) NOT NULL,
        expiry_date DATE,
        quantity INT NOT NULL,
        location VARCHAR(255),
        FOREIGN KEY (product_id) REFERENCES tbl_products(product_id)
    );

    CREATE TABLE tbl_suppliers (
        supplier_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(255),
        address TEXT
    );

    CREATE TABLE tbl_purchase_orders (
        po_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        order_date DATE NOT NULL,
        total_amount DECIMAL(10,2),
        status VARCHAR(50),
        FOREIGN KEY (supplier_id) REFERENCES tbl_suppliers(supplier_id)
    );

    CREATE TABLE tbl_customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(255),
        address TEXT
    );

    CREATE TABLE tbl_sales (
        sale_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        sale_date DATE NOT NULL,
        total_amount DECIMAL(10,2),
        status VARCHAR(50),
        FOREIGN KEY (customer_id) REFERENCES tbl_customers(customer_id)
    );

4.  You can then interact with these API endpoints using tools like Postman, Insomnia, or from your frontend application.

    Example API Calls:

    -   Create User:
        POST to http://your-domain/create_user.php
        Body (JSON): {"user_id": "john.doe", "user_pass": "securepassword123", "user_department": "Sales", "user_type": "Staff", "user_status": "Active"}

    -   Get All Products:
        GET to http://your-domain/products.php

    -   Get Product by ID:
        GET to http://your-domain/products.php?id=1

    -   Create Product:
        POST to http://your-domain/products.php
        Body (JSON): {"product_name": "Paracetamol 500mg", "manufacturer_id": 1, "price": 5.75, "description": "Pain reliever", "active_ingredient_id": 1, "stock_quantity": 1000}

    -   Update Product:
        PUT to http://your-domain/products.php?id=1
        Body (JSON): {"price": 6.00, "stock_quantity": 950}

    -   Delete Product:
        DELETE to http://your-domain/products.php?id=1

This structure provides a robust and secure foundation for your Pharmacy Management System backend.