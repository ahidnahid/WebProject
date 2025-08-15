// JavaScript for Booking page

document.addEventListener('DOMContentLoaded', function() {
    // Initialize booking functionality
    initializeBooking();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load saved data if any
    loadSavedData();
    
    // Set minimum date to today
    setMinDate();
});

let currentStep = 1;
let selectedSeats = [];
let seatPrice = 0;
let bookingData = {
    route: '',
    date: '',
    returnDate: '',
    isRoundTrip: false,
    bus: '',
    seats: [],
    totalPrice: 0,
    passenger: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        address: '',
        idNumber: ''
    }
};

function initializeBooking() {
    // Initialize form steps
    updateStepDisplay();
    
    // Generate seat layout
    generateSeatLayout();
    
    // Set up date validation
    setupDateValidation();
}

function setupEventListeners() {
    // Round trip checkbox
    const roundTripCheckbox = document.getElementById('roundTrip');
    if (roundTripCheckbox) {
        roundTripCheckbox.addEventListener('change', function() {
            const returnDateField = document.getElementById('returnDate');
            const returnDateLabel = returnDateField.closest('.form-group').querySelector('label');
            
            if (this.checked) {
                returnDateField.required = true;
                returnDateLabel.textContent = 'Return Date *';
                bookingData.isRoundTrip = true;
            } else {
                returnDateField.required = false;
                returnDateLabel.textContent = 'Return Date (Optional)';
                bookingData.isRoundTrip = false;
                bookingData.returnDate = '';
            }
        });
    }
    
    // Form submission
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            processBooking();
        });
    }
    
    // Swap cities functionality
    const swapCities = document.querySelector('.swap-cities');
    if (swapCities) {
        swapCities.addEventListener('click', swapCitiesFunction);
    }
    
    // City selection change
    const fromCity = document.getElementById('fromCity');
    const toCity = document.getElementById('toCity');
    
    if (fromCity && toCity) {
        fromCity.addEventListener('change', validateRoute);
        toCity.addEventListener('change', validateRoute);
    }
}

function loadSavedData() {
    // Load selected route from localStorage
    const savedRoute = localStorage.getItem('selectedRoute');
    if (savedRoute) {
        const [from, to] = savedRoute.split('-');
        const fromCity = document.getElementById('fromCity');
        const toCity = document.getElementById('toCity');
        
        if (fromCity && toCity) {
            fromCity.value = from.charAt(0).toUpperCase() + from.slice(1);
            toCity.value = to.charAt(0).toUpperCase() + to.slice(1);
            bookingData.route = `${fromCity.value} → ${toCity.value}`;
        }
        
        // Clear saved route
        localStorage.removeItem('selectedRoute');
    }
}

function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    const departureDate = document.getElementById('departureDate');
    const returnDate = document.getElementById('returnDate');
    
    if (departureDate) {
        departureDate.min = today;
    }
    
    if (returnDate) {
        returnDate.min = today;
    }
}

function setupDateValidation() {
    const departureDate = document.getElementById('departureDate');
    const returnDate = document.getElementById('returnDate');
    
    if (departureDate && returnDate) {
        departureDate.addEventListener('change', function() {
            returnDate.min = this.value;
            if (returnDate.value && returnDate.value < this.value) {
                returnDate.value = this.value;
            }
        });
    }
}

function validateRoute() {
    const fromCity = document.getElementById('fromCity').value;
    const toCity = document.getElementById('toCity').value;
    
    if (fromCity && toCity && fromCity === toCity) {
        showNotification('Departure and destination cities must be different', 'error');
        return false;
    }
    
    return true;
}

function swapCitiesFunction() {
    const fromCity = document.getElementById('fromCity');
    const toCity = document.getElementById('toCity');
    
    if (fromCity && toCity) {
        const temp = fromCity.value;
        fromCity.value = toCity.value;
        toCity.value = temp;
        
        validateRoute();
    }
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < 4) {
            currentStep++;
            updateStepDisplay();
            saveStepData();
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepDisplay();
    }
}

function updateStepDisplay() {
    // Update step indicators
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else if (index + 1 < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
    
    // Update form steps
    const formSteps = document.querySelectorAll('.form-step');
    formSteps.forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Update booking summary on final step
    if (currentStep === 4) {
        updateBookingSummary();
    }
}

function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        case 4:
            return validateStep4();
        default:
            return true;
    }
}

function validateStep1() {
    const fromCity = document.getElementById('fromCity').value;
    const toCity = document.getElementById('toCity').value;
    const departureDate = document.getElementById('departureDate').value;
    const returnDate = document.getElementById('returnDate').value;
    
    if (!fromCity || !toCity) {
        showNotification('Please select both departure and destination cities', 'error');
        return false;
    }
    
    if (fromCity === toCity) {
        showNotification('Departure and destination cities must be different', 'error');
        return false;
    }
    
    if (!departureDate) {
        showNotification('Please select departure date', 'error');
        return false;
    }
    
    if (bookingData.isRoundTrip && !returnDate) {
        showNotification('Please select return date for round trip', 'error');
        return false;
    }
    
    return true;
}

function validateStep2() {
    const selectedBus = document.querySelector('input[name="busSelect"]:checked');
    
    if (!selectedBus) {
        showNotification('Please select a bus', 'error');
        return false;
    }
    
    return true;
}

function validateStep3() {
    if (selectedSeats.length === 0) {
        showNotification('Please select at least one seat', 'error');
        return false;
    }
    
    return true;
}

