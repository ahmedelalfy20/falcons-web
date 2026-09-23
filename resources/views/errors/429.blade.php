@include('errors.layout', ['code' => 429, 'title' => __('Too many attempts'), 'message' => __('You have made too many requests. Please wait a minute and try again.'), 'retry' => true])
