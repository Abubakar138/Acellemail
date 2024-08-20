@include('helpers.form_control', [
    'type' => 'text',
    'name' => 'subject',
    'value' => $page->subject,
    'rules' => ['subject' => 'subject']])

@if ($layout->alias == 'sign_up_form')
    <div class=" mb-4">
        <div class="d-flex align-items-center mb-2">
            <div class="me-auto">
                <div class="d-flex align-items-center">
                    <div class="notoping pe-3 ps-2">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'name' => 'enable_term',
                            'label' => '',
                            'value' => $list->getEmbeddedFormOption('enable_term'),
                            'options' => ['no','yes'],
                            'help_class' => 'list',
                        ])
                    </div>
                    <label>{{ trans('messages.embedded_form.enable_term') }}</label>
                </div>
            </div>
        </div>
        <div data-control="term" style="display:none">
            <textarea class="form-control term-editor" style="height:100px" name="term">{!! $list->getEmbeddedFormOption('term') !!}</textarea>
        </div>
    </div>

    <script>
        function toggleTermInput() {
            var checked = $('[name="enable_term"]').is(':checked');

            if (checked) {
                $('[data-control="term"]').show();
            } else {
                $('[data-control="term"]').hide();
            }
        }

        $(function() {
            // term click
            toggleTermInput();
            $('[name="enable_term"]').on('change', function() {
                toggleTermInput();
            });

            //
            tinymce.init({
                selector: '.term-editor',
                height: 200,
                convert_urls: false,
                remove_script_host: false,
                forced_root_block: "",
                skin: "oxide",
                branding: false,
                elementpath: false,
                statusbar: false,
                plugins: [
                'fullpage table advlist autolink lists link image charmap print preview anchor directionality',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media paste code'
                ],
                setup: function(editor) {
                    editor.on('input', function(e) {
                        editor.save();
                    });
                },
                toolbar: 'insertfile undo redo | fontselect | fontsizeselect | styleselect | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | ltr rtl',
                valid_elements : '*[*],meta[*]',
                valid_children: '+p[ol],+p[ul],+h1[div],+h2[div],+h3[div],+h4[div],+h5[div],+h6[div],+a[div],*[*]',
                extended_valid_elements : "meta[*]",
                valid_children : "+body[style],+body[meta],+div[h2|span|meta|object],+object[param|embed]",
                external_filemanager_path:APP_URL.replace('/index.php','')+"/filemanager2/",
                filemanager_title:"Responsive Filemanager" ,
                external_plugins: { "filemanager" : APP_URL.replace('/index.php','')+"/filemanager2/plugin.min.js"}
            });
        });
        
    </script>
@endif

@include('helpers.form_control', ['class' => ($layout->type == 'page' ? 'full-editor' : 'email-editor'), 'type' => 'textarea', 'name' => 'content', 'value' => $page->content, 'rules' => $list->getFieldRules()])



@if (count($layout->tags()) > 0)
    <div class="tags_list">
        <label class="text-semibold text-teal">{{ trans('messages.required_tags') }}:</label>
        <br />
        @foreach($layout->tags() as $tag)
            @if ($tag["required"])
                <a data-popup="tooltip" draggable="false" title='{{ trans('messages.click_to_insert_tag') }}' href="javascript:;" class="btn btn-default text-semibold btn-xs insert_tag_button" data-tag-name="{{ $tag["name"] }}">
                    {{ $tag["name"] }}
                </a>
            @endif
        @endforeach
    </div>
@endif

<br />
@if (count($layout->tags()) > 0)
    <div class="tags_list">
        <label class="text-semibold text-teal">{{ trans('messages.available_tags') }}:</label>
        <br />
        @foreach ($list->fields as $field)
            <a data-popup="tooltip" title='{{ trans('messages.click_to_insert_tag') }}' href="javascript:;" class="btn btn-default text-semibold btn-xs insert_tag_button"
                data-tag-name="{{ "{SUBSCRIBER_".$field->tag."}" }}">
                {{ "{SUBSCRIBER_".$field->tag."}" }}
            </a>
        @endforeach
        @foreach($layout->tags() as $tag)
            @if (!$tag["required"])
                <a data-popup="tooltip" draggable="false" title='{{ trans('messages.click_to_insert_tag') }}' href="javascript:;" class="btn btn-default text-semibold btn-xs insert_tag_button" data-tag-name="{{ $tag["name"] }}">
                    {{ $tag["name"] }}
                </a>
            @endif
        @endforeach
    </div>
@endif

<script>
    $(function() {
        // Click to insert tag
        $(document).on("click", ".insert_tag_button", function() {
            var tag = $(this).attr("data-tag-name");

            if($('textarea[name="html"]').length || $('textarea[name="content"]').length) {
                tinymce.activeEditor.execCommand('mceInsertContent', false, tag);
            } else {
                speechSynthesis;
                $('textarea[name="plain"]').val($('textarea[name="plain"]').val()+tag);
            }
        });
    });
</script>