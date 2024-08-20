@if (request()->ajax())
	@include('fields.indexPopup')
@else
	@include('fields.indexNormal')
@endif


