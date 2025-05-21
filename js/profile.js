$(document).ready(function () {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    // Display basic info immediately
    $('#profileUsername').text(user.username || 'Unknown User');
    $('#profileEmail').text(user.email || 'Not Available');

    fetchProfile();

    $('#updateProfileForm').submit(function (e) {
        e.preventDefault();
        updateProfile();
    });

    $('#logoutBtn').click(function () {
        logout();
    });

    function fetchProfile() {
        $.ajax({
            url: 'php/profile.php',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function (response) {
                if (response.status === 'success') {
                    const profile = response.profile;

                    // Store updated profile in localStorage
                    const updatedUser = {
                        username: profile.username,
                        email: profile.email,
                        age: profile.age,
                        dob: profile.dob,
                        contact: profile.contact
                    };
                    localStorage.setItem('user', JSON.stringify(updatedUser));

                    displayProfile(updatedUser);
                } else {
                    handleSessionError(response.message);
                }
            },
            error: function () {
                showMessage('error', 'Failed to fetch profile.');
            }
        });
    }

    function displayProfile(profile) {
        $('#profileUsername').text(profile.username || 'Unknown User');
        $('#profileEmail').text(profile.email || 'Not Available');
        $('#profileAge').text(profile.age || 'Not specified');
        $('#profileDob').text(profile.dob || 'Not specified');
        $('#profileContact').text(profile.contact || 'Not specified');

        $('#editAge').val(profile.age || '');
        $('#editDob').val(profile.dob || '');
        $('#editContact').val(profile.contact || '');

        $('#profileLoader').hide();
        $('#profileContent').show();
    }

    function updateProfile() {
        const age = $('#editAge').val();
        const dob = $('#editDob').val();
        const contact = $('#editContact').val();

        $('#updateProfileBtn').prop('disabled', true).html(`<span class="spinner-border spinner-border-sm"></span> Updating...`);

        $.ajax({
            url: 'php/profile.php',
            type: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            contentType: 'application/json',
            data: JSON.stringify({ age, dob, contact }),
            success: function (response) {
                if (response.status === 'success') {
                    $('#editProfileModal').modal('hide');
                    showMessage('success', response.message);

                    // Update localStorage with new values
                    const stored = JSON.parse(localStorage.getItem('user') || '{}');
                    const updated = {
                        ...stored,
                        age: age,
                        dob: dob,
                        contact: contact
                    };
                    localStorage.setItem('user', JSON.stringify(updated));

                    // Re-render the frontend with updated values
                    displayProfile(updated);
                } else {
                    showModalMessage('error', response.message);
                }
                $('#updateProfileBtn').prop('disabled', false).html('Update Profile');
            },
            error: function () {
                showModalMessage('error', 'Failed to update profile.');
                $('#updateProfileBtn').prop('disabled', false).html('Update Profile');
            }
        });
    }

    function logout() {
        $.ajax({
            url: 'php/profile.php',
            type: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function () {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = 'login.html';
            },
            error: function () {
                showMessage('error', 'Logout failed.');
            }
        });
    }

    function handleSessionError(message) {
        showMessage('error', message);
        if (message.includes('Invalid') || message.includes('expired')) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        }
    }

    function showMessage(type, message) {
        $('.alert').remove();
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        $('.profile-container').before(`<div class="alert ${alertClass}" role="alert">${message}</div>`);
    }

    function showModalMessage(type, message) {
        $('.modal-alert').remove();
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#updateProfileForm').before(`<div class="alert modal-alert ${alertClass}" role="alert">${message}</div>`);
    }
});
