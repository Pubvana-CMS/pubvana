{% if comments_enabled %}
<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title">{% lang 'Blog.commentFormTitle' %}</h4>
        {% if is_logged_in %}
            <form action="{% post_url post.slug %}" method="POST">
                <input type="hidden" name="csrf_token_name" value="{{ csrf_token }}">
                <input type="hidden" name="parent_id" value="">
                <div class="mb-3">
                    <label class="form-label">{% lang 'Blog.commentLabel' %}</label>
                    <textarea name="content" class="form-control" rows="5" required></textarea>
                </div>
                {% if hcaptcha_site_key %}
                    <div class="h-captcha mb-3" data-sitekey="{{ hcaptcha_site_key }}"></div>
                    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
                {% endif %}
                <button type="submit" class="btn btn-primary">{% lang 'Blog.commentPostBtn' %}</button>
                {% if comment_moderation %}
                    <small class="text-muted ms-2">{% lang 'Blog.commentModerated' %}</small>
                {% endif %}
            </form>
        {% else %}
            <p>{% lang 'Blog.commentLoginRequired' %} <a href="{% base_url 'login' %}">{% lang 'Blog.commentLoginLink' %}</a></p>
        {% endif %}
    </div>
</div>
{% endif %}
