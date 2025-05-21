$(document).ready(function() {
    // Check if user is already logged in
    if (localStorage.getItem('token')) {
        window.location.href = 'profile.html';
        return;
    }
    
    // Login form submission
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('.alert').remove();
        
        // Get form data
        const username = $('#username').val();
        const password = $('#password').val();
        
        // Validate form data
        if (!username || !password) {
            showMessage('error', 'Username and password are required');
            return;
        }
        
        // Disable submit button
        $('#loginBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...');
        
        // Send AJAX request
        $.ajax({
            url: 'php/login.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                if (response.status === 'success') {
                    // Store token and user info in localStorage
                    localStorage.setItem('token', response.token);
                    localStorage.setItem('user', JSON.stringify(response.user));
                    
                    showMessage('success', response.message + ' Redirecting to profile page...');
                    setTimeout(function() {
                        window.location.href = 'profile.html';
                    }, 1500);
                } else {
                    showMessage('error', response.message);
                    $('#loginBtn').prop('disabled', false).html('Login');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Login failed. Please try again.');
                $('#loginBtn').prop('disabled', false).html('Login');
            }
        });
    });
    
    // Function to show message
    function showMessage(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass}" role="alert">${message}</div>`;
        $('#loginForm').after(alertHtml);
    }
});