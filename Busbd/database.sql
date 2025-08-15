-- MySQL Database Schema for BusBD
-- Create database and tables

CREATE DATABASE IF NOT EXISTS busbd;
USE busbd;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    national_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Routes table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_city VARCHAR(100) NOT NULL,
    to_city VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    estimated_duration_minutes INT NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Buses table
CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    bus_type ENUM('AC', 'Non-AC', 'Business', 'Economy') NOT NULL,
    facilities JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    bus_id INT NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    days_of_operation JSON NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (bus_id) REFERENCES buses(id)
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    departure_date DATE NOT NULL,
    return_date DATE,
    is_round_trip BOOLEAN DEFAULT FALSE,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    booking_status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    passenger_details JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

-- Seats table
CREATE TABLE IF NOT EXISTS seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    seat_number INT NOT NULL,
    seat_type ENUM('window', 'aisle', 'middle') DEFAULT 'window',
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Bus tracking table
CREATE TABLE IF NOT EXISTS bus_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    schedule_id INT NOT NULL,
    current_latitude DECIMAL(10,8) NOT NULL,
    current_longitude DECIMAL(11,8) NOT NULL,
    speed_kmh DECIMAL(5,2),
    status ENUM('on_time', 'delayed', 'early', 'stopped', 'maintenance') DEFAULT 'on_time',
    driver_name VARCHAR(100),
    driver_phone VARCHAR(20),
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

-- Price history table
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    schedule_id INT NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    demand_factor DECIMAL(5,2) DEFAULT 1.0,
    time_factor DECIMAL(5,2) DEFAULT 1.0,
    season_factor DECIMAL(5,2) DEFAULT 1.0,
    weather_factor DECIMAL(5,2) DEFAULT 1.0,
    day_of_week_factor DECIMAL(5,2) DEFAULT 1.0,
    fuel_price_factor DECIMAL(5,2) DEFAULT 1.0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

-- User preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    travel_frequency ENUM('rarely', 'occasionally', 'frequently') DEFAULT 'occasionally',
    budget_preference ENUM('budget', 'mid_range', 'premium') DEFAULT 'mid_range',
    time_preference ENUM('fastest', 'cheapest', 'balanced') DEFAULT 'balanced',
    comfort_priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    preferred_transport_types JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Recommendations table
CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    route_id INT NOT NULL,
    transport_type ENUM('bus', 'train', 'ferry') NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL,
    recommendation_reasons JSON,
    is_booked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- Insert sample data

-- Insert sample routes
INSERT INTO routes (from_city, to_city, distance_km, estimated_duration_minutes, base_price) VALUES
('Dhaka', 'Chittagong', 300.5, 360, 800.00),
('Dhaka', 'Sylhet', 240.3, 270, 650.00),
('Dhaka', 'Rajshahi', 260.8, 300, 550.00),
('Chittagong', 'Cox\'s Bazar', 150.2, 180, 400.00),
('Dhaka', 'Barisal', 200.7, 480, 350.00),
('Dhaka', 'Khulna', 280.4, 420, 600.00),
('Sylhet', 'Chittagong', 320.1, 390, 750.00),
('Rajshahi', 'Khulna', 180.6, 240, 300.00);

-- Insert sample buses
INSERT INTO buses (bus_number, company_name, capacity, bus_type, facilities) VALUES
('DHK-1234', 'Green Line Paribahan', 40, 'AC', '["wifi", "ac", "tv", "charging", "restroom", "food"]'),
('DHK-5678', 'Shohagh Paribahan', 45, 'AC', '["wifi", "ac", "charging", "restroom"]'),
('DHK-9012', 'Hanif Paribahan', 50, 'Non-AC', '["charging", "restroom"]'),
('CTG-3456', 'Saint Martin Paribahan', 35, 'AC', '["wifi", "ac", "tv", "charging"]'),
('SYL-7890', 'Shyamoli Paribahan', 42, 'AC', '["wifi", "ac", "charging"]');

-- Insert sample schedules
INSERT INTO schedules (route_id, bus_id, departure_time, arrival_time, days_of_operation, base_price) VALUES
(1, 1, '06:00:00', '12:00:00', '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]', 800.00),
(1, 2, '08:00:00', '14:00:00', '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]', 650.00),
(1, 3, '10:00:00', '16:00:00', '["monday", "tuesday", "wednesday", "thursday", "friday"]', 450.00),
(2, 1, '07:00:00', '11:30:00', '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]', 650.00),
(2, 5, '09:00:00', '13:30:00', '["monday", "tuesday", "wednesday", "thursday", "friday"]', 550.00),
(4, 4, '08:00:00', '11:00:00', '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]', 400.00);

-- Insert sample bus tracking data
INSERT INTO bus_tracking (bus_id, schedule_id, current_latitude, current_longitude, speed_kmh, status, driver_name, driver_phone) VALUES
(1, 1, 23.8103, 90.4125, 60.5, 'on_time', 'Mohammed Rahman', '+8801712345678'),
(2, 2, 23.7500, 90.3800, 55.2, 'delayed', 'Abdul Karim', '+8801712987654'),
(4, 6, 21.4500, 91.9800, 45.8, 'on_time', 'Rahim Uddin', '+8801712345999');

-- Insert sample price history
INSERT INTO price_history (route_id, schedule_id, current_price, demand_factor, time_factor, season_factor, weather_factor, day_of_week_factor, fuel_price_factor) VALUES
(1, 1, 850.00, 1.15, 1.05, 1.0, 1.0, 1.1, 1.02),
(1, 2, 680.00, 1.08, 1.02, 1.0, 1.0, 1.05, 1.01),
(2, 4, 670.00, 1.12, 1.03, 1.0, 0.98, 1.08, 1.02),
(4, 6, 420.00, 1.25, 1.08, 1.15, 1.05, 1.2, 1.03);

-- Create indexes for better performance
CREATE INDEX idx_routes_from_to ON routes(from_city, to_city);
CREATE INDEX idx_schedules_route ON schedules(route_id);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_schedule ON bookings(schedule_id);
CREATE INDEX idx_tracking_bus ON bus_tracking(bus_id);
CREATE INDEX idx_tracking_schedule ON bus_tracking(schedule_id);
CREATE INDEX idx_price_history_route ON price_history(route_id);
CREATE INDEX idx_price_history_schedule ON price_history(schedule_id);
CREATE INDEX idx_recommendations_user ON recommendations(user_id);
CREATE INDEX idx_recommendations_route ON recommendations(route_id);