function validateStep4() {
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    const idNumber = document.getElementById('idNumber').value;
    
    if (!firstName || !lastName || !email || !phone || !address || !idNumber) {
        showNotification('Please fill in all passenger details', 'error');
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Please enter a valid email address', 'error');
        return false;
    }
    
    return true;
}

function saveStepData() {
    switch (currentStep - 1) {
        case 1:
            const fromCity = document.getElementById('fromCity').value;
            const toCity = document.getElementById('toCity').value;
            const departureDate = document.getElementById('departureDate').value;
            const returnDate = document.getElementById('returnDate').value;
            
            bookingData.route = `${fromCity} → ${toCity}`;
            bookingData.date = departureDate;
            bookingData.returnDate = returnDate;
            break;
            
        case 2:
            const selectedBus = document.querySelector('input[name="busSelect"]:checked');
            if (selectedBus) {
                bookingData.bus = selectedBus.value;
                // Set seat price based on bus selection
                const busPrice = selectedBus.closest('.bus-option').querySelector('.price').textContent;
                seatPrice = parseInt(busPrice.replace('৳', ''));
            }
            break;
            
        case 3:
            bookingData.seats = [...selectedSeats];
            bookingData.totalPrice = selectedSeats.length * seatPrice;
            break;
    }
}

function generateSeatLayout() {
    const seatGrid = document.querySelector('.seat-grid');
    if (!seatGrid) return;
    
    seatGrid.innerHTML = '';
    
    // Generate 32 seats (8 rows x 4 columns)
    for (let i = 1; i <= 32; i++) {
        const seat = document.createElement('div');
        seat.className = 'seat available';
        seat.textContent = i;
        seat.addEventListener('click', () => toggleSeat(i, seat));
        
        // Randomly make some seats occupied
        if (Math.random() < 0.3) {
            seat.classList.remove('available');
            seat.classList.add('occupied');
            seat.removeEventListener('click', toggleSeat);
        }
        
        seatGrid.appendChild(seat);
    }
}

function toggleSeat(seatNumber, seatElement) {
    if (seatElement.classList.contains('occupied')) {
        return;
    }
    
    if (seatElement.classList.contains('selected')) {
        seatElement.classList.remove('selected');
        seatElement.classList.add('available');
        selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
    } else {
        seatElement.classList.remove('available');
        seatElement.classList.add('selected');
        selectedSeats.push(seatNumber);
    }
    
    updateSeatInfo();
}

function updateSeatInfo() {
    const selectedSeatsElement = document.getElementById('selectedSeats');
    const totalPriceElement = document.getElementById('totalPrice');
    
    if (selectedSeatsElement) {
        selectedSeatsElement.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
    }
    
    if (totalPriceElement && seatPrice > 0) {
        totalPriceElement.textContent = `৳${selectedSeats.length * seatPrice}`;
    }
}

function updateBookingSummary() {
    const summaryRoute = document.getElementById('summaryRoute');
    const summaryDate = document.getElementById('summaryDate');
    const summaryBus = document.getElementById('summaryBus');
    const summarySeats = document.getElementById('summarySeats');
    const summaryTotal = document.getElementById('summaryTotal');
    
    if (summaryRoute) summaryRoute.textContent = bookingData.route;
    if (summaryDate) summaryDate.textContent = bookingData.date;
    if (summaryBus) summaryBus.textContent = getBusName(bookingData.bus);
    if (summarySeats) summarySeats.textContent = bookingData.seats.join(', ');
    if (summaryTotal) summaryTotal.textContent = `৳${bookingData.totalPrice}`;
}

function getBusName(busValue) {
    const busNames = {
        'greenline': 'Green Line Paribahan',
        'shohagh': 'Shohagh Paribahan',
        'hanif': 'Hanif Paribahan'
    };
    return busNames[busValue] || busValue;
}

function processBooking() {
    // Save passenger details
    bookingData.passenger = {
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        idNumber: document.getElementById('idNumber').value
    };
    
    // Show processing message
    showNotification('Processing your booking...', 'info');
    
    // Simulate booking process
    setTimeout(() => {
        // In a real application, you would send this data to a server
        console.log('Booking data:', bookingData);
        
        // Show success message
        showNotification('Booking confirmed successfully! Check your email for details.', 'success');
        
        // Reset form after successful booking
        setTimeout(() => {
            if (confirm('Booking successful! Would you like to make another booking?')) {
                resetBookingForm();
            } else {
                window.location.href = 'index.html';
            }
        }, 2000);
    }, 2000);
}

function resetBookingForm() {
    // Reset all form data
    currentStep = 1;
    selectedSeats = [];
    seatPrice = 0;
    bookingData = {
        route: '',
        date: '',
        returnDate: '',
        isRoundTrip: false,
        bus: '',
        seats: [],
        totalPrice: 0,
        passenger: {
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
            address: '',
            idNumber: ''
        }
    };
    
    // Reset form
    document.getElementById('bookingForm').reset();
    
    // Regenerate seat layout
    generateSeatLayout();
    
    // Update display
    updateStepDisplay();
    updateSeatInfo();
    
    // Reset round trip state
    const returnDateField = document.getElementById('returnDate');
    const returnDateLabel = returnDateField.closest('.form-group').querySelector('label');
    returnDateField.required = false;
    returnDateLabel.textContent = 'Return Date (Optional)';
}

// Export functions for testing
window.nextStep = nextStep;
window.prevStep = prevStep;
window.toggleSeat = toggleSeat;