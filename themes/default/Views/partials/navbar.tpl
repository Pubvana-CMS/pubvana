<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="{{ site.url }}">{{ site.name }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            {% if nav %}
            <ul class="navbar-nav me-auto">
                {% for item in nav %}
                <li class="nav-item{% if item.children %} dropdown{% endif %}">
                    {% if item.children %}
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ item.label }}</a>
                    <ul class="dropdown-menu">
                        {% for child in item.children %}
                        <li><a class="dropdown-item" href="{{ child.url }}">{{ child.label }}</a></li>
                        {% endfor %}
                    </ul>
                    {% else %}
                    <a class="nav-link" href="{{ item.url }}">{{ item.label }}</a>
                    {% endif %}
                </li>
                {% endfor %}
            </ul>
            {% endif %}
        </div>
    </div>
</nav>

{! theme_regions.navbar !}
