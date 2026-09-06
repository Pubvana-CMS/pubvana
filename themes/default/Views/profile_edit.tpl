{# Profile edit form. Extends the master layout. #}
{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
        <div class="pv-profile-card">
            <h3 class="pv-profile-section-title">Edit Profile</h3>
            {# Plain HTML form: POSTs to the profile update endpoint. #}
            <form method="post" action="/profile/{{ user.username }}/update" class="pv-profile-form">
                {# Custom tag: emits the hidden CSRF token input the framework validates on POST. #}
                {% csrf_field %}

                {# Every field follows the same pattern: label, input, value from the profile. #}
                {# Escaped output in each value attribute. #}
                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="display_name">Display Name</label>
                    <input type="text" class="pv-profile-form-input" id="display_name" name="display_name"
                           value="{{ profile.display_name }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="bio">Bio</label>
                    <textarea class="pv-profile-form-textarea" id="bio" name="bio" rows="4">{{ profile.bio }}</textarea>
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label">Avatar</label>
                    {# Custom tag: the Media plugin's picker, arguments are the field name and current value. #}
                    {% media_picker 'avatar' profile.avatar %}
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="website">Website</label>
                    <input type="text" class="pv-profile-form-input" id="website" name="website"
                           value="{{ profile.website }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="twitter">Twitter</label>
                    <input type="text" class="pv-profile-form-input" id="twitter" name="twitter"
                           value="{{ profile.twitter }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="facebook">Facebook</label>
                    <input type="text" class="pv-profile-form-input" id="facebook" name="facebook"
                           value="{{ profile.facebook }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="linkedin">LinkedIn</label>
                    <input type="text" class="pv-profile-form-input" id="linkedin" name="linkedin"
                           value="{{ profile.linkedin }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="job_title">Job Title</label>
                    <input type="text" class="pv-profile-form-input" id="job_title" name="job_title"
                           value="{{ profile.job_title }}">
                </div>

                <div class="pv-profile-form-field">
                    <label class="pv-profile-form-label" for="works_for">Works For</label>
                    <input type="text" class="pv-profile-form-input" id="works_for" name="works_for"
                           value="{{ profile.works_for }}">
                </div>

                <div class="pv-profile-form-actions">
                    <button type="submit" class="pv-profile-btn">Save Profile</button>
                    <a href="/profile/{{ user.username }}" class="pv-profile-btn pv-profile-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        {# Region: content blocks placed below the form, inside the content column. #}
        {% region 'after-content' %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endblock %}
