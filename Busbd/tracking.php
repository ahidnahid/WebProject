<?php
// Bus Tracking API
// api/tracking.php

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

class BusTrackingAPI {
    private $db;
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->db = $database;
    }

    // Get bus tracking information
    public function getBusTracking($busNumber = null) {
        try {
            $query = "SELECT bt.*, b.bus_number, b.company_name, r.from_city, r.to_city, s.departure_time, s.arrival_time 
                      FROM bus_tracking bt 
                      JOIN buses b ON bt.bus_id = b.id 
                      JOIN schedules s ON bt.schedule_id = s.id 
                      JOIN routes r ON s.route_id = r.id 
                      WHERE b.is_active = TRUE AND s.is_active = TRUE";
            
            $params = [];
            
            if ($busNumber) {
                $query .= " AND b.bus_number = :bus_number";
                $params[':bus_number'] = $busNumber;
            }
            
            $query .= " ORDER BY bt.last_update DESC LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $trackingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the response
            $formattedData = [];
            foreach ($trackingData as $data) {
                $formattedData[] = [
                    'busNumber' => $data['bus_number'],
                    'company' => $data['company_name'],
                    'route' => $data['from_city'] . ' → ' . $data['to_city'],
                    'status' => $data['status'],
                    'driver' => $data['driver_name'],
                    'contact' => $data['driver_phone'],
                    'location' => [
                        'latitude' => $data['current_latitude'],
                        'longitude' => $data['current_longitude']
                    ],
                    'speed' => $data['speed_kmh'],
                    'schedule' => [
                        'departure' => $data['departure_time'],
                        'arrival' => $data['arrival_time']
                    ],
                    'lastUpdate' => $data['last_update']
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $formattedData,
                'count' => count($formattedData)
            ]);
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Update bus location
    public function updateBusLocation($data) {
        try {
            // Validate required fields
            $requiredFields = ['busNumber', 'latitude', 'longitude', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    handleError("Missing required field: $field", 400);
                }
            }
            
            // Get bus ID
            $busQuery = "SELECT id FROM buses WHERE bus_number = :bus_number AND is_active = TRUE";
            $stmt = $this->conn->prepare($busQuery);
            $stmt->bindParam(':bus_number', $data['busNumber']);
            $stmt->execute();
            $bus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bus) {
                handleError("Bus not found", 404);
            }
            
            // Get active schedule for this bus
            $scheduleQuery = "SELECT id FROM schedules WHERE bus_id = :bus_id AND is_active = TRUE LIMIT 1";
            $stmt = $this->conn->prepare($scheduleQuery);
            $stmt->bindParam(':bus_id', $bus['id']);
            $stmt->execute();
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
                handleError("No active schedule found for this bus", 404);
            }
            
            // Update or insert tracking data
            $updateQuery = "INSERT INTO bus_tracking 
                           (bus_id, schedule_id, current_latitude, current_longitude, speed_kmh, status, driver_name, driver_phone) 
                           VALUES (:bus_id, :schedule_id, :latitude, :longitude, :speed, :status, :driver_name, :driver_phone)
                           ON DUPLICATE KEY UPDATE 
                           current_latitude = VALUES(current_latitude),
                           current_longitude = VALUES(current_longitude),
                           speed_kmh = VALUES(speed_kmh),
                           status = VALUES(status),
                           driver_name = VALUES(driver_name),
                           driver_phone = VALUES(driver_phone),
                           last_update = CURRENT_TIMESTAMP";
            
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bindParam(':bus_id', $bus['id']);
            $stmt->bindParam(':schedule_id', $schedule['id']);
            $stmt->bindParam(':latitude', $data['latitude']);
            $stmt->bindParam(':longitude', $data['longitude']);
            $stmt->bindParam(':speed', $data['speed']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':driver_name', $data['driverName']);
            $stmt->bindParam(':driver_phone', $data['driverPhone']);
            
            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Bus location updated successfully',
                    'busNumber' => $data['busNumber']
                ]);
            } else {
                handleError("Failed to update bus location");
            }
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Get bus route progress
    public function getBusProgress($busNumber) {
        try {
            $query = "SELECT bt.*, b.bus_number, r.from_city, r.to_city, r.distance_km, s.departure_time, s.arrival_time 
                      FROM bus_tracking bt 
                      JOIN buses b ON bt.bus_id = b.id 
                      JOIN schedules s ON bt.schedule_id = s.id 
                      JOIN routes r ON s.route_id = r.id 
                      WHERE b.bus_number = :bus_number AND b.is_active = TRUE AND s.is_active = TRUE";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_number', $busNumber);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                handleError("Bus not found", 404);
            }
            
            // Calculate progress (simplified calculation)
            $totalDistance = $data['distance_km'];
            $currentSpeed = $data['speed_kmh'] ?: 50; // Default speed if not available
            
            // Get journey start time (simplified - using schedule departure)
            $departureTime = new DateTime($data['departure_time']);
            $currentTime = new DateTime();
            $elapsedHours = ($currentTime->getTimestamp() - $departureTime->getTimestamp()) / 3600;
            
            $distanceCovered = min($totalDistance, $currentSpeed * $elapsedHours);
            $progress = ($distanceCovered / $totalDistance) * 100;
            $distanceRemaining = $totalDistance - $distanceCovered;
            
            // Calculate ETA
            if ($currentSpeed > 0) {
                $etaHours = $distanceRemaining / $currentSpeed;
                $eta = new DateTime();
                $eta->add(new DateInterval('PT' . round($etaHours) . 'H'));
                $etaString = $eta->format('g:i A');
            } else {
                $etaString = 'Unknown';
            }
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'busNumber' => $data['bus_number'],
                    'route' => $data['from_city'] . ' → ' . $data['to_city'],
                    'progress' => round($progress, 1),
                    'distanceCovered' => round($distanceCovered, 1),
                    'distanceRemaining' => round($distanceRemaining, 1),
                    'eta' => $etaString,
                    'status' => $data['status'],
                    'currentLocation' => [
                        'latitude' => $data['current_latitude'],
                        'longitude' => $data['current_longitude']
                    ]
                ]
            ]);
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Get bus facilities
    public function getBusFacilities($busNumber) {
        try {
            $query = "SELECT facilities FROM buses WHERE bus_number = :bus_number AND is_active = TRUE";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_number', $busNumber);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                handleError("Bus not found", 404);
            }
            
            $facilities = json_decode($data['facilities'], true) ?: [];
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'busNumber' => $busNumber,
                    'facilities' => $facilities
                ]
            ]);
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Get tracking updates for a bus
    public function getTrackingUpdates($busNumber, $limit = 10) {
        try {
            // This would typically be implemented with a separate updates table
            // For now, we'll simulate with recent tracking data
            $query = "SELECT bt.*, b.bus_number 
                      FROM bus_tracking bt 
                      JOIN buses b ON bt.bus_id = b.id 
                      WHERE b.bus_number = :bus_number AND b.is_active = TRUE 
                      ORDER BY bt.last_update DESC LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_number', $busNumber);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formattedUpdates = [];
            foreach ($updates as $update) {
                $time = new DateTime($update['last_update']);
                $formattedUpdates[] = [
                    'time' => $time->format('h:i A'),
                    'text' => $this->generateUpdateText($update['status'], $update['speed_kmh']),
                    'location' => [
                        'latitude' => $update['current_latitude'],
                        'longitude' => $update['current_longitude']
                    ]
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $formattedUpdates,
                'count' => count($formattedUpdates)
            ]);
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    private function generateUpdateText($status, $speed) {
        $updates = [
            'on_time' => 'Bus is running on schedule',
            'delayed' => 'Bus is experiencing delays',
            'early' => 'Bus is ahead of schedule',
            'stopped' => 'Bus has made a scheduled stop',
            'maintenance' => 'Bus is under maintenance'
        ];
        
        $text = $updates[$status] ?? 'Bus status updated';
        
        if ($speed && $speed > 0) {
            $text .= " - Speed: {$speed} km/h";
        }
        
        return $text;
    }
}

// Handle API requests
$trackingAPI = new BusTrackingAPI();
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        switch ($request) {
            case 'get_tracking':
                $busNumber = isset($_GET['busNumber']) ? sanitizeInput($_GET['busNumber']) : null;
                $trackingAPI->getBusTracking($busNumber);
                break;
                
            case 'get_progress':
                $busNumber = isset($_GET['busNumber']) ? sanitizeInput($_GET['busNumber']) : '';
                if (empty($busNumber)) {
                    handleError("Bus number is required", 400);
                }
                $trackingAPI->getBusProgress($busNumber);
                break;
                
            case 'get_facilities':
                $busNumber = isset($_GET['busNumber']) ? sanitizeInput($_GET['busNumber']) : '';
                if (empty($busNumber)) {
                    handleError("Bus number is required", 400);
                }
                $trackingAPI->getBusFacilities($busNumber);
                break;
                
            case 'get_updates':
                $busNumber = isset($_GET['busNumber']) ? sanitizeInput($_GET['busNumber']) : '';
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                if (empty($busNumber)) {
                    handleError("Bus number is required", 400);
                }
                $trackingAPI->getTrackingUpdates($busNumber, $limit);
                break;
                
            default:
                handleError("Invalid action", 400);
        }
        break;
        
    case 'POST':
        switch ($request) {
            case 'update_location':
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                if (!$data) {
                    handleError("Invalid JSON data", 400);
                }
                
                $trackingAPI->updateBusLocation($data);
                break;
                
            default:
                handleError("Invalid action", 400);
        }
        break;
        
    default:
        handleError("Method not allowed", 405);
}
?>