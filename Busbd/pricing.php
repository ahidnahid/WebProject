<?php
// Dynamic Pricing API
// api/pricing.php

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

class PricingAPI {
    private $db;
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->db = $database;
    }

    // Get current pricing for a route
    public function getCurrentPricing($routeId, $scheduleId = null) {
        try {
            // Get route and schedule information
            $query = "SELECT r.*, s.id as schedule_id, s.base_price as schedule_base_price, 
                             s.departure_time, b.company_name, b.bus_type
                      FROM routes r
                      LEFT JOIN schedules s ON r.id = s.route_id
                      LEFT JOIN buses b ON s.bus_id = b.id
                      WHERE r.id = :route_id AND r.is_active = TRUE";
            
            $params = [':route_id' => $routeId];
            
            if ($scheduleId) {
                $query .= " AND s.id = :schedule_id";
                $params[':schedule_id'] = $scheduleId;
            }
            
            $query .= " AND s.is_active = TRUE AND b.is_active = TRUE";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $routeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($routeData)) {
                handleError("Route not found", 404);
            }
            
            $pricingData = [];
            foreach ($routeData as $route) {
                // Calculate dynamic price
                $dynamicPrice = $this->calculateDynamicPrice($route);
                
                // Get price history
                $priceHistory = $this->getPriceHistory($route['id'], $route['schedule_id']);
                
                // Get price predictions
                $predictions = $this->generatePricePredictions($route, $dynamicPrice);
                
                // Get influencing factors
                $factors = $this->getPricingFactors($route);
                
                // Get price statistics
                $stats = $this->getPriceStatistics($route['id'], $route['schedule_id']);
                
                $pricingData[] = [
                    'routeId' => $route['id'],
                    'scheduleId' => $route['schedule_id'],
                    'route' => $route['from_city'] . ' → ' . $route['to_city'],
                    'company' => $route['company_name'],
                    'busType' => $route['bus_type'],
                    'departureTime' => $route['departure_time'],
                    'basePrice' => (float)$route['schedule_base_price'],
                    'currentPrice' => $dynamicPrice['currentPrice'],
                    'priceChange' => $dynamicPrice['priceChange'],
                    'priceChangePercent' => $dynamicPrice['priceChangePercent'],
                    'factors' => $factors,
                    'predictions' => $predictions,
                    'confidence' => $predictions['confidence'],
                    'history' => $priceHistory,
                    'statistics' => $stats,
                    'lastUpdated' => date('Y-m-d H:i:s')
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $pricingData,
                'count' => count($pricingData)
            ]);
            
        } catch (PDOException $e) {
            handleError("Database error: " . $e->getMessage());
        }
    }

    // Calculate dynamic price based on various factors
    private function calculateDynamicPrice($route) {
        $basePrice = (float)$route['schedule_base_price'];
        $currentPrice = $basePrice;
        
        // Get current factors
        $demandFactor = $this->getDemandFactor($route);
        $timeFactor = $this->getTimeFactor($route['departure_time']);
        $seasonFactor = $this->getSeasonFactor();
        $weatherFactor = $this->getWeatherFactor($route['from_city']);
        $dayOfWeekFactor = $this->getDayOfWeekFactor();
        $fuelPriceFactor = $this->getFuelPriceFactor();
        
        // Apply factors
        $currentPrice = $basePrice * $demandFactor * $timeFactor * $seasonFactor * 
                       $weatherFactor * $dayOfWeekFactor * $fuelPriceFactor;
        
        // Get previous price for change calculation
        $previousPrice = $this->getPreviousPrice($route['id'], $route['schedule_id']);
        if (!$previousPrice) {
            $previousPrice = $basePrice;
        }
        
        $priceChange = $currentPrice - $previousPrice;
        $priceChangePercent = ($priceChange / $previousPrice) * 100;
        
        // Save current price to history
        $this->savePriceHistory($route['id'], $route['schedule_id'], $currentPrice, [
            'demand' => $demandFactor,
            'time' => $timeFactor,
            'season' => $seasonFactor,
            'weather' => $weatherFactor,
            'dayOfWeek' => $dayOfWeekFactor,
            'fuelPrice' => $fuelPriceFactor
        ]);
        
        return [
            'currentPrice' => round($currentPrice, 2),
            'priceChange' => round($priceChange, 2),
            'priceChangePercent' => round($priceChangePercent, 2)
        ];
    }

    // Get demand factor (simplified)
    private function getDemandFactor($route) {
        // Simulate demand based on time and route popularity
        $hour = (int)date('H');
        $dayOfWeek = date('N'); // 1-7 (Monday-Sunday)
        
        $demand = 1.0;
        
        // Higher demand during peak hours (6-9 AM, 5-8 PM)
        if (($hour >= 6 && $hour <= 9) || ($hour >= 17 && $hour <= 20)) {
            $demand += 0.3;
        }
        
        // Higher demand on weekends
        if ($dayOfWeek >= 6) {
            $demand += 0.2;
        }
        
        // Higher demand for popular routes
        $popularRoutes = ['Dhaka → Chittagong', 'Dhaka → Sylhet'];
        $routeName = $route['from_city'] . ' → ' . $route['to_city'];
        if (in_array($routeName, $popularRoutes)) {
            $demand += 0.15;
        }
        
        return min(2.0, max(0.5, $demand));
    }

    // Get time factor based on departure time
    private function getTimeFactor($departureTime) {
        $hour = (int)explode(':', $departureTime)[0];
        
        // Early morning and late night discounts
        if ($hour >= 22 || $hour <= 5) {
            return 0.9;
        }
        
        // Peak hour premium
        if (($hour >= 6 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
            return 1.2;
        }
        
        return 1.0;
    }

    // Get seasonal factor
    private function getSeasonFactor() {
        $month = (int)date('n');
        
        // Peak seasons (summer vacation, holidays)
        if (($month >= 6 && $month <= 8) || ($month == 12 || $month == 1)) {
            return 1.3;
        }
        
        // Off-season discounts
        if ($month >= 2 && $month <= 4) {
            return 0.9;
        }
        
        return 1.0;
    }

    // Get weather factor (simplified)
    private function getWeatherFactor($city) {
        // Simulate weather impact
        // In reality, this would integrate with a weather API
        $weatherConditions = [
            'clear' => 1.0,
            'rainy' => 1.1,
            'stormy' => 1.2,
            'foggy' => 1.15
        ];
        
        // Random weather condition for simulation
        $conditions = array_keys($weatherConditions);
        $randomCondition = $conditions[array_rand($conditions)];
        
        return $weatherConditions[$randomCondition];
    }

    // Get day of week factor
    private function getDayOfWeekFactor() {
        $dayOfWeek = date('N');
        
        // Weekend premium
        if ($dayOfWeek >= 6) {
            return 1.15;
        }
        
        // Monday morning premium
        if ($dayOfWeek == 1 && date('H') < 12) {
            return 1.1;
        }
        
        return 1.0;
    }

    // Get fuel price factor
    private function getFuelPriceFactor() {
        // Simulate fuel price impact
        // In reality, this would fetch current fuel prices
        return 1.02; // 2% fuel price increase
    }

    // Get previous price from history
    private function getPreviousPrice($routeId, $scheduleId) {
        try {
            $query = "SELECT current_price FROM price_history 
                      WHERE route_id = :route_id AND schedule_id = :schedule_id 
                      ORDER BY recorded_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $routeId);
            $stmt->bindParam(':schedule_id', $scheduleId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (float)$result['current_price'] : null;
            
        } catch (PDOException $e) {
            return null;
        }
    }

    // Save price to history
    private function savePriceHistory($routeId, $scheduleId, $price, $factors) {
        try {
            $query = "INSERT INTO price_history 
                      (route_id, schedule_id, current_price, demand_factor, time_factor, 
                       season_factor, weather_factor, day_of_week_factor, fuel_price_factor) 
                      VALUES (:route_id, :schedule_id, :current_price, :demand_factor, :time_factor, 
                              :season_factor, :weather_factor, :day_of_week_factor, :fuel_price_factor)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $routeId);
            $stmt->bindParam(':schedule_id', $scheduleId);
            $stmt->bindParam(':current_price', $price);
            $stmt->bindParam(':demand_factor', $factors['demand']);
            $stmt->bindParam(':time_factor', $factors['time']);
            $stmt->bindParam(':season_factor', $factors['season']);
            $stmt->bindParam(':weather_factor', $factors['weather']);
            $stmt->bindParam(':day_of_week_factor', $factors['dayOfWeek']);
            $stmt->bindParam(':fuel_price_factor', $factors['fuelPrice']);
            
            $stmt->execute();
            
        } catch (PDOException $e) {
            // Log error but don't fail the request
            error_log("Failed to save price history: " . $e->getMessage());
        }
    }

    // Get price history for charting
    private function getPriceHistory($routeId, $scheduleId, $hours = 24) {
        try {
            $query = "SELECT current_price, recorded_at 
                      FROM price_history 
                      WHERE route_id = :route_id AND schedule_id = :schedule_id 
                      AND recorded_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                      ORDER BY recorded_at ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $routeId);
            $stmt->bindParam(':schedule_id', $scheduleId);
            $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $history = [];
            foreach ($results as $result) {
                $history[] = [
                    'price' => (float)$result['current_price'],
                    'time' => $result['recorded_at']
                ];
            }
            
            return $history;
            
        } catch (PDOException $e) {
            return [];
        }
    }

    // Generate price predictions
    private function generatePricePredictions($route, $currentPrice) {
        $predictions = [];
        $confidence = 75; // Base confidence
        
        // 6-hour prediction
        $sixHourChange = (mt_rand(-50, 100) / 1000); // -5% to +10%
        $predictions['6h'] = round($currentPrice * (1 + $sixHourChange), 2);
        
        // 12-hour prediction
        $twelveHourChange = (mt_rand(-100, 150) / 1000); // -10% to +15%
        $predictions['12h'] = round($currentPrice * (1 + $twelveHourChange), 2);
        
        // 24-hour prediction
        $twentyFourHourChange = (mt_rand(-80, 120) / 1000); // -8% to +12%
        $predictions['24h'] = round($currentPrice * (1 + $twentyFourHourChange), 2);
        
        // Adjust confidence based on prediction consistency
        if (abs($sixHourChange) < 0.05 && abs($twelveHourChange) < 0.08) {
            $confidence += 10;
        }
        
        return [
            'predictions' => $predictions,
            'confidence' => min(95, max(60, $confidence))
        ];
    }

    // Get pricing factors for display
    private function getPricingFactors($route) {
        return [
            'demand' => [
                'value' => $this->getDemandFactor($route),
                'impact' => $this->getImpactLevel($this->getDemandFactor($route)),
                'description' => 'Current demand for this route'
            ],
            'timeOfDay' => [
                'value' => $this->getTimeFactor($route['departure_time']),
                'impact' => $this->getImpactLevel($this->getTimeFactor($route['departure_time'])),
                'description' => 'Time of day pricing adjustment'
            ],
            'season' => [
                'value' => $this->getSeasonFactor(),
                'impact' => $this->getImpactLevel($this->getSeasonFactor()),
                'description' => 'Seasonal demand adjustment'
            ],
            'weather' => [
                'value' => $this->getWeatherFactor($route['from_city']),
                'impact' => $this->getImpactLevel($this->getWeatherFactor($route['from_city'])),
                'description' => 'Weather condition impact'
            ],
            'dayOfWeek' => [
                'value' => $this->getDayOfWeekFactor(),
                'impact' => $this->getImpactLevel($this->getDayOfWeekFactor()),
                'description' => 'Day of week adjustment'
            ],
            'fuelPrice' => [
                'value' => $this->getFuelPriceFactor(),
                'impact' => $this->getImpactLevel($this->getFuelPriceFactor()),
                'description' => 'Fuel price impact'
            ]
        ];
    }

    // Get impact level based on factor value
    private function getImpactLevel($value) {
        if ($value >= 1.2) {
            return 'high';
        } elseif ($value >= 1.1) {
            return 'medium';
        } elseif ($value <= 0.9) {
            return 'low';
        } else {
            return 'low';
        }
    }

    // Get price statistics
    private function getPriceStatistics($routeId, $scheduleId) {
        try {
            $query = "SELECT 
                        MIN(current_price) as min_price,
                        MAX(current_price) as max_price,
                        AVG(current_price) as avg_price,
                        COUNT(*) as data_points
                      FROM price_history 
                      WHERE route_id = :route_id AND schedule_id = :schedule_id
                      AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $routeId);
            $stmt->bindParam(':schedule_id', $scheduleId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'min24h' => (float)$result['min_price'],
                'max24h' => (float)$result['max_price'],
                'avg24h' => (float)$result['avg_price'],
                'dataPoints' => (int)$result['data_points']
            ];
            
        } catch (PDOException $e) {
            return [
                'min24h' => 0,
                'max24h' => 0,
                'avg24h' => 0,
                'dataPoints' => 0
            ];
        }
    }

    // Get pricing optimization tips
    public function getOptimizationTips($routeId = null) {
        $tips = [
            [
                'title' => 'Book in Advance',
                'description' => 'Book 2-3 days in advance for better prices and seat availability',
                'impact' => 'high',
                'category' => 'timing'
            ],
            [
                'title' => 'Avoid Peak Hours',
                'description' => 'Travel during off-peak hours (10 AM - 4 PM) for lower prices',
                'impact' => 'medium',
                'category' => 'timing'
            ],
            [
                'title' => 'Weekday Travel',
                'description' => 'Weekday travel is typically 10-15% cheaper than weekends',
                'impact' => 'medium',
                'category' => 'timing'
            ],
            [
                'title' => 'Off-Season Discounts',
                'description' => 'Consider traveling during off-season months for significant savings',
                'impact' => 'high',
                'category' => 'seasonal'
            ],
            [
                'title' => 'Flexible Dates',
                'description' => 'Being flexible with travel dates can save you up to 20%',
                'impact' => 'high',
                'category' => 'flexibility'
            ],
            [
                'title' => 'Group Booking',
                'description' => 'Group bookings of 4+ passengers may qualify for discounts',
                'impact' => 'medium',
                'category' => 'group'
            ]
        ];
        
        // Filter tips based on route if provided
        if ($routeId) {
            // In a real implementation, you would customize tips based on route characteristics
            // For now, we'll return all tips
        }
        
        jsonResponse([
            'success' => true,
            'data' => $tips,
            'count' => count($tips)
        ]);
    }

    // Get all available routes with pricing
    public function getAllRoutesPricing() {
        try {
            $query = "SELECT r.id, r.from_city, r.to_city, r.base_price, 
                             MIN(s.base_price) as min_schedule_price,
                             COUNT(s.id) as schedule_count
                      FROM routes r
                      LEFT JOIN schedules s ON r.id = s.route_id AND s.is_active = TRUE
                      WHERE r.is_active = TRUE
                      GROUP BY r.id
                      ORDER BY r.from_city, r.to_city";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formattedRoutes = [];
            foreach ($routes as $route) {
                $formattedRoutes[] = [
                    'id' => $route['id'],
                    'from' => $route['from_city'],
                    'to' => $route['to_city'],
                    'route' => $route['from_city'] . ' → ' . $route['to_city'],
                    'basePrice' => (float)$route['base_price'],
                    'minPrice' => (float)$route['min_schedule_price'],
                    'scheduleCount' => (int)$route['schedule_count']
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
$pricingAPI = new PricingAPI();
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        switch ($request) {
            case 'get_pricing':
                $routeId = isset($_GET['routeId']) ? (int)$_GET['routeId'] : 0;
                $scheduleId = isset($_GET['scheduleId']) ? (int)$_GET['scheduleId'] : null;
                
                if ($routeId <= 0) {
                    handleError("Valid route ID is required", 400);
                }
                
                $pricingAPI->getCurrentPricing($routeId, $scheduleId);
                break;
                
            case 'get_routes':
                $pricingAPI->getAllRoutesPricing();
                break;
                
            case 'get_tips':
                $routeId = isset($_GET['routeId']) ? (int)$_GET['routeId'] : null;
                $pricingAPI->getOptimizationTips($routeId);
                break;
                
            default:
                handleError("Invalid action", 400);
        }
        break;
        
    default:
        handleError("Method not allowed", 405);
}
?>