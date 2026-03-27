<div class="{{ cls_widget | default('widget widget-ad-unit') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_content | default('widget-content') }}">
        {! code !}
    </div>
</div>
