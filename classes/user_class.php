<?php
require_once __DIR__ . '/../settings/db_class.php';

class User extends db_connection
{
    private $user_id;
    private $name;
    private $email;
    private $password;
    private $role;
    private $date_created;
    private $phone_number;

    public function __construct($user_id = null)
    {
        try {
            // Initialize DB connection
            $this->db_connect();

            // Check if connection was successful
            if (!$this->db) {
                error_log("Database connection failed in User constructor");
                throw new Exception("Database connection failed");
            }

            if ($user_id) {
                $this->user_id = $user_id;
                $this->loadUser();
            }
        } catch (Exception $e) {
            error_log("User constructor error: " . $e->getMessage());
            throw $e;
        }
    }

    private function loadUser($user_id = null)
    {
        if ($user_id) {
            $this->user_id = $user_id;
        }
        if (!$this->user_id) {
            return false;
        }

        try {
            $sql = "SELECT * FROM customer WHERE customer_id = " . intval($this->user_id);
            $result = $this->db_fetch_one($sql);

            if ($result) {
                $this->name         = $result['customer_name'];
                $this->email        = $result['customer_email'];
                $this->role         = $result['user_role'];
                $this->date_created = $result['date_created'] ?? null;
                $this->phone_number = $result['customer_contact'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("LoadUser error: " . $e->getMessage());
            return false;
        }
    }

    // REGISTER new user with all required fields
    public function createUser($name, $email, $password, $phone_number, $country, $city, $role = 1)
    {
        try {
            error_log("CreateUser called with: name=$name, email=$email, phone=$phone_number, country=$country, city=$city, role=$role");

            // Check if database connection exists
            if (!$this->db) {
                error_log("No database connection in createUser");
                return ['status' => 'error', 'message' => 'Database connection failed'];
            }

            // Check if email already exists
            $check_sql = "SELECT customer_id FROM customer WHERE customer_email = '" . mysqli_real_escape_string($this->db, $email) . "'";
            error_log("Email check SQL: " . $check_sql);

            $exists = $this->db_fetch_one($check_sql);
            if ($exists) {
                error_log("Email already exists: " . $email);
                return ['status' => 'error', 'message' => 'Email already exists'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            error_log("Password hashed successfully");

            // Insert user with all required fields
            $sql = "INSERT INTO customer (
                        customer_name, 
                        customer_email, 
                        customer_pass, 
                        customer_contact, 
                        user_role, 
                        customer_country,
                        customer_city
                    ) VALUES (
                        '" . mysqli_real_escape_string($this->db, $name) . "',
                        '" . mysqli_real_escape_string($this->db, $email) . "',
                        '" . mysqli_real_escape_string($this->db, $hashed_password) . "',
                        '" . mysqli_real_escape_string($this->db, $phone_number) . "',
                        '" . intval($role) . "',
                        '" . mysqli_real_escape_string($this->db, $country) . "',
                        '" . mysqli_real_escape_string($this->db, $city) . "'
                    )";

            error_log("Insert SQL: " . $sql);

            $result = $this->db_write_query($sql);
            if ($result) {
                $user_id = mysqli_insert_id($this->db);
                error_log("User created successfully with ID: " . $user_id);
                return ['status' => 'success', 'message' => 'Registration successful', 'user_id' => $user_id];
            } else {
                $error = mysqli_error($this->db);
                error_log("Insert failed with error: " . $error);

                // Check if there are more required fields
                if (strpos($error, "doesn't have a default value") !== false) {
                    // Try to get table structure to see what's required
                    $describe = $this->db->query("DESCRIBE customer");
                    $required_fields = [];
                    if ($describe) {
                        while ($row = $describe->fetch_assoc()) {
                            if ($row['Null'] == 'NO' && $row['Default'] === null && $row['Extra'] != 'auto_increment') {
                                $required_fields[] = $row['Field'];
                            }
                        }
                    }
                    return ['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $required_fields)];
                }

                return ['status' => 'error', 'message' => 'Database insert failed: ' . $error];
            }
        } catch (Exception $e) {
            error_log("CreateUser exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    // Alternative method: Insert with minimal required fields only
    public function createUserMinimal($name, $email, $password, $phone_number, $role = 1)
    {
        try {
            // Check if email already exists
            $check_sql = "SELECT customer_id FROM customer WHERE customer_email = '" . mysqli_real_escape_string($this->db, $email) . "'";
            $exists = $this->db_fetch_one($check_sql);
            if ($exists) {
                return ['status' => 'error', 'message' => 'Email already exists'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Try with common default values for typical required fields
            $sql = "INSERT INTO customer SET 
                        customer_name = '" . mysqli_real_escape_string($this->db, $name) . "',
                        customer_email = '" . mysqli_real_escape_string($this->db, $email) . "',
                        customer_pass = '" . mysqli_real_escape_string($this->db, $hashed_password) . "',
                        customer_contact = '" . mysqli_real_escape_string($this->db, $phone_number) . "',
                        user_role = " . intval($role) . ",
                        customer_country = 'Ghana',
                        customer_city = 'Accra',
                        customer_address = '',
                        date_created = NOW()";

            if ($this->db_write_query($sql)) {
                $user_id = mysqli_insert_id($this->db);
                return ['status' => 'success', 'message' => 'Registration successful', 'user_id' => $user_id];
            } else {
                $error = mysqli_error($this->db);
                return ['status' => 'error', 'message' => 'Insert failed: ' . $error];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    // GET user by email - for login functionality
    public function getUserByEmail($email)
    {
        try {
            $sql = "SELECT * FROM customer WHERE customer_email = '" . mysqli_real_escape_string($this->db, $email) . "'";
            return $this->db_fetch_one($sql);
        } catch (Exception $e) {
            error_log("GetUserByEmail error: " . $e->getMessage());
            return false;
        }
    }

    // LOGIN user - enhanced method that matches assignment requirements
    public function login($email, $password)
    {
        try {
            // Get customer by email
            $sql = "SELECT * FROM customer WHERE customer_email = '" . mysqli_real_escape_string($this->db, $email) . "'";
            $user = $this->db_fetch_one($sql);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Invalid email or password'];
            }

            // Check if password matches stored password
            if (password_verify($password, $user['customer_pass'])) {
                // Start session if not already started
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                // Set session variables as required by assignment
                $_SESSION['user_id'] = $user['customer_id'];
                $_SESSION['role'] = $user['user_role'];
                $_SESSION['name'] = $user['customer_name'];
                $_SESSION['email'] = $user['customer_email'];

                return [
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user_data' => $user
                ];
            } else {
                return ['status' => 'error', 'message' => 'Invalid email or password'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    // Method to get customer by email and check password (as per assignment requirements)
    public function getCustomerByEmailAndPassword($email, $password)
    {
        try {
            // Get customer by email
            $sql = "SELECT * FROM customer WHERE customer_email = '" . mysqli_real_escape_string($this->db, $email) . "'";
            $customer = $this->db_fetch_one($sql);

            if (!$customer) {
                return ['status' => 'error', 'message' => 'Invalid email or password'];
            }

            // Check if password input matches the password stored
            if (password_verify($password, $customer['customer_pass'])) {
                return [
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user_data' => $customer
                ];
            } else {
                return ['status' => 'error', 'message' => 'Invalid email or password'];
            }
        } catch (Exception $e) {
            error_log("getCustomerByEmailAndPassword error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
}
