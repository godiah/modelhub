@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '<span class="font-main">Success</span>',
                html: '<span class="font-main text-dark text-sm">{{ session('success') }}</span>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                customClass: {
                    popup: 'font-main'
                }
            });
        });
</script>
@endif

@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: '<span class="font-main">Error</span>',
                html: '<span class="font-main text-dark text-sm">{{ session('error') }}</span>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                customClass: {
                    popup: 'font-main'
                }
            });
        });
</script>
@endif

@if (session('warning'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: '<span class="font-main">Warning</span>',
                html: '<span class="font-main text-dark text-sm">{{ session('warning') }}</span>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                customClass: {
                    popup: 'font-main'
                }
            });
        });
</script>
@endif

@if (session('info'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'info',
                title: '<span class="font-main">Information</span>',
                html: '<span class="font-main text-dark text-sm">{{ session('info') }}</span>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                customClass: {
                    popup: 'font-main'
                }
            });
        });
</script>
@endif

@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: '<span class="font-main">Validation Error</span>',
                html: `<ul class="font-main text-dark text-sm text-left">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 7000,
                timerProgressBar: true,
                customClass: {
                    popup: 'font-main'
                }
            });
        });
</script>
@endif