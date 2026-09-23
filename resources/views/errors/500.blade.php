@include('errors.layout', ['code' => 500, 'title' => __('Something went wrong'), 'message' => __('An unexpected error occurred on our side. Please try again in a moment.'), 'retry' => true])
