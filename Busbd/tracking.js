/ JavaScript for Bus Tracking page

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tracking functionality
    initializeTracking();
    
    // Set up real-time updates
    startRealTimeUpdates();
    
    // Initialize map placeholder
    initializeMap();
    
    // Set up event listeners
    setupEventListeners();
});

let trackingInterval;
let busData = {
    number: 'DHK-1234',
    route: 'Dhaka → Chittagong',
    status: 'On Time',
    driver: 'Mohammed Rahman',
    contact: '+880 1712 345 678',
    progress: 60,
    distanceCovered: 180,
    distanceRemaining: 120,
    eta: '2:30 PM',
    facilities: ['wifi', 'ac', 'tv', 'charging', 'restroom'],
    updates: [
        { time: '10:30 AM', text: 'Bus departed from Dhaka' },
        { time: '11:15 AM', text: 'Bus reached Comilla' },
        { time: '12:00 PM', text: 'Bus is currently on schedule' }
    ]
};

function initializeTracking() {
    // Display initial bus data
    updateBusDisplay();
    
    // Initialize progress bar
    updateProgressBar();
    
    // Initialize facilities
    updateFacilities();
    
    // Initialize updates
    updateUpdatesList();
}

function updateBusDisplay() {
    document.getElementById('busNumberDisplay').textContent = busData.number;
    document.getElementById('routeDisplay').textContent = busData.route;
    document.getElementById('driverDisplay').textContent = busData.driver;
    document.getElementById('contactDisplay').textContent = busData.contact;
    document.getElementById('distanceCovered').textContent = `${busData.distanceCovered} km`;
    document.getElementById('distanceRemaining').textContent = `${busData.distanceRemaining} km`;
    document.getElementById('etaDisplay').textContent = busData.eta;
    
    // Update status badge
    const statusBadge = document.getElementById('statusDisplay');
    statusBadge.textContent = busData.status;
    statusBadge.className = 'value status-badge';
    
    switch(busData.status.toLowerCase()) {
        case 'on time':
            statusBadge.classList.add('ontime');
            break;
        case 'delayed':
            statusBadge.classList.add('delayed');
            break;
        default:
            statusBadge.classList.add('ontime');
    }
}

function updateProgressBar() {
    const progressFill = document.getElementById('progressFill');
    progressFill.style.width = `${busData.progress}%`;
}

function updateFacilities() {
    const facilityItems = document.querySelectorAll('.facility-item');
    facilityItems.forEach(item => {
        const facilityName = item.querySelector('span').textContent.toLowerCase();
        const isActive = busData.facilities.includes(facilityName);
        
        if (isActive) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

function updateUpdatesList() {
    const updatesList = document.getElementById('updatesList');
    updatesList.innerHTML = '';
    
    busData.updates.forEach(update => {
        const updateItem = document.createElement('div');
        updateItem.className = 'update-item';
        updateItem.innerHTML = `
            <div class="update-time">${update.time}</div>
            <div class="update-text">${update.text}</div>
        `;
        updatesList.appendChild(updateItem);
    });
}

function initializeMap() {
    // This is a placeholder for map initialization
    // In a real implementation, you would integrate with Google Maps, Mapbox, or similar
    const mapPlaceholder = document.querySelector('.map-placeholder');
    if (mapPlaceholder) {
        mapPlaceholder.innerHTML = `
            <i class="fas fa-map-marked-alt"></i>
            <p>Interactive Map</p>
            <p class="map-subtitle">Real-time bus location will be shown here</p>
            <div class="map-simulation">
                <div class="bus-marker">
                    <i class="fas fa-bus"></i>
                </div>
                <div class="route-line"></div>
            </div>
        `;
    }
}

function setupEventListeners() {
    const trackBtn = document.getElementById('trackBtn');
    const busNumberInput = document.getElementById('busNumber');
    
    if (trackBtn && busNumberInput) {
        trackBtn.addEventListener('click', function() {
            const busNumber = busNumberInput.value.trim();
            if (busNumber) {
                trackBus(busNumber);
            } else {
                showNotification('Please enter a bus number or ticket ID', 'error');
            }
        });
        
        busNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                trackBtn.click();
            }
        });
    }
}

function trackBus(busNumber) {
    // Simulate tracking request
    showNotification('Tracking bus...', 'info');
    
    // Simulate API call delay
    setTimeout(() => {
        // Generate random tracking data for demonstration
        generateRandomTrackingData();
        updateBusDisplay();
        updateProgressBar();
        updateFacilities();
        updateUpdatesList();
        
        showNotification('Bus tracking updated successfully!', 'success');
    }, 1000);
}

function generateRandomTrackingData() {
    const statuses = ['On Time', 'Delayed', 'Early', 'Stopped'];
    const routes = [
        'Dhaka → Chittagong',
        'Dhaka → Sylhet',
        'Dhaka → Rajshahi',
        'Chittagong → Cox\'s Bazar'
    ];
    
    busData.number = busNumber;
    busData.route = routes[Math.floor(Math.random() * routes.length)];
    busData.status = statuses[Math.floor(Math.random() * statuses.length)];
    busData.progress = Math.floor(Math.random() * 100);
    busData.distanceCovered = Math.floor(Math.random() * 200) + 50;
    busData.distanceRemaining = Math.floor(Math.random() * 150) + 50;
    
    // Calculate ETA based on progress
    const now = new Date();
    const etaHours = Math.floor((100 - busData.progress) * 0.06); // Rough estimate
    now.setHours(now.getHours() + etaHours);
    busData.eta = now.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    // Add new update
    const nowTime = now.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    const updateMessages = [
        'Bus is running on schedule',
        'Bus encountered minor traffic',
        'Bus made a scheduled stop',
        'Bus is ahead of schedule',
        'Bus is experiencing slight delay'
    ];
    
    busData.updates.unshift({
        time: nowTime,
        text: updateMessages[Math.floor(Math.random() * updateMessages.length)]
    });
    
    // Keep only last 10 updates
    if (busData.updates.length > 10) {
        busData.updates = busData.updates.slice(0, 10);
    }
}

function startRealTimeUpdates() {
    // Update bus data every 5 seconds
    trackingInterval = setInterval(() => {
        if (busData.number) {
            // Simulate small changes in position
            const progressChange = Math.random() * 4 - 2; // -2 to +2
            busData.progress = Math.max(0, Math.min(100, busData.progress + progressChange));
            
            // Update distance
            const totalDistance = busData.distanceCovered + busData.distanceRemaining;
            busData.distanceCovered = Math.floor(totalDistance * busData.progress / 100);
            busData.distanceRemaining = totalDistance - busData.distanceCovered;
            
            // Update display
            updateBusDisplay();
            updateProgressBar();
            
            // Update last updated time
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            const lastUpdatedElement = document.querySelector('.last-updated span');
            if (lastUpdatedElement) {
                lastUpdatedElement.textContent = timeString;
            }
        }
    }, 5000);
}

// Clean up when page is unloaded
window.addEventListener('beforeunload', function() {
    if (trackingInterval) {
        clearInterval(trackingInterval);
    }
});

// Export functions for testing
window.trackBus = trackBus;
window.generateRandomTrackingData = generateRandomTrackingData;