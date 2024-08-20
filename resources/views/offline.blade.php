@extends('layouts.core.login')

@section('title', trans('messages.offline'))

@section('content')
    <div class="alert alert-info">
        <span class="text-semibold">
            {{ Acelle\Model\Setting::get("site_offline_message") }}
        </span>
    </div>
@endsection