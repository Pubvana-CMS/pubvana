{# Homepage: the blog list. Content-only; the layout owns the sidebar. #}
{# The design has no visible page title, so the h1 is for screen readers
   only. Without it the page starts at h2 and has no top level heading. #}
<h1 class="visually-hidden">{{ site.name }}</h1>
{# Include: the post cards partial, partials/post-list.tpl. #}
{% include 'partials/post-list' %}
