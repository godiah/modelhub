<!-- Check User Notifications -->
@auth
    <script>
        window.userId = {{ auth()->id() }};
    </script>
@endauth
