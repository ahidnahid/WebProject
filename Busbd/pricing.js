// JavaScript for Dynamic Pricing page

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pricing functionality
    initializePricing();
    
    // Set up event listeners
    setupEventListeners();
    
    // Initialize price chart
    initializePriceChart();
    
    // Start real-time price updates
    startPriceUpdates();
});

let priceChart;
let currentRoute = 'dhaka-chittagong';
let priceData = {
    'dhaka-chittagong': {
        current: 850,
        change: 50,
        changePercent: 6.3,
        history: generatePriceHistory(850, 24),
        factors: {
            demand: 85,
            timeOfDay: 60,
            season: 30,
            weather: 45,
            dayOfWeek: 75,
            fuelPrice: 25
        },
        predictions: {
            '6h': 880,
            '12h': 920,
            '24h': 860
        },
        confidence: 87,
        stats: {
            low: 780,
            high: 920,
            average: 835
        }
    },
    'dhaka-sylhet': {
        current: 650,
        change: -20,
        changePercent: -3.0,
        history: generatePriceHistory(650, 24),
        factors: {
            demand: 70,
            timeOfDay: 45,
            season: 25,
            weather: 30,
            dayOfWeek: 60,
            fuelPrice: 20
        },
        predictions: {
            '6h': 670,
            '12h': 690,
            '24h': 640
        },
        confidence: 82,
        stats: {
            low: 590,
            high: 720,
            average: 645
        }
    },
    'dhaka-rajshahi': {
        current: 550,
        change: 15,
        changePercent: 2.8,
        history: generatePriceHistory(550, 24),
        factors: {
            demand: 55,
            timeOfDay: 40,
            season: 20,
            weather: 35,
            dayOfWeek: 50,
            fuelPrice: 15
        },
        predictions: {
            '6h': 560,
            '12h': 580,
            '24h': 545
        },
        confidence: 78,
        stats: {
            low: 480,
            high: 620,
            average: 535
        }
    },
    'chittagong-coxsbazar': {
        current: 400,
        change: 30,
        changePercent: 8.1,
        history: generatePriceHistory(400, 24),
        factors: {
            demand: 90,
            timeOfDay: 70,
            season: 80,
            weather: 60,
            dayOfWeek: 85,
            fuelPrice: 25
        },
        predictions: {
            '6h': 420,
            '12h': 450,
            '24h': 410
        },
        confidence: 91,
        stats: {
            low: 350,
            high: 480,
            average: 395
        }
    }
};

function initializePricing() {
    // Set initial route
    updateRouteDisplay();
    
    // Update last updated time
    updateLastUpdatedTime();
}

function setupEventListeners() {
    const routeSelect = document.getElementById('routeSelect');
    
    if (routeSelect) {
        routeSelect.addEventListener('change', function() {
            currentRoute = this.value;
            updateRouteDisplay();
            updatePriceChart();
            showNotification('Route updated successfully!', 'success');
        });
    }
}

function updateRouteDisplay() {
    const data = priceData[currentRoute];
    
    // Update current price
    document.getElementById('currentPrice').textContent = `৳${data.current}`;
    
    // Update price change
    const priceChangeElement = document.getElementById('priceChange');
    const isPositive = data.change > 0;
    const changeIcon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
    const changeClass = isPositive ? 'up' : 'down';
    
    priceChangeElement.className = `price-change ${changeClass}`;
    priceChangeElement.innerHTML = `
        <i class="fas ${changeIcon}"></i> ৳${Math.abs(data.change)} (${data.changePercent > 0 ? '+' : ''}${data.changePercent}%)
    `;
    
    // Update predictions
    document.getElementById('prediction6h').textContent = `৳${data.predictions['6h']} (${calculatePercentageChange(data.current, data.predictions['6h'])}%)`;
    document.getElementById('prediction12h').textContent = `৳${data.predictions['12h']} (${calculatePercentageChange(data.current, data.predictions['12h'])}%)`;
    document.getElementById('prediction24h').textContent = `৳${data.predictions['24h']} (${calculatePercentageChange(data.current, data.predictions['24h'])}%)`;
    
    // Update confidence
    document.getElementById('confidenceLevel').textContent = `${data.confidence}%`;
    
    // Update stats
    document.getElementById('price24hLow').textContent = `৳${data.stats.low}`;
    document.getElementById('price24hHigh').textContent = `৳${data.stats.high}`;
    document.getElementById('priceAverage').textContent = `৳${data.stats.average}`;
    
    // Update factors
    updateFactorsDisplay(data.factors);
    
    // Update AI recommendation
    updateAIRecommendation(data);
}

