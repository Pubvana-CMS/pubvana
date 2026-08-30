<nav>
    <ul class="pagination justify-content-center">
        {% if pagination.prev_url %}
        <li class="page-item">
            <a class="page-link" href="{{ pagination.prev_url }}">Previous</a>
        </li>
        {% else %}
        <li class="page-item disabled">
            <span class="page-link">Previous</span>
        </li>
        {% endif %}

        {% for page in pagination.pages %}
        <li class="page-item{% if page.active %} active{% endif %}">
            <a class="page-link" href="{{ page.url }}">{{ page.number }}</a>
        </li>
        {% endfor %}

        {% if pagination.next_url %}
        <li class="page-item">
            <a class="page-link" href="{{ pagination.next_url }}">Next</a>
        </li>
        {% else %}
        <li class="page-item disabled">
            <span class="page-link">Next</span>
        </li>
        {% endif %}
    </ul>
</nav>
