<div id="WebhookPopup">
    <div class="row">
        <div class="col-md-8">
            <p>{{ trans('messages.list.webhook.simple_intro') }}</p>
            <div>
                <div class="d-flex">
                    <div class="me-2 w-100">
                        <input type="text" class="form-control disabled" disabled name="" value="{{ action("Api\SubscriberController@store") }}" />
                    </div>
                    <div>
                        <button webhook-control="copy-action" class="btn btn-secondary">{{ trans('messages.copy') }}</button>
                    </div>
                </div>
            </div>

            <p class="mt-4">{{ trans('messages.list.webhook.curl_example') }}</p>

            <div class="border">
                <div class="bg-light py-2 px-3 border-bottom">
                    {{ trans('messages.list.webhook.posting_json_with_curl') }}
                </div>
                <div class="bg-light p-2 px-3">
                    <pre class="mb-0"><code data-control="example-code" class="language-curl"></code></pre>
                    
                </div>
            </div>

            <div class="mt-1">
                <button webhook-control="copy-code" class="btn btn-secondary">{{ trans('messages.copy') }}</button>
            </div>
        </div>
        <div class="col-md-4">
            <p>{{ trans('messages.list.webhook.fields_available_sample') }}</p>

            <div>
                @foreach ($list->fields as $field)
                    <a data-control="field" data-name="{{ $field->tag }}"  data-example="value_of_{{ $field->tag }}" href="javascript:;" class="btn btn-light rounded-pill px-3 me-1 mb-2">{{ $field->label }}</a>
                @endforeach
                <a data-control="field" data-name="tag"  data-example="foo,bar,tag+with+space" href="javascript:;" class="btn btn-light rounded-pill px-3 me-1 mb-2">
                    {{ trans('messages.tag') }}
                </a>
                {{-- <a data-control="field" data-name="status"  data-example="subscribed" href="javascript:;" class="btn btn-light rounded-pill px-3 me-1 mb-2">
                    {{ trans('messages.status') }}
                </a> --}}
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        document.querySelectorAll('pre code').forEach((el) => {
            hljs.highlightElement(el);
        });

        // copy action
        $('[webhook-control="copy-action"]').on('click', function(e) {
            copyToClipboard('{{ action('SubscriberController@store', [
                'list_uid' => $list->uid,
            ]) }}', $('#WebhookPopup'));

            //
            notify('success', '{{ trans('messages.notify.success') }}', '{{ trans('messages.list.webhook.action.copied') }}');
        });

        // copy code
        $('[webhook-control="copy-code"]').on('click', function(e) {
            copyToClipboard($('[data-control="example-code"]').text().replace(/\\/g, ''), $('#WebhookPopup'));

            //
            notify('success', '{{ trans('messages.notify.success') }}', '{{ trans('messages.list.webhook.code.copied') }}');
        });

        // tab links
        $('[webhook-control="tab-link"]').on('click', function(e) {
            e.preventDefault();

            var url = $(this).attr('href');
            listWebhook.popup.load(url);
        });

        // Sample manager
        new SampleManager({
            type: '{{ $type }}',
            codeContainer: $('[data-control="example-code"]'),
            fieldButtons: $('[data-control="field"]'),
        });
    });

    var SampleManager = class {
        constructor(options) {
            this.type = options.type;
            this.codeContainer = options.codeContainer;
            this.fieldButtons = options.fieldButtons;

            this.fields = [
                // {name: 'api_token', value: '{{ \Auth::user()->api_token }}'},
                {name: 'list_uid', value: '{{ $list->uid }}'},
                // {name: 'status', value: 'subscribed'},
                {name: 'EMAIL', value: 'test@gmail.com'},
                {name: 'FIRST_NAME', value: 'Marine'},
                {name: 'LAST_NAME', value: 'Joze'},
            ];

            // 
            this.render();

            // 
            this.events();
        }

        render() {
            if (this.type == 'json') {
                var data = {};
                this.fields.forEach((field) => {
                    data[field.name] = field.value;
                });
                var json = JSON.stringify(data);

                this.codeContainer.html(`curl -X POST {{ action("Api\Public\SubscriberController@store") }} \\
    -H 'Content-Type: application/json' \\
    -d '`+json+`'`);
            }

            else if (this.type == 'form') {
                var data = '';
                for (var i = 0; i < this.fields.length; i++) {
                    var field = this.fields[i];

                    if (i === this.fields.length - 1) {
                        data += `    -d `+field.name+`='`+field.value+`'`;
                    } else {
                        data += `    -d `+field.name+`='`+field.value+`' \\
`;
                    }
                };

                this.codeContainer.html(`curl -X POST {{ action("Api\Public\SubscriberController@store") }} \\
    -H 'accept:application/json' \\
` + data);
            }

            //
            this.hightlightButtons();
        }

        hasField(fieldName) {
            var exist = false;

            this.fields.forEach((field) => {
                if (field.name == fieldName) {
                    exist = true;
                }
            });

            return exist;
        }

        addField(fieldName, fieldExample) {
            this.fields.push({
                name: fieldName,
                value: fieldExample,
            });

            console.log(this.fields);
        }

        removeField(fieldName) {
            this.fields = this.fields.filter(function(field) {
                return field.name !== fieldName;
            });

            console.log(this.fields);
        }

        events() {
            var _this = this;
            this.fieldButtons.on('click', function(e) {
                e.preventDefault();

                var fieldName = $(this).attr('data-name');
                var fieldExample = $(this).attr('data-example');

                if (_this.hasField(fieldName)) {
                    _this.removeField(fieldName);
                } else {
                    _this.addField(fieldName, fieldExample);
                }

                //
                _this.render();
            });
        }

        hightlightButtons()
        {
            // remove all classes
            this.fieldButtons.each(function() {
                $(this).removeClass('active');
            });

            this.fields.forEach((field) => {
                this.fieldButtons.each(function() {
                    var button = $(this);
                    if (button.attr('data-name') == field.name) {
                        button.addClass('active');
                    }
                });
            });
        }
    }
</script>