function updateFactorsDisplay(factors) {
    const factorItems = document.querySelectorAll('.factor-item');
    
    factorItems.forEach(item => {
        const factorName = item.querySelector('span').textContent.toLowerCase().replace(' ', '');
        const factorValue = factors[factorName] || 0;
        
        // Update factor bar
        const factorFill = item.querySelector('.factor-fill');
        if (factorFill) {
            factorFill.style.width = `${factorValue}%`;
        }
        
        // Update factor impact class
        item.className = 'factor-item';
        if (factorValue >= 70) {
            item.classList.add('high');
        } else if (factorValue >= 40) {
            item.classList.add('medium');
        } else {
            item.classList.add('low');
        }
    });
}

function updateAIRecommendation(data) {
    const recommendationElement = document.getElementById('aiRecommendation');
    let recommendation = '';
    
    if (data.change > 0 && data.changePercent > 5) {
        recommendation = 'Prices are rising. Consider booking now to lock in current rates before they increase further.';
    } else if (data.change < 0 && data.changePercent < -3) {
        recommendation = 'Prices are dropping. You might want to wait a bit for potentially better rates, but don\'t wait too long.';
    } else if (data.predictions['12h'] > data.current * 1.05) {
        recommendation = 'Book now for best price. Prices are expected to rise in the next 12 hours due to increased demand.';
    } else if (data.predictions['24h'] < data.current * 0.95) {
        recommendation = 'Consider waiting if possible. Prices are expected to drop in the next 24 hours.';
    } else {
        recommendation = 'Current prices are stable. This is a good time to book at fair market rates.';
    }
    
    recommendationElement.textContent = recommendation;
}

function calculatePercentageChange(current, future) {
    const change = future - current;
    const percentage = (change / current) * 100;
    return percentage > 0 ? `+${percentage.toFixed(1)}` : percentage.toFixed(1);
}

function initializePriceChart() {
    const ctx = document.getElementById('priceChart');
    if (!ctx) return;
    
    const data = priceData[currentRoute];
    const labels = [];
    const prices = [];
    
    // Generate labels for the last 24 hours
    for (let i = 23; i >= 0; i--) {
        const hour = new Date();
        hour.setHours(hour.getHours() - i);
        labels.push(hour.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        }));
        prices.push(data.history[23 - i]);
    }
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Price (৳)',
                data: prices,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '৳' + value;
                        }
                    }
                },
                x: {
                    ticks: {
                        maxTicksLimit: 8
                    }
                }
            }
        }
    });
}

function updatePriceChart() {
    if (!priceChart) return;
    
    const data = priceData[currentRoute];
    const labels = [];
    const prices = [];
    
    // Generate labels for the last 24 hours
    for (let i = 23; i >= 0; i--) {
        const hour = new Date();
        hour.setHours(hour.getHours() - i);
        labels.push(hour.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        }));
        prices.push(data.history[23 - i]);
    }
    
    priceChart.data.labels = labels;
    priceChart.data.datasets[0].data = prices;
    priceChart.update();
}

function generatePriceHistory(basePrice, hours) {
    const history = [];
    let currentPrice = basePrice;
    
    for (let i = 0; i < hours; i++) {
        // Generate realistic price fluctuations
        const change = (Math.random() - 0.5) * 40; // -20 to +20
        currentPrice = Math.max(basePrice * 0.8, Math.min(basePrice * 1.2, currentPrice + change));
        history.push(Math.round(currentPrice));
    }
    
    return history;
}

function startPriceUpdates() {
    // Update prices every 10 seconds
    setInterval(() => {
        updatePrices();
    }, 10000);
}

function updatePrices() {
    const data = priceData[currentRoute];
    
    // Generate small price changes
    const changeAmount = (Math.random() - 0.5) * 20; // -10 to +10
    const newPrice = Math.max(data.stats.low, Math.min(data.stats.high, data.current + changeAmount));
    
    // Update data
    data.current = Math.round(newPrice);
    data.change = data.current - data.stats.average;
    data.changePercent = ((data.change / data.stats.average) * 100).toFixed(1);
    
    // Update history (remove oldest, add newest)
    data.history.shift();
    data.history.push(data.current);
    
    // Update predictions with some randomness
    Object.keys(data.predictions).forEach(key => {
        const predictionChange = (Math.random() - 0.5) * 30;
        data.predictions[key] = Math.round(data.current + predictionChange);
    });
    
    // Update confidence
    data.confidence = Math.max(70, Math.min(95, data.confidence + (Math.random() - 0.5) * 5));
    
    // Update display
    updateRouteDisplay();
    updatePriceChart();
    updateLastUpdatedTime();
}

function updateLastUpdatedTime() {
    const lastUpdatedElement = document.getElementById('lastUpdated');
    if (lastUpdatedElement) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
        lastUpdatedElement.textContent = timeString;
    }
}

// Export functions for testing
window.updatePrices = updatePrices;
window.updateRouteDisplay = updateRouteDisplay;