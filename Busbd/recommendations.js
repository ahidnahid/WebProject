// JavaScript for Route Recommendations page

document.addEventListener('DOMContentLoaded', function() {
    // Initialize recommendations functionality
    initializeRecommendations();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load user profile
    loadUserProfile();
    
    // Initialize recommendations
    loadRecommendations();
});

let recommendationsData = [
    {
        id: 1,
        from: 'Dhaka',
        to: 'Chittagong',
        transportType: 'bus',
        price: 800,
        duration: '6 hours',
        rating: 4.5,
        comfort: 'AC Business Class',
        confidence: 95,
        reasons: [
            'Matches your comfort preference',
            'Best value for money on this route',
            'High on-time performance'
        ]
    },
    {
        id: 2,
        from: 'Dhaka',
        to: 'Sylhet',
        transportType: 'train',
        price: 550,
        duration: '4.5 hours',
        rating: 4.3,
        comfort: 'AC Chair Car',
        confidence: 88,
        reasons: [
            'Faster than bus alternatives',
            'Within your budget range',
            'Scenic route experience'
        ]
    },
    {
        id: 3,
        from: 'Chittagong',
        to: 'Cox\'s Bazar',
        transportType: 'bus',
        price: 400,
        duration: '3 hours',
        rating: 4.4,
        comfort: 'Non-AC',
        confidence: 82,
        reasons: [
            'Most economical option',
            'Frequent departures',
            'Good customer reviews'
        ]
    },
    {
        id: 4,
        from: 'Dhaka',
        to: 'Barisal',
        transportType: 'ferry',
        price: 350,
        duration: '8 hours',
        rating: 4.1,
        comfort: 'Economy Class',
        confidence: 78,
        reasons: [
            'Unique travel experience',
            'Very budget-friendly',
            'Scenic river journey'
        ]
    }
];

let userProfile = {
    travelFrequency: 'Frequent Traveler',
    budgetPreference: 'Mid-range',
    timePreference: 'Balanced',
    comfortPriority: 'High'
};

function initializeRecommendations() {
    // Set default values
    const fromLocation = document.getElementById('fromLocation');
    const toLocation = document.getElementById('toLocation');
    
    if (fromLocation && toLocation) {
        fromLocation.value = 'Dhaka';
        toLocation.value = 'Chittagong';
    }
}

