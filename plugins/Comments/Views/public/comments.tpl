{# Injectable comment thread + form fragment.
   Rendered by CommentService::render() for a content item and injected into
   a host plugin's view. Consumes the data array from CommentService::dataFor(). #}
{% if comments_enabled %}
<div class="pv-comments">
    <style>
        .pv-comments { margin-top: 2rem; }
        .pv-comments-list { list-style: none; margin: 0; padding: 0; }
        .pv-comment { padding: .75rem .75rem 0 0; }
        .pv-comment-depth-1 { margin-left: 1.25rem; }
        .pv-comment-depth-2 { margin-left: 2.5rem; }
        .pv-comment-depth-3 { margin-left: 3.75rem; }
        .pv-comment-depth-4 { margin-left: 5rem; }
        .pv-comment-meta { display: flex; gap: .75rem; align-items: baseline; }
        .pv-comment-body { margin-top: .25rem; }
        .pv-comment-form { margin-top: 1.25rem; display: grid; gap: .5rem; }
        .pv-comment-form textarea,
        .pv-comment-form input { width: 100%; box-sizing: border-box; }
        .pv-comments-error { color: #b3261e; margin-bottom: .75rem; }
        .pv-comments-closed, .pv-comments-empty { color: var(--pv-muted, #6b7280); }
    </style>

    <h3>Comments</h3>

    {% if comments_error %}
    <div class="pv-comments-error">{{ comments_error }}</div>
    {% endif %}

    {% if comments %}
    <ul class="pv-comments-list">
        {% for comment in comments %}
        <li class="pv-comment pv-comment-depth-{{ comment.depth }}">
            <div class="pv-comment-meta">
                <strong>{{ comment.author }}</strong>
                <small>{{ comment.created_at | date('M j, Y g:ia') }}</small>
            </div>
            <div class="pv-comment-body">{! comment.body !}</div>
        </li>
        {% endfor %}
    </ul>
    {% else %}
    <p class="pv-comments-empty">No comments yet.</p>
    {% endif %}

    {% if comments_open %}
    <form method="POST" action="{{ comment_post_url }}" class="pv-comment-form" autocomplete="off">
        {% csrf_field %}
        <textarea name="body" rows="4" placeholder="Leave a comment..." required></textarea>
        {% if comments_is_guest %}
        <input type="text" name="guest_name" placeholder="Name" required>
        <input type="email" name="guest_email" placeholder="Email (optional)">
        <input type="text" name="guest_website" placeholder="Website (optional)">
        {% endif %}
        {% if captcha_provider %}
        <div class="g-recaptcha" data-sitekey="{{ captcha_site_key }}"></div>
        {% endif %}
        <button type="submit">Post Comment</button>
    </form>
    {% endif %}

    {% if comments_closed %}
    <p class="pv-comments-closed">Comments are closed.</p>
    {% endif %}
</div>
{% endif %}
