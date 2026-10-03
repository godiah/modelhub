<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support assistant: UI preview
    |--------------------------------------------------------------------------
    |
    | The support assistant is being designed visual-first: the chat widget renders scripted conversations from
    | App\Support\SupportChat\PreviewScenarios, with no backend behind it. This flag mounts the widget on every
    | signed-in member page so it can be judged in context. Off by default; turn it on locally only.
    |
    */

    'ui_preview' => (bool) env('SUPPORT_UI_PREVIEW', false),

];
