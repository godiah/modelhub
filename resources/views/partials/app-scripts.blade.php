<!-- Check User Notifications -->
@auth
    <script>
        window.userId = {{ auth()->id() }};
    </script>
@endauth
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
