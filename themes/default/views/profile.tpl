{% extends 'layout' %}

{% block content %}
<div class="row justify-content-center">
    <div class="col-lg-8">

        <h1 class="mb-4">{% lang 'Blog.profileTitle' %}</h1>

        {# --- Basic Information --- #}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{% lang 'Blog.profileBasicInfo' %}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{% site_url 'accounts/profile' %}">
                    {! csrf_field !}

                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">{% lang 'Blog.profileUsername' %}</label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ user.username }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">{% lang 'Blog.profileEmail' %}</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ email }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">{% lang 'Blog.profilePassword' %}</label>
                        <input type="password" name="password" id="password" class="form-control" autocomplete="new-password">
                        <div class="form-text">{% lang 'Blog.profilePasswordHelp' %}</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm" class="form-label fw-bold">{% lang 'Blog.profilePasswordConfirm' %}</label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" autocomplete="new-password">
                    </div>

                    {% if is_author %}
                    {# --- Author Profile --- #}
                    <hr class="my-4">
                    <h5 class="mb-3">{% lang 'Blog.profileAuthorInfo' %}</h5>

                    <div class="mb-3">
                        <label for="display_name" class="form-label fw-bold">{% lang 'Blog.profileDisplayName' %}</label>
                        <input type="text" name="display_name" id="display_name" class="form-control" value="{{ profile.display_name | default('') }}">
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label fw-bold">{% lang 'Blog.profileBio' %}</label>
                        <textarea name="bio" id="bio" class="form-control" rows="4">{{ profile.bio | default('') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{% lang 'Blog.profileAvatar' %}</label>
                        <div class="d-flex align-items-center gap-3">
                            {% if profile.avatar %}
                            <img src="{% base_url profile.avatar %}" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;">
                            {% endif %}
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('avatar-form-input').click()">
                                    <i class="fas fa-camera me-1"></i>{% lang 'Blog.profileAvatarChange' %}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="website" class="form-label fw-bold">{% lang 'Blog.profileWebsite' %}</label>
                        <input type="url" name="website" id="website" class="form-control" value="{{ profile.website | default('') }}">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="twitter" class="form-label fw-bold">{% lang 'Blog.profileTwitter' %}</label>
                            <input type="text" name="twitter" id="twitter" class="form-control" value="{{ profile.twitter | default('') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="facebook" class="form-label fw-bold">{% lang 'Blog.profileFacebook' %}</label>
                            <input type="text" name="facebook" id="facebook" class="form-control" value="{{ profile.facebook | default('') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="linkedin" class="form-label fw-bold">{% lang 'Blog.profileLinkedin' %}</label>
                            <input type="text" name="linkedin" id="linkedin" class="form-control" value="{{ profile.linkedin | default('') }}">
                        </div>
                    </div>
                    {% endif %}

                    <button type="submit" class="btn btn-primary">{% lang 'Blog.profileSave' %}</button>
                </form>
            </div>
        </div>

    </div>
</div>

{# Hidden avatar upload form #}
{% if is_author %}
<form id="avatar-upload-form" method="POST" action="{% site_url 'accounts/avatar' %}" enctype="multipart/form-data" class="d-none">
    {! csrf_field !}
    <input type="file" id="avatar-form-input" name="avatar" accept="image/*" onchange="this.form.submit()">
</form>
{% endif %}
{% endblock %}