function setupEventListeners() {
    const searchBtn = document.getElementById('searchBtn');
    const swapIcon = document.querySelector('.swap-icon');
    const transportType = document.getElementById('transportType');
    const maxPrice = document.getElementById('maxPrice');
    const minRating = document.getElementById('minRating');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', performSearch);
    }
    
    if (swapIcon) {
        swapIcon.addEventListener('click', swapLocations);
    }
    
    // Filter change listeners
    if (transportType) {
        transportType.addEventListener('change', applyFilters);
    }
    
    if (maxPrice) {
        maxPrice.addEventListener('change', applyFilters);
    }
    
    if (minRating) {
        minRating.addEventListener('change', applyFilters);
    }
    
    // Enter key search
    const fromLocation = document.getElementById('fromLocation');
    const toLocation = document.getElementById('toLocation');
    
    if (fromLocation) {
        fromLocation.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    if (toLocation) {
        toLocation.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
}

function swapLocations() {
    const fromLocation = document.getElementById('fromLocation');
    const toLocation = document.getElementById('toLocation');
    
    if (fromLocation && toLocation) {
        const temp = fromLocation.value;
        fromLocation.value = toLocation.value;
        toLocation.value = temp;
        
        // Perform search with swapped locations
        performSearch();
    }
}

function performSearch() {
    const fromLocation = document.getElementById('fromLocation').value.trim();
    const toLocation = document.getElementById('toLocation').value.trim();
    
    if (!fromLocation || !toLocation) {
        showNotification('Please enter both departure and destination cities', 'error');
        return;
    }
    
    showNotification('Searching for routes...', 'info');
    
    // Simulate API call
    setTimeout(() => {
        generateRecommendations(fromLocation, toLocation);
        showNotification('Routes found successfully!', 'success');
    }, 1000);
}

function generateRecommendations(from, to) {
    // Generate new recommendations based on search
    const routes = [
        { from: 'Dhaka', to: 'Chittagong', distance: 300 },
        { from: 'Dhaka', to: 'Sylhet', distance: 240 },
        { from: 'Dhaka', to: 'Rajshahi', distance: 260 },
        { from: 'Chittagong', to: 'Cox\'s Bazar', distance: 150 },
        { from: 'Dhaka', to: 'Barisal', distance: 200 }
    ];
    
    const matchingRoutes = routes.filter(route => 
        route.from.toLowerCase().includes(from.toLowerCase()) &&
        route.to.toLowerCase().includes(to.toLowerCase())
    );
    
    if (matchingRoutes.length === 0) {
        // Generate generic recommendations
        recommendationsData = [
            {
                id: Date.now(),
                from: from,
                to: to,
                transportType: 'bus',
                price: Math.floor(Math.random() * 500) + 300,
                duration: `${Math.floor(Math.random() * 4) + 3} hours`,
                rating: (Math.random() * 1 + 4).toFixed(1),
                comfort: 'AC Business Class',
                confidence: Math.floor(Math.random() * 20) + 80,
                reasons: [
                    'Direct route available',
                    'Good customer reviews',
                    'Reasonable pricing'
                ]
            }
        ];
    } else {
        // Use matching routes
        recommendationsData = matchingRoutes.map((route, index) => ({
            id: Date.now() + index,
            from: route.from,
            to: route.to,
            transportType: ['bus', 'train', 'ferry'][Math.floor(Math.random() * 3)],
            price: Math.floor(route.distance * (2 + Math.random())) + 200,
            duration: `${Math.floor(route.distance / 60 + Math.random() * 2)} hours`,
            rating: (Math.random() * 1 + 4).toFixed(1),
            comfort: ['AC Business Class', 'AC Chair Car', 'Non-AC', 'Economy Class'][Math.floor(Math.random() * 4)],
            confidence: Math.floor(Math.random() * 20) + 75,
            reasons: [
                'Popular route choice',
                'Good value for money',
                'Reliable service'
            ]
        }));
    }
    
    // Apply current filters
    applyFilters();
}

function applyFilters() {
    const transportType = document.getElementById('transportType').value;
    const maxPrice = document.getElementById('maxPrice').value;
    const minRating = document.getElementById('minRating').value;
    
    let filteredRecommendations = [...recommendationsData];
    
    // Apply transport type filter
    if (transportType !== 'all') {
        filteredRecommendations = filteredRecommendations.filter(
            rec => rec.transportType === transportType
        );
    }
    
    // Apply price filter
    if (maxPrice !== 'any') {
        const maxPriceValue = parseInt(maxPrice);
        filteredRecommendations = filteredRecommendations.filter(
            rec => rec.price <= maxPriceValue
        );
    }
    
    // Apply rating filter
    if (minRating !== '0') {
        const minRatingValue = parseFloat(minRating);
        filteredRecommendations = filteredRecommendations.filter(
            rec => parseFloat(rec.rating) >= minRatingValue
        );
    }
    
    // Sort by confidence score
    filteredRecommendations.sort((a, b) => b.confidence - a.confidence);
    
    // Display filtered recommendations
    displayRecommendations(filteredRecommendations);
}

function displayRecommendations(recommendations) {
    const recommendationsGrid = document.querySelector('.recommendations-grid');
    
    if (!recommendationsGrid) return;
    
    if (recommendations.length === 0) {
        recommendationsGrid.innerHTML = `
            <div class="no-recommendations">
                <i class="fas fa-search"></i>
                <p>No routes found matching your criteria.</p>
                <p>Try adjusting your filters or search terms.</p>
            </div>
        `;
        return;
    }
    
    recommendationsGrid.innerHTML = recommendations.map(rec => `
        <div class="recommendation-card">
            <div class="recommendation-header">
                <div class="route-info">
                    <h4>${rec.from} → ${rec.to}</h4>
                    <div class="route-meta">
                        <span class="transport-type ${rec.transportType}">
                            <i class="fas fa-${getTransportIcon(rec.transportType)}"></i> 
                            ${rec.transportType.charAt(0).toUpperCase() + rec.transportType.slice(1)}
                        </span>
                        <span class="confidence-score">${rec.confidence}% Match</span>
                    </div>
                </div>
                <div class="route-price">৳${rec.price}</div>
            </div>
            <div class="recommendation-details">
                <div class="detail-row">
                    <i class="fas fa-clock"></i>
                    <span>Duration: ${rec.duration}</span>
                </div>
                <div class="detail-row">
                    <i class="fas fa-star"></i>
                    <span>Rating: ${rec.rating}/5</span>
                </div>
                <div class="detail-row">
                    <i class="fas fa-couch"></i>
                    <span>Comfort: ${rec.comfort}</span>
                </div>
            </div>
            <div class="recommendation-reasons">
                <h5>Why recommended:</h5>
                <ul class="reasons-list">
                    ${rec.reasons.map(reason => `<li>${reason}</li>`).join('')}
                </ul>
            </div>
            <div class="recommendation-actions">
                <button class="btn btn-primary" onclick="bookRoute('${rec.from}-${rec.to}')">Book Now</button>
                <button class="btn btn-secondary" onclick="viewRouteDetails(${rec.id})">View Details</button>
            </div>
        </div>
    `).join('');
}

function getTransportIcon(type) {
    const icons = {
        'bus': 'bus',
        'train': 'train',
        'ferry': 'ship'
    };
    return icons[type] || 'route';
}

function loadUserProfile() {
    // Update profile display
    const profileStats = document.querySelectorAll('.profile-stat .stat-value');
    if (profileStats.length >= 4) {
        profileStats[0].textContent = userProfile.travelFrequency;
        profileStats[1].textContent = userProfile.budgetPreference;
        profileStats[2].textContent = userProfile.timePreference;
        profileStats[3].textContent = userProfile.comfortPriority;
    }
}

function loadRecommendations() {
    // Load initial recommendations
    displayRecommendations(recommendationsData);
}

function bookRoute(route) {
    // Store route in localStorage
    localStorage.setItem('selectedRoute', route);
    
    // Redirect to booking page
    window.location.href = 'booking.html';
}

function viewRouteDetails(routeId) {
    const route = recommendationsData.find(rec => rec.id === routeId);
    if (route) {
        // Show route details in a modal or redirect
        showNotification(`Viewing details for ${route.from} → ${route.to}`, 'info');
        
        // In a real implementation, you would show a modal or redirect to a details page
        console.log('Route details:', route);
    }
}

// Export functions for global use
window.bookRoute = bookRoute;
window.viewRouteDetails = viewRouteDetails;