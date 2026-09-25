{{-- This partial displays the title for each notification type --}}
{{ \App\Helpers\NotificationPresenterHelper::present($notification)['title'] }}
