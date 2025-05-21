$(document).ready(function() {
    // Check if user is already logged in
    if (localStorage.getItem('token')) {
        window.location.href = 'profile.html';
        return;
    }
    
    // Register form submission
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('.alert').remove();
        
        // Get form data
        const username = $('#username').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        
        // Validate form data
        if (!username || !email || !password || !confirmPassword) {
            showMessage('error', 'All fields are required');
            return;
        }
        
        if (password !== confirmPassword) {
            showMessage('error', 'Passwords do not match');
            return;
        }
        
        if (password.length < 6) {
            showMessage('error', 'Password must be at least 6 characters');
            return;
        }
        
        // Basic email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showMessage('error', 'Please enter a valid email address');
            return;
        }
        
        // Disable submit button and show loading state
        $('#registerBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...');
        
        // Send AJAX request
        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                email: email,
                password: password
            }),
            success: function(response) {
                console.log("Server response:", response);
                
                // Handle string responses (convert to JSON if needed)
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error("Failed to parse response:", response);
                        showMessage('error', 'Invalid server response');
                        $('#registerBtn').prop('disabled', false).html('Register');
                        return;
                    }
                }
                
                // Handle the response
                if (response && response.status === 'success') {
                    showMessage('success', response.message + ' Redirecting to login page...');
                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    // Show error message
                    const errorMsg = response && response.message ? response.message : 'Registration failed with an unknown error';
                    showMessage('error', errorMsg);
                    $('#registerBtn').prop('disabled', false).html('Register');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                console.log("Response text:", xhr.responseText);
                
                // Try to parse the error response
                let errorMessage = 'Registration failed. Please try again.';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse && errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        console.error("Failed to parse error response:", e);
                        errorMessage = 'Server error: ' + error;
                    }
                }
                
                showMessage('error', errorMessage);
                $('#registerBtn').prop('disabled', false).html('Register');
            }
        });
    });
    
    // Function to show message
    function showMessage(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass}" role="alert">${message}</div>`;
        $('#registerForm').after(alertHtml);
        
        // Scroll to the message
        $('html, body').animate({
            scrollTop: $('.alert').offset().top - 100
        }, 200);
    }
});