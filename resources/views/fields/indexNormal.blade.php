@extends('layouts.core.frontend', [
    'menu' => 'list',
])

@section('title', $list->name . ": " . trans('messages.manage_list_fields') )

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/anytime.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.date.js') }}"></script>
@endsection

@section('page_header')

            @include("lists._header")

@endsection

@section('content')

    @include("lists._menu", [
        'menu' => 'field',
    ])

    @include('fields._form')
    
@endsection