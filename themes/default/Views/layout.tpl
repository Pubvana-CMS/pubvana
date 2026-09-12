{# Master page shell. layout.tpl is the whole page: head, navbar, hero, #}
{# breadcrumbs, sidebar, content, footer. Page templates are content-only; #}
{# PublicController renders them separately and passes the finished HTML #}
{# in as `content`. Nothing extends this file. #}
{# Theme assets load straight from /assets/theme/... (served by AssetService, never copied into public/). #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {# Raw output: plugin-contributed <head> markup and stylesheets. Trusted HTML, {{ }} would escape it. #}
    {# Plugin CSS loads FIRST. The theme's own stylesheets below load after it, so theme rules override plugin rules on ties. #}
    {! header !}
    <link rel="stylesheet" href="/assets/theme/default/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/theme/default/css/pubvana.css">
</head>
<body>

    {# Include: renders partials/navbar.tpl inline. Included templates see the same variables. #}
    {% include 'partials/navbar' %}

    {# Theme option branch: the hero banner renders only when the site owner enables it. #}
    {% if theme_options.hero.show %}
    {% include 'partials/hero' %}
    {% endif %}

    {# Filter: default('1') covers a missing option. Renders only when breadcrumbs exist for this page. #}
    {% if theme_options.breadcrumbs.enabled | default('1') and breadcrumbs %}
    {% include 'partials/breadcrumbs' %}
    {% endif %}

    {# Region: content blocks the site owner placed via Admin > Appearance > Themes > Regions. #}
    {# A region with nothing placed in it prints nothing, so it is safe to always output. #}
    {% region 'before-content' %}

    {# Include: one-shot flash messages (login notices, form feedback, etc.), before the page body. #}
    {% include 'partials/alerts' %}

    {# Page body. The sidebar renders when the layout.page_sidebar theme option #}
    {# covers this page kind (home / not_home) AND the sidebar region has #}
    {# blocks in it. An empty aside collapses the row to one column. Sidebar #}
    {# side (left/right) follows blog_layout. #}
    <main class="container my-4">
        {% if sidebar_kind %}
        <div class="row">
            {% if sidebar_kind == 'sidebar-left' %}
            <div class="col-lg-4">
                {% region 'sidebar' %}
            </div>
            <div class="col-lg-8">
                {# Raw output: the page content, assembled by PublicController. #}
                {! content !}
            </div>
            {% else %}
            <div class="col-lg-8">
                {# Raw output: the page content, assembled by PublicController. #}
                {! content !}
            </div>
            <div class="col-lg-4">
                {% region 'sidebar' %}
            </div>
            {% endif %}
        </div>
        {% else %}
        {# Raw output: the page content, assembled by PublicController. #}
        {! content !}
        {% endif %}
    </main>

    {# Include: footer columns, footer region, and the copyright line. #}
    {% include 'partials/footer' %}

    <script src="/assets/theme/default/js/bootstrap.bundle.min.js"></script>
    {# Raw output: plugin scripts, pre-assembled into one block by the controller (mirror of {! header !}). #}
    {! scripts_footer !}
</body>
</html>
