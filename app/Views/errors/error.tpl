{# Themed error page body. Rendered by PublicController::renderErrorPage #}
{# as the fallback when the active theme ships no errors/error.tpl, and #}
{# by the production error handler. status and message are controller- #}
{# provided plain text, escaped by Vision. #}
<div class="error-page text-center py-5">
    <h1 class="display-1">{{ status }}</h1>
    <p class="lead">{{ message }}</p>
</div>