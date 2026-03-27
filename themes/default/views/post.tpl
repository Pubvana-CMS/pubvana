{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="{% if show_sidebar == '1' %}col-lg-8{% else %}col-12{% endif %}">
        {% widget_area 'before-content' %}

        <article>
            {% if post.featured_image %}
                <img src="{% base_url post.featured_image %}" alt="{{ post.title }}" class="img-fluid rounded mb-4 w-100" style="max-height:400px;object-fit:cover;">
            {% endif %}

            <h1>{{ post.title }}</h1>

            <div class="text-muted small mb-4">
                <i class="fas fa-calendar-days"></i> {% lang 'Blog.postedOn' %} {{ post.published_at | date('F j, Y') }}
                &nbsp;&middot;&nbsp;
                <i class="fas fa-eye"></i> {% lang 'Blog.views' post.views|number_format %}
                {% if reading_time %}
                &nbsp;&middot;&nbsp;
                <i class="fas fa-clock"></i> {% lang 'Blog.readingTime' reading_time %}
                {% endif %}
            </div>

            {% if paywall %}
                {% if post.excerpt %}
                    <div class="post-content">{{ post.excerpt | nl2br | raw }}</div>
                {% endif %}
            {% else %}
                <div class="post-content">
                    {% render_content post %}
                </div>
            {% endif %}

            {% if is_premium_active and author_profile %}
                {% include 'partials/author-card' with {author_profile: author_profile, post: post} %}
            {% endif %}
        </article>

        {% if not paywall and comments_enabled %}
        <hr class="my-5">

        <div id="comments">
            <h3 class="mb-4">{% lang 'Blog.commentsHeading' comments|count %}</h3>

            {% if comments %}
                {% include 'partials/comments-list' with {comments: comments} %}
            {% endif %}

            {% include 'partials/comment-form' with {post: post} %}
        </div>
        {% endif %}
    </div>

    {% if show_sidebar == '1' %}
    <div class="col-lg-4">
        {% include 'partials/sidebar' %}
    </div>
    {% endif %}
</div>
{% endblock %}
