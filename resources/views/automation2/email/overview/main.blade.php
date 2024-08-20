@extends('layouts.popup.large')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include("automation2.email.overview._info")

            <br />

            @include("automation2.email.overview._chart")

            @include("automation2.email.overview._open_click_rate")

            @include("automation2.email.overview._count_boxes")

            <br />

            @include("automation2.email.overview._24h_chart")


            <br />

            @include("automation2.email.overview._top_link")

            <br />

            @include("automation2.email.overview._most_click_country")

            <br />

            @include("automation2.email.overview._most_open_country")

            <br />

            @include("automation2.email.overview._most_open_location")

            <br />
        </div>
    </div>
    <script>
        
    </script>

@endsection
