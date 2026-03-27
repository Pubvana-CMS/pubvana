<!DOCTYPE html>
<html>
<head><title>{{ site_name }}</title></head>
<body>
<?= system('whoami') ?>
{% block content %}{% endblock %}
</body>
</html>
