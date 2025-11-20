# Create repair service tables
# Run these commands in your MySQL database to fix the appointment booking system
CREATE TABLE IF NOT EXISTS repair_issues (
    issue_id INT(11) NOT NULL AUTO_INCREMENT,
    issue_name VARCHAR(100) NOT NULL,
    issue_description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    estimated_time VARCHAR(50),
    difficulty_level ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (issue_id)
);

CREATE TABLE IF NOT EXISTS repair_specialists (
    specialist_id INT(11) NOT NULL AUTO_INCREMENT,
    specialist_name VARCHAR(100) NOT NULL,
    specialist_email VARCHAR(100),
    specialist_phone VARCHAR(20),
    experience_years INT(3) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 5.00,
    total_repairs INT(11) DEFAULT 0,
    specialization TEXT,
    avatar_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (specialist_id)
);

CREATE TABLE IF NOT EXISTS repair_appointments (
    appointment_id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11),
    specialist_id INT(11) NOT NULL,
    issue_id INT(11) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(100),
    device_info TEXT,
    issue_description TEXT,
    status ENUM('scheduled', 'confirmed', 'in-progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    FOREIGN KEY (specialist_id) REFERENCES repair_specialists(specialist_id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES repair_issues(issue_id) ON DELETE CASCADE
);

-- Insert sample repair issues
INSERT IGNORE INTO repair_issues (issue_id, issue_name, issue_description, base_price, estimated_time, difficulty_level) VALUES
(1, 'Screen Replacement', 'Replace cracked or damaged phone screens', 150.00, '2-3 hours', 'Medium'),
(2, 'Battery Issues', 'Battery replacement and charging problems', 80.00, '1-2 hours', 'Easy'),
(3, 'Water Damage', 'Repair devices damaged by water or moisture', 200.00, '1-2 days', 'Hard'),
(4, 'Software Issues', 'Fix software glitches and system problems', 60.00, '1-2 hours', 'Easy'),
(5, 'Camera Problems', 'Repair or replace faulty cameras', 120.00, '2-4 hours', 'Medium'),
(6, 'Audio Issues', 'Fix speaker, microphone, and headphone problems', 90.00, '1-3 hours', 'Medium');

-- Insert sample specialists
INSERT IGNORE INTO repair_specialists (specialist_id, specialist_name, specialist_email, experience_years, rating, total_repairs, specialization) VALUES
(1, 'John Smith', 'john@gadgetgarage.com', 5, 4.8, 1250, 'iPhone and Samsung repairs'),
(2, 'Sarah Johnson', 'sarah@gadgetgarage.com', 3, 4.9, 890, 'Water damage and data recovery'),
(3, 'Mike Chen', 'mike@gadgetgarage.com', 7, 4.7, 2100, 'All device types and software issues'),
(4, 'Emily Davis', 'emily@gadgetgarage.com', 4, 4.9, 1560, 'Tablet and laptop repairs'),
(5, 'David Wilson', 'david@gadgetgarage.com', 6, 4.6, 1800, 'Gaming devices and consoles');