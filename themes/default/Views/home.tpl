{% extends 'layout' %}

{% block content %}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        {% include 'partials/post-list' %}
    </div>
</div>
{% else %}
<div class="row">
    <div class="col-lg-8">
        {% include 'partials/post-list' %}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endif %}
{% endblock %}