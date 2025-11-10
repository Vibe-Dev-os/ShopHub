// Main JavaScript for E-Commerce Application

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrengthUI(strength);
        });
    }
    
    // Confirm password match
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Quantity input validation
    const quantityInputs = document.querySelectorAll('input[type="number"][name="quantity"]');
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max) || 999;
            let value = parseInt(this.value);
            
            if (value < min) this.value = min;
            if (value > max) this.value = max;
        });
    });
    
    // Image preview for file uploads
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here
                    console.log('Image selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Smooth scroll to top
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'block';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        });
        
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Add to cart animation
    const addToCartButtons = document.querySelectorAll('button[name="add_to_cart"]');
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            this.innerHTML = '<i class="bi bi-check-circle"></i> Added!';
            this.classList.remove('btn-primary');
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
                this.classList.remove('btn-success');
                this.classList.add('btn-primary');
            }, 2000);
        });
    });
    
    // AJAX Add to Cart functionality
    const ajaxAddToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    ajaxAddToCartButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const originalHTML = this.innerHTML;
            
            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            
            // Send AJAX request
            fetch('ajax/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    this.innerHTML = '<i class="bi bi-check-circle"></i>';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');
                    
                    // Show success message
                    showToast('Success', data.message, 'success');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                        this.disabled = false;
                    }, 2000);
                } else {
                    // Show error state
                    this.innerHTML = '<i class="bi bi-x-circle"></i>';
                    showToast('Error', data.message, 'danger');
                    
                    // If not logged in, redirect to login
                    if (data.message.includes('login')) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalHTML;
                this.disabled = false;
                showToast('Error', 'Failed to add product to cart', 'danger');
            });
        });
    });
    
    // Buy Now functionality - Clear cart, add product, and redirect to checkout
    const buyNowButtons = document.querySelectorAll('.buy-now-btn');
    buyNowButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-product-id');
            const originalHTML = this.innerHTML;
            
            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            // Send AJAX request to buy now (clears cart and adds only this product)
            fetch('ajax/buy-now.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to checkout
                    window.location.href = 'checkout.php';
                } else {
                    // Show error
                    showToast('Error', data.message, 'danger');
                    
                    // If not logged in, redirect to login
                    if (data.message.includes('login')) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        // Reset button
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalHTML;
                this.disabled = false;
                showToast('Error', 'Failed to process request', 'danger');
            });
        });
    });
    
    // Buy Now functionality for product detail page (with quantity)
    const buyNowDetailButtons = document.querySelectorAll('.buy-now-detail-btn');
    buyNowDetailButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-product-id');
            const quantityInput = document.getElementById('quantity');
            const quantity = quantityInput ? quantityInput.value : 1;
            const originalHTML = this.innerHTML;
            
            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            // Send AJAX request to buy now (clears cart and adds only this product)
            fetch('ajax/buy-now.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to checkout
                    window.location.href = 'checkout.php';
                } else {
                    // Show error
                    showToast('Error', data.message, 'danger');
                    
                    // If not logged in, redirect to login
                    if (data.message.includes('login')) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        // Reset button
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalHTML;
                this.disabled = false;
                showToast('Error', 'Failed to process request', 'danger');
            });
        });
    });
});

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

// Update password strength UI
function updatePasswordStrengthUI(strength) {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;
    
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColor = ['danger', 'warning', 'info', 'primary', 'success'];
    
    strengthIndicator.className = `badge bg-${strengthColor[strength - 1]}`;
    strengthIndicator.textContent = strengthText[strength - 1] || '';
}

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

// Confirm delete action
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
}

// Show loading spinner
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingSpinner';
    loadingDiv.className = 'position-fixed top-50 start-50 translate-middle';
    loadingDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(loadingDiv);
}

// Hide loading spinner
function hideLoading() {
    const loadingDiv = document.getElementById('loadingSpinner');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Show toast notification
function showToast(title, message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
