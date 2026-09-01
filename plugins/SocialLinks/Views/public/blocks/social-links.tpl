{% if links %}
<div class="social-links">
    {% if title %}
    <h5 class="social-links-title">{{ title }}</h5>
    {% endif %}
    <div class="social-links-list">
        {% for link in links %}
        <a class="social-links-item" href="{{ link.url }}" target="{{ target }}" rel="{{ rel }}" aria-label="{{ link.label }}">
            <i class="{{ link.icon }}" aria-hidden="true"></i>
        </a>
        {% endfor %}
    </div>
</div>
{% endif %}