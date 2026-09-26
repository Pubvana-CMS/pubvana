<div class="row">
    <div class="col-lg-8">
        {% if comments_enabled %}
        <div class="pv-comments">
            <h2>Comments</h2>

            {% if comments %}
            <ul class="pv-comments-list">
                {% for comment in comments %}
                <li class="pv-comment pv-comment-depth-{{ comment.depth }}" id="comment-{{ comment.id }}">
                    <div class="pv-comment-meta">
                        <strong>{{ comment.author }}</strong>
                        <small>{{ comment.created_at | date('M j, Y g:ia') }}</small>
                    </div>
                    <div class="pv-comment-body">{! comment.body !}</div>
                    {% if comments_open %}
                    <button type="button" class="pv-comment-reply" data-parent-id="{{ comment.id }}" data-author="{{ comment.author }}">Reply</button>
                    {% endif %}
                </li>
                {% endfor %}
            </ul>
            {% else %}
            <p class="pv-comments-empty">No comments yet.</p>
            {% endif %}

            {% if comments_open %}
            <form method="POST" action="{{ comment_post_url }}" class="pv-comment-form" id="pv-comment-form" autocomplete="off">
                {% csrf_field %}
                <input type="hidden" name="parent_id" id="pv-comment-parent-id" value="">
                <div class="pv-comment-replying" id="pv-comment-replying" hidden>
                    Replying to <span id="pv-comment-replying-author"></span>
                    <button type="button" class="pv-comment-cancel-reply" id="pv-comment-cancel-reply">Cancel</button>
                </div>
                <textarea name="body" rows="4" placeholder="Leave a comment..." required></textarea>
                {% if comments_is_guest %}
                <input type="text" name="guest_name" placeholder="Name" required>
                <input type="email" name="guest_email" placeholder="Email (optional)">
                <input type="text" name="guest_website" placeholder="Website (optional)">
                {% endif %}
                {% captcha 'comments' %}
                <button type="submit">Post Comment</button>
            </form>
            {% endif %}

            {% if comments_closed %}
            <p class="pv-comments-closed">Comments are closed.</p>
            {% endif %}
        </div>

        <script>
        (function () {
            var parentInput = document.getElementById('pv-comment-parent-id');
            var banner = document.getElementById('pv-comment-replying');
            var bannerAuthor = document.getElementById('pv-comment-replying-author');
            var form = document.getElementById('pv-comment-form');
            if (!parentInput || !form) {
                return;
            }
            var textarea = form.querySelector('textarea[name="body"]');

            document.addEventListener('click', function (event) {
                var reply = event.target.closest('.pv-comment-reply');
                if (reply) {
                    event.preventDefault();
                    parentInput.value = reply.getAttribute('data-parent-id') || '';
                    if (bannerAuthor) {
                        bannerAuthor.textContent = reply.getAttribute('data-author') || '';
                    }
                    if (banner) {
                        banner.hidden = false;
                    }
                    if (textarea) {
                        textarea.focus();
                    }
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                if (event.target.closest('#pv-comment-cancel-reply')) {
                    event.preventDefault();
                    parentInput.value = '';
                    if (banner) {
                        banner.hidden = true;
                    }
                }
            });
        })();
        </script>
        {% endif %}
    </div>
</div>
