<div class="{{ cls_widget | default('widget widget-text-block') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_content | default('widget-content') }}">
        {! content !}
    </div>
</div>
