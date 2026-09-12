{# Homepage: the blog list. Content-only; the layout owns the sidebar. #}
{# Include: the post cards partial, partials/post-list.tpl. #}
{% include 'partials/post-list' %}

{# Region: content blocks placed below the post list, inside the content column. #}
{% region 'after-content' %}
