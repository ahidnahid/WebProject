// Product Page: product.html
document.addEventListener("DOMContentLoaded", function () {
    const buyButton = document.querySelector(".btn-primary");
    const downloadButton = document.querySelector(".btn-secondary");
    
    // Handle Buy Now Button click
    buyButton.addEventListener("click", function (e) {
        e.preventDefault();
        window.location.href = "purchase.html?product=bio101";  // Navigate to purchase page
    });

    // Handle Download Sample Button click
    downloadButton.addEventListener("click", function (e) {
        e.preventDefault();
        alert("You can now download a sample of this note!");
        // Here, you can trigger the actual download logic if needed
    });
});

// Profile Page: profile.html
document.addEventListener("DOMContentLoaded", function () {
    const editButtons = document.querySelectorAll('.btn-icon');
    const deleteButtons = document.querySelectorAll('.btn-danger');

    // Edit Button Logic (For simplicity, show alert, implement modal or form for actual edit)
    editButtons.forEach(button => {
        button.addEventListener("click", function () {
            alert("You can now edit this note!");
        });
    });

    // Delete Button Logic
    deleteButtons.forEach(button => {
        button.addEventListener("click", function () {
            if (confirm("Are you sure you want to delete this note?")) {
                alert("Note deleted successfully!");
                // Add actual deletion logic (AJAX or form submission)
            }
        });
    });
});

// Purchase Page: purchase.html
document.addEventListener("DOMContentLoaded", function () {
    const agreeCheckbox = document.getElementById('agree');
    const payButton = document.querySelector('.btn-primary');

    // Enable/Disable Pay button based on agreement checkbox
    agreeCheckbox.addEventListener('change', function () {
        payButton.disabled = !agreeCheckbox.checked;
    });

    // Simulate Step Navigation
    const steps = document.querySelectorAll('.step');
    steps.forEach(step => {
        step.addEventListener('click', function () {
            alert(`Navigating to ${step.querySelector('.step-label').textContent} step`);
            // Implement real navigation between steps
        });
    });
});

// Upload Page: upload.html
document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.querySelector('input[type="file"]');
    const form = document.querySelector('.upload-form');

    // Handle file upload preview (assuming file input)
    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (file) {
            alert(`You selected: ${file.name}`);
            // Implement file preview or upload logic
        }
    });

    // Handle form submission (prevent actual form submission for now)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alert('Your notes are being uploaded!');
        // Here, you can add your form submission (AJAX or other logic)
    });
});
