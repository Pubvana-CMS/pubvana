{# Homepage: the blog list. Extends the master layout and fills its `content` block. #}
{% extends 'layout' %}

{% block content %}
{# Theme option layout.blog_layout chooses which side the sidebar sits on. #}
{# This branch: sidebar on the left (theme option set to sidebar-left). #}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {# Region: content blocks the site owner placed in the sidebar region. #}
        {% region 'sidebar' %}
    </div>
    <div class="col-lg-8">
        {# Include: the post cards partial, partials/post-list.tpl. #}
        {% include 'partials/post-list' %}

        {# Region: content blocks placed below the post list, inside the content column. #}
        {% region 'after-content' %}
    </div>
</div>
{% else %}
{# Default branch (default('sidebar-right')): post list first, sidebar on the right. #}
<div class="row">
    <div class="col-lg-8">
        {# Include: the post cards partial. #}
        {% include 'partials/post-list' %}

        {# Region: content blocks placed below the post list, inside the content column. #}
        {% region 'after-content' %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endif %}
{% endblock %}
