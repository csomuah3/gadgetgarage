$(document).ready(function() {
    // Registration form handler
    $('#register-form').submit(function(e) {
        e.preventDefault();

        // Get form values
        var name = $('#name').val().trim();
        var email = $('#email').val().trim();
        var password = $('#password').val();
        var phone_number = $('#phone_number').val().trim();
        var country = $('#country').val();
        var city = $('#city').val().trim();
        var role = $('input[name="role"]:checked').val();

        console.log('=== REGISTRATION ATTEMPT ===');
        console.log('Name:', name);
        console.log('Email:', email);
        console.log('Phone:', phone_number);
        console.log('Country:', country);
        console.log('City:', city);
        console.log('Role:', role);

        // Basic validation
        if (name === '' || email === '' || password === '' || phone_number === '' || country === '' || city === '') {
            alert('Please fill in all fields!');
            return;
        }

        // Simple email validation
        if (!email.includes('@') || !email.includes('.')) {
            alert('Please enter a valid email address!');
            return;
        }

        // Password length validation
        if (password.length < 6) {
            alert('Password must be at least 6 characters long!');
            return;
        }

        // Show loading
        var $btn = $('button[type="submit"]');
        $btn.prop('disabled', true).text('Registering...');

        // AJAX request for registration
        $.ajax({
            url: '../actions/register_user_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                email: email,
                password: password,
                phone_number: phone_number,
                country: country,
                city: city,
                role: role
            },
            success: function(response) {
                console.log('=== SERVER RESPONSE (SUCCESS) ===');
                console.log('Full Response:', response);
                console.log('Status:', response.status);
                console.log('Message:', response.message);
                
                if (response.status === 'success') {
                    alert('SUCCESS: ' + response.message);
                    window.location.href = 'login.php';
                } else {
                    alert('ERROR: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('=== AJAX ERROR ===');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                alert('AJAX Error: ' + xhr.responseText);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Register');
            }
        });
    });

    // Login form handler
    $('#login-form').submit(function(e) {
        e.preventDefault();

        // Get form values
        var email = $('#email').val().trim();
        var password = $('#password').val();

        console.log('=== LOGIN ATTEMPT ===');
        console.log('Email:', email);

        // Basic validation
        if (email === '' || password === '') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Please fill in all fields!',
                    icon: 'error',
                    confirmButtonColor: '#D19C97'
                });
            } else {
                alert('Please fill in all fields!');
            }
            return;
        }

        // Simple email validation for login
        if (!email.includes('@') || !email.includes('.')) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Please enter a valid email address!',
                    icon: 'error',
                    confirmButtonColor: '#D19C97'
                });
            } else {
                alert('Please enter a valid email address!');
            }
            return;
        }

        // Show loading state
        var $btn = $('button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Logging in...');

        // AJAX request for login - using separate login action file
        $.ajax({
            url: '../actions/login_customer_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                email: email,
                password: password
            },
            success: function(response) {
                console.log('=== SERVER RESPONSE (SUCCESS) ===');
                console.log('Full Response:', response);
                console.log('Status:', response.status);
                console.log('Message:', response.message);
                
                if (response.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#D19C97',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = '../index.php';
                        });
                    } else {
                        alert('SUCCESS: ' + response.message);
                        window.location.href = '../index.php';
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Login Failed',
                            text: response.message,
                            icon: 'error',
                            confirmButtonColor: '#D19C97'
                        });
                    } else {
                        alert('ERROR: ' + response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('=== AJAX ERROR ===');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Connection Error',
                        text: 'Failed to connect to server. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#D19C97'
                    });
                } else {
                    alert('Connection Error: ' + xhr.responseText);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});