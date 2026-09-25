{{-- This partial displays the content for each notification type --}}
{{ \App\Helpers\NotificationPresenterHelper::present($notification)['content'] }}
