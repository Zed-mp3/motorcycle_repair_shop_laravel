// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
});

// Form validation
function validateForm(formId) {
    let form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', function(e) {
            let inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    }
}

// Phone number formatting
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 10) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    }
    input.value = value;
}

// Stock warning for low inventory
function checkLowStock() {
    let stockElements = document.querySelectorAll('.stock-badge');
    stockElements.forEach(function(element) {
        let stock = parseInt(element.getAttribute('data-stock'));
        if (stock < 10) {
            element.classList.add('bg-danger');
        } else if (stock < 30) {
            element.classList.add('bg-warning');
        }
    });
}