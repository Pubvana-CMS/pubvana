function formcheck()
{
    var siteName = document.getElementById('site_name').value;
    var adminUser = document.getElementById('admin_username').value;
    var adminPass = document.getElementById('admin_pass').value;
    var adminEmail = document.getElementById('admin_email').value;

    var error = '';

    if (siteName.length < 2) {
        error += 'Please enter a site name.\n';
    }

    if (adminUser.length < 1 || adminUser.length > 30) {
        error += 'Admin username must be between 1 and 30 characters.\n';
    }

    if (adminPass.length < 8) {
        error += 'Admin password must be at least 8 characters.\n';
    }

    var lowerPass = adminPass.toLowerCase();
    if (adminUser.length > 0 && lowerPass.indexOf(adminUser.toLowerCase()) !== -1) {
        error += 'Admin password cannot contain the username.\n';
    }

    var emailLocal = adminEmail.split('@')[0];
    if (emailLocal.length > 0 && lowerPass.indexOf(emailLocal.toLowerCase()) !== -1) {
        error += 'Admin password cannot contain the email.\n';
    }

    if (adminEmail.length < 5 || adminEmail.indexOf('@') < 1 || adminEmail.indexOf('.') < 2) {
        error += 'Please enter a valid admin email.\n';
    }

    if (error != '') {
        alert(error);
        return false;
    }

    return true;
}