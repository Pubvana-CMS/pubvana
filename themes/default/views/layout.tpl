<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ page_title | default(site_name) }}</title>
    {% if seo.description %}
    <meta name="description" content="{{ seo.description }}">
    {% endif %}
    {% if seo.og_title %}
    <meta property="og:title" content="{{ seo.og_title }}">
    <meta property="og:description" content="{{ seo.og_description | default('') }}">
    {% if seo.og_image %}
    <meta property="og:image" content="{{ seo.og_image }}">
    {% endif %}
    {% endif %}
    <link rel="alternate" type="application/rss+xml" title="{{ site_name }} RSS Feed" href="{% site_url 'feed' %}">
    <link rel="alternate" type="application/atom+xml" title="{{ site_name }} Atom Feed" href="{% site_url 'atom' %}">
    {% if lang_switcher.buttons %}
    {% for btn in lang_switcher.buttons %}
    <link rel="alternate" hreflang="{{ btn.code }}" href="{% base_url btn.url %}">
    {% endfor %}
    <link rel="alternate" hreflang="x-default" href="{% base_url %}">
    {% endif %}

    <!-- Bootstrap 5 -->
    <link href="{% theme_url 'css/bootstrap.css' %}" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="{% theme_url 'css/theme.css' %}" rel="stylesheet">

    {% block head_extra %}{% endblock %}

    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{! analytics_id !}');</script>
    {% endif %}
    {% if json_ld %}
    <script type="application/ld+json">{! json_ld !}</script>
    {% endif %}
</head>
<body>

{% if preview_mode %}
<div style="background:#f59e0b;color:#000;text-align:center;padding:8px 16px;font-size:14px;font-weight:600;position:sticky;top:0;z-index:9999;">
    &#128065; {% lang 'Blog.previewModeBanner' %}
</div>
{% endif %}

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{% site_url %}">
            {{ site_name }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{% site_url %}">{% lang 'Blog.home' %}</a></li>
                <li class="nav-item"><a class="nav-link" href="{% site_url 'blog' %}">{% lang 'Blog.blog' %}</a></li>
                {% for navItem in primary_nav %}
                <li class="nav-item">
                    <a class="nav-link" href="{{ navItem.url }}" target="{{ navItem.target }}">
                        {{ navItem.label }}
                    </a>
                </li>
                {% endfor %}
            </ul>
            <form class="d-flex" action="{% site_url 'search' %}" method="GET">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="{% lang 'Blog.searchPlaceholder' %}" aria-label="{% lang 'Blog.search' %}">
                <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</nav>

{% if flash_success %}
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ flash_success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
{% endif %}
{% if flash_error %}
<div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ flash_error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
{% endif %}

<!-- Main Content -->
<main class="py-5">
    <div class="container">
        {% widget_area 'before-content' %}
        <div class="row">
            <div class="{% if show_sidebar == '1' %}col-lg-8{% else %}col-12{% endif %}">
                {% block content %}{% endblock %}
            </div>
            {% if show_sidebar == '1' %}
            <div class="col-lg-4">
                {% include 'partials/sidebar' %}
            </div>
            {% endif %}
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5" data-bs-theme="dark">
    <div class="container">
        <div class="row">
            <div class="col-md-3 mb-4">
                <h5>{{ site_name }}</h5>
                <p class="text-white-50">{{ site_tagline }}</p>
                {% for s in social_links %}
                    <a href="{{ s.url }}" class="text-white-50 me-2" target="_blank" rel="noopener">
                        <i class="{{ s.icon }}"></i>
                    </a>
                {% endfor %}
                {% if footer_nav %}
                <nav class="mt-3">
                    {% for item in footer_nav %}
                    <a href="{% site_url item.url %}" class="text-white-50 d-block small"{% if item.target == '_blank' %} target="_blank" rel="noopener"{% endif %}>{{ item.label }}</a>
                    {% endfor %}
                </nav>
                {% endif %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-1' %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-2' %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-3' %}
            </div>
        </div>
        <hr class="border-secondary">
        <div class="row">
            <div class="col text-center text-white-50 small">
                {% if footer_copyright %}
                    {{ footer_copyright }}
                {% else %}
                    &copy; {{ site_name }}. {% lang 'Blog.allRightsReserved' %}
                {% endif %}
                &nbsp;&middot;&nbsp;
                <a href="{% site_url 'feed' %}" class="text-white-50"><i class="fas fa-rss"></i> {% lang 'Blog.rssFeed' %}</a>
                {% if sitemap_enabled %}
                &nbsp;&middot;&nbsp;
                <a href="{% site_url 'sitemap.xml' %}" class="text-white-50">{% lang 'Blog.sitemap' %}</a>
                {% endif %}
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
{% block scripts %}{% endblock %}
</body>
</html>
