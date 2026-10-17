{# Theme override for the Comments plugin's public thread.
   Resolved by CommentService::resolveTemplate() as
   themes/{active}/Views/pubvana/comments/comments.tpl.
   Data comes from CommentService::dataFor(): comments, comments_enabled,
   comments_open, comments_closed, comments_is_guest, comment_post_url,
   max_nesting_depth, csrf_field. Each comment carries id, parent_id, depth,
   author, body, created_at, children. #}
{% if comments_enabled %}
<hr>
<section class="mt-4" id="comments">
    <h3>Comments ({{ comments | count }})</h3>

    {% if comments %}
    {% for comment in comments %}
    <div class="card mb-2 ms-{{ comment.depth }}" id="comment-{{ comment.id }}">
        <div class="card-body py-2 px-3">
            <strong>{{ comment.author }}</strong>
            <span class="text-muted small ms-1">{{ comment.created_at | date('F j, Y g:ia') }}</span>
            <div class="mt-1">{! comment.body !}</div>
            {% if comments_open %}
            {# The p-0 left this button 21px tall, under the 24px 2.5.8 wants. #}
            <button type="button" class="btn btn-sm btn-link mt-1 comment-reply-btn" data-parent-id="{{ comment.id }}" data-author="{{ comment.author }}" aria-label="Reply to {{ comment.author }}">Reply</button>
            {% endif %}
        </div>
    </div>
    {% endfor %}
    {% else %}
    <p class="text-muted">No comments yet.</p>
    {% endif %}

    {% if comments_open %}
    <div class="mt-4" id="comment-form-wrapper">
        <h5 id="comment-form-title">Leave a Comment</h5>
        <div id="reply-indicator" class="alert alert-info d-none mb-3" role="status">
            Replying to <span id="reply-indicator-author"></span> (<a href="#" id="cancel-reply">cancel</a>)
        </div>
        <form method="post" action="{{ comment_post_url }}" id="comment-form">
            {! csrf_field !}
            <input type="hidden" name="parent_id" id="comment-parent-id" value="">

            {% if comments_is_guest %}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="comment-guest-name">Name <span aria-hidden="true">*</span></label>
                    <input type="text" name="guest_name" id="comment-guest-name" class="form-control" autocomplete="name" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="comment-guest-email">Email (optional)</label>
                    <input type="email" name="guest_email" id="comment-guest-email" class="form-control" autocomplete="email">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="comment-guest-website">Website (optional)</label>
                    <input type="url" name="guest_website" id="comment-guest-website" class="form-control" autocomplete="url">
                </div>
            </div>
            {% endif %}

            <div class="mb-3">
                <label class="form-label" for="comment-body">Comment <span aria-hidden="true">*</span></label>
                <textarea name="body" id="comment-body" class="form-control" rows="4" required></textarea>
            </div>

            {% captcha 'comments' %}

            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
    </div>
    {% elseif comments_closed %}
    <p class="text-muted mt-3">Comments are closed for this post.</p>
    {% else %}
    <p class="text-muted mt-3">Please <a href="/login">log in</a> to leave a comment.</p>
    {% endif %}
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btns = document.querySelectorAll('.comment-reply-btn');
    var parentInput = document.getElementById('comment-parent-id');
    var indicator = document.getElementById('reply-indicator');
    var indicatorAuthor = document.getElementById('reply-indicator-author');
    var formTitle = document.getElementById('comment-form-title');
    var formWrapper = document.getElementById('comment-form-wrapper');

    btns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            parentInput.value = this.getAttribute('data-parent-id');
            // Reveal the live region before filling it. A change made while
            // it is still display:none is not announced.
            indicator.classList.remove('d-none');
            if (indicatorAuthor) { indicatorAuthor.textContent = this.getAttribute('data-author') || ''; }
            formTitle.textContent = 'Reply';
            formWrapper.scrollIntoView({ behavior: 'smooth' });
        });
    });

    var cancel = document.getElementById('cancel-reply');
    if (cancel) {
        cancel.addEventListener('click', function(e) {
            e.preventDefault();
            parentInput.value = '';
            indicator.classList.add('d-none');
            formTitle.textContent = 'Leave a Comment';
        });
    }
});
</script>
{% endif %}
