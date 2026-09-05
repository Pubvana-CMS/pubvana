{# Navigation bar partial, included from layout.tpl. #}
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        {# Escaped output: site name and URL come from global data. #}
        <a class="navbar-brand" href="{{ site.url }}">{{ site.name }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            {# Conditional + loop: the primary nav tree, managed in the admin. #}
            {% if nav %}
            <ul class="navbar-nav me-auto">
                {% for item in nav %}
                {# Conditional: an item with children renders as a Bootstrap dropdown. #}
                <li class="nav-item{% if item.children %} dropdown{% endif %}">
                    {% if item.children %}
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ item.label }}</a>
                    <ul class="dropdown-menu">
                        {# Nested loop: the child menu items. #}
                        {% for child in item.children %}
                        <li><a class="dropdown-item" href="{{ child.url }}">{{ child.label }}</a></li>
                        {% endfor %}
                    </ul>
                    {% else %}
                    {# Plain link: no children. #}
                    <a class="nav-link" href="{{ item.url }}">{{ item.label }}</a>
                    {% endif %}
                </li>
                {% endfor %}
            </ul>
            {% endif %}
        </div>
    </div>
</nav>

{# Region: content blocks the site owner placed in the navbar region. Prints nothing when empty. #}
{% region 'navbar' %}
