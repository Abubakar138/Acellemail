document.addEventListener("DOMContentLoaded", function(event) {
    var popup = new AFormPopup({
        url: '{{ action('FormController@frontendContent', [
            'uid' => $form->uid,
        ]) }}',
        overlayOpacity: '{{ $form->getMetadata('overlay_opacity') ? ($form->getMetadata('overlay_opacity')/100) : '0.2' }}'
    });
    
    @if ($form->getMetadata('display') == 'click')
        if (document.getElementById('{{ $form->getMetadata('element_id') }}')) {
            document.getElementById('{{ $form->getMetadata('element_id') }}').addEventListener("click", function(event) {
                popup.load();
            });
        }
        if (document.querySelectorAll('{!! $form->getMetadata('element_id') !!}').length) {
            document.querySelectorAll('{!! $form->getMetadata('element_id') !!}').forEach(ele => {
                ele.addEventListener("click", function(event) {
                    popup.load();
                });
            });
        }
    @elseif ($form->getMetadata('display') == 'wait')
        setTimeout(function() {
            popup.load();
        }, {{ $form->getMetadata('wait_time')*1000 }});

    @elseif ($form->getMetadata('display') == 'first_visit')
        popup.loadOneTime();
    @else
        popup.load();
    @endif
});