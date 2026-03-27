<div class="{{ cls_widget | default('widget widget-social-links') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_social_links | default('widget-social-links-list') }}">
        {% for link in links %}
            <a href="{{ link.url }}" target="_blank" rel="noopener" class="{{ cls_social_link | default('widget-social-link') }}">
                <i class="{{ link.icon }}"></i>
                {% if style == 'icons+text' %}
                    {{ link.platform }}
                {% endif %}
            </a>
        {% endfor %}
    </div>
</div>
