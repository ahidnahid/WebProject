<?php
// Route Recommendations API
// api/recommendations.php

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

class RecommendationsAPI {
    private $db;
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->db = $database;
    }

    // Get route recommendations
    public function getRecommendations($from, $to, $userId = null, $filters = []) {
        try {
            // Get user preferences if userId is provided
            $userPreferences = null;
            if ($userId) {
                $userPreferences = $this->getUserPreferences($userId);
            }

            // Get available routes
            $routes = $this->getAvailableRoutes($from, $to, $filters);
            
            // Generate recommendations for each route
            $recommendations = [];
            foreach ($routes as $route) {
                $recommendation = $this->generateRouteRecommendation($route, $userPreferences);
                if ($recommendation['confidence'] >= 50) { // Only include recommendations with 50%+ confidence
                    $recommendations[] = $recommendation;
                }
            }

            // Sort by confidence score
            usort($recommendations, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });

            // Limit to top recommendations
            $recommendations = array_slice($recommendations, 0, 10);

            jsonResponse([
                'success' => true,
                'data' => $recommendations,
                'count' => count($recommendations),
                'userProfile' => $userPreferences
            ]);

        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Get user preferences
    private function getUserPreferences($userId) {
        try {
            $query = "SELECT * FROM user_preferences WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($preferences) {
                return [
                    'travelFrequency' => $preferences['travel_frequency'],
                    'budgetPreference' => $preferences['budget_preference'],
                    'timePreference' => $preferences['time_preference'],
                    'comfortPriority' => $preferences['comfort_priority'],
                    'preferredTransportTypes' => json_decode($preferences['preferred_transport_types'], true) ?: []
                ];
            }

            return null;

        } catch (PDOException $e) {
            return null;
        }
    }

    // Get available routes
    private function getAvailableRoutes($from, $to, $filters = []) {
        try {
            $query = "SELECT r.*, s.id as schedule_id, s.departure_time, s.arrival_time, s.base_price as schedule_price,
                             b.id as bus_id, b.bus_number, b.company_name, b.bus_type, b.facilities, b.capacity
                      FROM routes r
                      JOIN schedules s ON r.id = s.route_id
                      JOIN buses b ON s.bus_id = b.id
                      WHERE r.is_active = TRUE AND s.is_active = TRUE AND b.is_active = TRUE
                      AND r.from_city LIKE :from_city AND r.to_city LIKE :to_city";
            
            $params = [
                ':from_city' => '%' . $from . '%',
                ':to_city' => '%' . $to . '%'
            ];

            // Apply filters
            if (!empty($filters['transportType']) && $filters['transportType'] !== 'all') {
                // This is a simplified filter - in reality, you'd need to map transport types to bus types
                $query .= " AND b.bus_type = :bus_type";
                $params[':bus_type'] = $filters['transportType'] === 'train' ? 'AC' : 'AC';
            }

            if (!empty($filters['maxPrice']) && $filters['maxPrice'] !== 'any') {
                $query .= " AND s.base_price <= :max_price";
                $params[':max_price'] = $filters['maxPrice'];
            }

            $query .= " ORDER BY s.base_price ASC";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    // Generate route recommendation
    private function generateRouteRecommendation($route, $userPreferences) {
        $confidence = 50; // Base confidence
        $reasons = [];

        // Calculate confidence based on user preferences
        if ($userPreferences) {
            // Budget preference
            if ($userPreferences['budgetPreference'] === 'budget' && $route['schedule_price'] <= 500) {
                $confidence += 15;
                $reasons[] = 'Matches your budget preference';
            } elseif ($userPreferences['budgetPreference'] === 'premium' && $route['bus_type'] === 'AC') {
                $confidence += 10;
                $reasons[] = 'Premium service available';
            }

            // Time preference
            $duration = $route['estimated_duration_minutes'];
            if ($userPreferences['timePreference'] === 'fastest' && $duration <= 240) {
                $confidence += 12;
                $reasons[] = 'Fast route option';
            } elseif ($userPreferences['timePreference'] === 'cheapest' && $route['schedule_price'] <= 400) {
                $confidence += 10;
                $reasons[] = 'Most economical option';
            }

            // Comfort priority
            if ($userPreferences['comfortPriority'] === 'high' && $route['bus_type'] === 'AC') {
                $confidence += 15;
                $reasons[] = 'High comfort level';
            }

            // Transport type preference
            if (!empty($userPreferences['preferredTransportTypes'])) {
                if (in_array('bus', $userPreferences['preferredTransportTypes'])) {
                    $confidence += 8;
                    $reasons[] = 'Matches your transport preference';
                }
            }
        }

        // Add general factors
        if ($route['bus_type'] === 'AC') {
            $confidence += 5;
            $reasons[] = 'Air conditioning available';
        }

        $facilities = json_decode($route['facilities'], true) ?: [];
        if (in_array('wifi', $facilities)) {
            $confidence += 3;
            $reasons[] = 'WiFi available';
        }

        if (in_array('restroom', $facilities)) {
            $confidence += 3;
            $reasons[] = 'Restroom available';
        }

        // Price factor
        if ($route['schedule_price'] <= 600) {
            $confidence += 8;
            $reasons[] = 'Good value for money';
        }

        // Ensure confidence doesn't exceed 100
        $confidence = min(100, $confidence);

        // Ensure we have at least some reasons
        if (empty($reasons)) {
            $reasons[] = 'Popular route choice';
            $reasons[] = 'Reliable service';
        }

        return [
            'id' => $route['id'],
            'from' => $route['from_city'],
            'to' => $route['to_city'],
            'transportType' => 'bus',
            'price' => (int)$route['schedule_price'],
            'duration' => $this->formatDuration($route['estimated_duration_minutes']),
            'rating' => $this->getBusRating($route['bus_id']),
            'comfort' => $route['bus_type'],
            'company' => $route['company_name'],
            'busNumber' => $route['bus_number'],
            'departureTime' => $route['departure_time'],
            'arrivalTime' => $route['arrival_time'],
            'facilities' => $facilities,
            'confidence' => round($confidence, 1),
            'reasons' => array_slice($reasons, 0, 4) // Limit to top 4 reasons
        ];
    }

    // Format duration
    private function formatDuration($minutes) {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m";
        } elseif ($hours > 0) {
            return "{$hours} hours";
        } else {
            return "{$remainingMinutes} minutes";
        }
    }

    // Get bus rating (simplified - would normally come from reviews table)
    private function getBusRating($busId) {
        // Simulate ratings based on bus type and company
        $ratings = [
            'Green Line Paribahan' => 4.5,
            'Shohagh Paribahan' => 4.3,
            'Hanif Paribahan' => 4.1,
            'Saint Martin Paribahan' => 4.4,
            'Shyamoli Paribahan' => 4.2
        ];

        // Get company name
        try {
            $query = "SELECT company_name, bus_type FROM buses WHERE id = :bus_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':bus_id', $busId);
            $stmt->execute();
            $bus = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bus) {
                $baseRating = $ratings[$bus['company_name']] ?? 4.0;
                if ($bus['bus_type'] === 'AC') {
                    $baseRating += 0.2;
                }
                return min(5.0, round($baseRating, 1));
            }
        } catch (PDOException $e) {
            // Ignore database errors
        }

        return 4.0; // Default rating
    }

    // Save user preferences
    public function saveUserPreferences($userId, $preferences) {
        try {
            // Check if preferences already exist
            $query = "SELECT id FROM user_preferences WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing preferences
                $query = "UPDATE user_preferences 
                          SET travel_frequency = :travel_frequency, 
                              budget_preference = :budget_preference, 
                              time_preference = :time_preference, 
                              comfort_priority = :comfort_priority, 
                              preferred_transport_types = :preferred_transport_types 
                          WHERE user_id = :user_id";
            } else {
                // Insert new preferences
                $query = "INSERT INTO user_preferences 
                          (user_id, travel_frequency, budget_preference, time_preference, comfort_priority, preferred_transport_types) 
                          VALUES (:user_id, :travel_frequency, :budget_preference, :time_preference, :comfort_priority, :preferred_transport_types)";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':travel_frequency', $preferences['travelFrequency']);
            $stmt->bindParam(':budget_preference', $preferences['budgetPreference']);
            $stmt->bindParam(':time_preference', $preferences['timePreference']);
            $stmt->bindParam(':comfort_priority', $preferences['comfortPriority']);
            
            $transportTypes = json_encode($preferences['preferredTransportTypes'] ?: []);
            $stmt->bindParam(':preferred_transport_types', $transportTypes);

            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User preferences saved successfully'
                ]);
            } else {
                handleError("Failed to save user preferences");
            }

        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Get popular routes
    public function getPopularRoutes($limit = 8) {
        try {
            $query = "SELECT r.*, COUNT(b.id) as booking_count,
                             s.base_price as min_price,
                             AVG(b.total_amount) as avg_price
                      FROM routes r
                      LEFT JOIN schedules s ON r.id = s.route_id
                      LEFT JOIN bookings b ON s.id = b.schedule_id
                      WHERE r.is_active = TRUE
                      GROUP BY r.id
                      ORDER BY booking_count DESC, r.from_city, r.to_city
                      LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formattedRoutes = [];
            foreach ($routes as $route) {
                $formattedRoutes[] = [
                    'id' => $route['id'],
                    'from' => $route['from_city'],
                    'to' => $route['to_city'],
                    'distance' => $route['distance_km'],
                    'duration' => $this->formatDuration($route['estimated_duration_minutes']),
                    'minPrice' => (int)$route['min_price'],
                    'avgPrice' => (int)$route['avg_price'],
                    'popularity' => (int)$route['booking_count']
                ];
            }

            jsonResponse([
                'success' => true,
                'data' => $formattedRoutes,
                'count' => count($formattedRoutes)
            ]);

        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Search routes
    public function searchRoutes($query) {
        try {
            $searchQuery = "SELECT DISTINCT r.*, s.base_price as min_price
                           FROM routes r
                           LEFT JOIN schedules s ON r.id = s.route_id
                           WHERE r.is_active = TRUE
                           AND (r.from_city LIKE :search OR r.to_city LIKE :search)
                           ORDER BY r.from_city, r.to_city
                           LIMIT 20";

            $stmt = $this->conn->prepare($searchQuery);
            $searchParam = '%' . $query . '%';
            $stmt->bindParam(':search', $searchParam);
            $stmt->execute();
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formattedRoutes = [];
            foreach ($routes as $route) {
                $formattedRoutes[] = [
                    'id' => $route['id'],
                    'from' => $route['from_city'],
                    'to' => $route['to_city'],
                    'route' => $route['from_city'] . ' → ' . $route['to_city'],
                    'distance' => $route['distance_km'],
                    'duration' => $this->formatDuration($route['estimated_duration_minutes']),
                    'minPrice' => (int)$route['min_price']
                ];
            }

            jsonResponse([
                'success' => true,
                'data' => $formattedRoutes,
                'count' => count($formattedRoutes)
            ]);

        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }
}

// Handle API requests
$recommendationsAPI = new RecommendationsAPI();
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        switch ($request) {
            case 'get_recommendations':
                $from = isset($_GET['from']) ? sanitizeInput($_GET['from']) : '';
                $to = isset($_GET['to']) ? sanitizeInput($_GET['to']) : '';
                $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
                
                if (empty($from) || empty($to)) {
                    handleError("From and to locations are required", 400);
                }
                
                $filters = [
                    'transportType' => isset($_GET['transportType']) ? sanitizeInput($_GET['transportType']) : 'all',
                    'maxPrice' => isset($_GET['maxPrice']) ? sanitizeInput($_GET['maxPrice']) : 'any',
                    'minRating' => isset($_GET['minRating']) ? sanitizeInput($_GET['minRating']) : '0'
                ];
                
                $recommendationsAPI->getRecommendations($from, $to, $userId, $filters);
                break;
                
            case 'get_popular_routes':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
                $recommendationsAPI->getPopularRoutes($limit);
                break;
                
            case 'search_routes':
                $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
                if (empty($query)) {
                    handleError("Search query is required", 400);
                }
                $recommendationsAPI->searchRoutes($query);
                break;
                
            default:
                handleError("Invalid action", 400);
        }
        break;
        
    case 'POST':
        switch ($request) {
            case 'save_preferences':
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                if (!$data || !isset($data['userId']) || !isset($data['preferences'])) {
                    handleError("Invalid data format", 400);
                }
                
                $userId = (int)$data['userId'];
                $preferences = $data['preferences'];
                
                $recommendationsAPI->saveUserPreferences($userId, $preferences);
                break;
                
            default:
                handleError("Invalid action", 400);
        }
        break;
        
    default:
        handleError("Method not allowed", 405);
}
?>