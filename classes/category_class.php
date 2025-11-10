<?php
require_once __DIR__ . '/../settings/db_class.php';

class Category extends db_connection
{
    public function __construct()
    {
        if ($this->db === null) {
            $this->db_connect();
        }
    }

    private function normalize_name(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name);
        return $name;
    }

    // Add new category (GLOBAL)
    public function add_category(string $category_name): array
    {
        $category_name = $this->normalize_name($category_name);
        if ($category_name === '') return ['success' => false, 'code' => 'EMPTY'];

        $esc = mysqli_real_escape_string($this->db, $category_name);

        // Duplicate check (case-insensitive)
        $sql = "SELECT cat_id
                  FROM categories
                 WHERE LOWER(TRIM(cat_name)) = LOWER(TRIM('$esc'))
                 LIMIT 1";
        $exists = $this->db_fetch_one($sql);
        if ($exists) return ['success' => false, 'code' => 'DUPLICATE'];

        $sql = "INSERT INTO categories (cat_name) VALUES ('$esc')";
        $ok  = $this->db_query($sql);

        if ($ok) {
            return ['success' => true, 'cat_id' => mysqli_insert_id($this->db)];
        }
        return ['success' => false, 'code' => 'DB'];
    }

    // Fetch all categories (GLOBAL)
    public function get_categories(): array
    {
        $sql = "SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC";
        $rows = $this->db_fetch_all($sql);
        return is_array($rows) ? $rows : [];
    }

    // Get single category by ID
    public function get_category(int $cat_id): array
    {
        $cat_id = (int)$cat_id;
        $sql = "SELECT cat_id, cat_name FROM categories WHERE cat_id = $cat_id LIMIT 1";
        $row = $this->db_fetch_one($sql);

        if ($row) {
            return ['success' => true, 'data' => $row];
        }
        return ['success' => false, 'data' => null, 'code' => 'NOT_FOUND'];
    }

    // Get user categories (for now, returns all categories since they appear to be global)
    // You can modify this if categories become user-specific later
    public function get_user_categories(int $user_id): array
    {
        // Since categories appear to be global in your system, 
        // this just returns all categories for now
        $rows = $this->get_categories();
        return $rows;
    }

    // Update category name (GLOBAL)
    public function update_category(int $cat_id, string $category_name): array
    {
        $category_name = $this->normalize_name($category_name);
        if ($category_name === '') return ['success' => false, 'code' => 'EMPTY'];

        $cat_id = (int)$cat_id;
        $esc    = mysqli_real_escape_string($this->db, $category_name);

        // Prevent renaming into an existing name
        $sql = "SELECT cat_id
                  FROM categories
                 WHERE LOWER(TRIM(cat_name)) = LOWER(TRIM('$esc'))
                   AND cat_id <> $cat_id
                 LIMIT 1";
        $exists = $this->db_fetch_one($sql);
        if ($exists) return ['success' => false, 'code' => 'DUPLICATE'];

        $sql = "UPDATE categories SET cat_name = '$esc' WHERE cat_id = $cat_id";
        $ok  = $this->db_query($sql);

        if ($ok) return ['success' => true];
        return ['success' => false, 'code' => 'DB'];
    }

    // Delete category (GLOBAL)
    public function delete_category(int $cat_id): array
    {
        $cat_id = (int)$cat_id;
        $sql = "DELETE FROM categories WHERE cat_id = $cat_id";
        $ok  = $this->db_query($sql);

        if ($ok) return ['success' => true];
        return ['success' => false, 'code' => 'DB'];
    }
}
