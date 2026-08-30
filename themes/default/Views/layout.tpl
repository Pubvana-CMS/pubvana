<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
    {! header !}
    {% block head_extra %}{% endblock %}
</head>
<body>

    {% include 'partials/navbar' %}

    {% if theme_options.hero.show %}
    {% include 'partials/hero' %}
    {% endif %}

    {% if theme_options.breadcrumbs.enabled | default('1') and breadcrumbs %}
    {% include 'partials/breadcrumbs' %}
    {% endif %}

    {! before_content !}

    <main class="container my-4">
        {% block content %}{% endblock %}
    </main>

    {! after_content !}

    {% include 'partials/footer' %}

    <script src="/assets/theme/default/js/bootstrap.bundle.min.js"></script>
    {% for js in footer.js %}
    <script src="{{ js }}"></script>
    {% endfor %}
    {% block scripts_extra %}{% endblock %}
</body>
</html>
