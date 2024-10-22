<h3 class="text-primary"><i class="icon-puzzle2"></i> {{ trans('messages.setting_up_background_job') }}</h3>

<div class="database-config-box">
    @if(!$valid || isset($show_all))
        @include('helpers.form_control', [
            'type' => 'radio',
            'name' => 'php_bin_path',
            'class' => '',
            'label' => trans('messages.find_php_bin_path'),
            'value' => $php_bin_path,
            'options' => \Acelle\Library\Tool::phpPathsSelectOptions($php_paths),
            'rules' => [['php_bin_path' => 'required']]
        ])

        <div class="php_bin_path_value_box">
            @include('helpers.form_control', [
                'type' => 'text',
                'name' => 'php_bin_path_value',
                'class' => '',
                'placeholder' => 'Example: /usr/local/bin/php',
                'label' => trans('messages.enter_php_bin_path'),
                'value' => $php_bin_path_value,
                'rules' => [['php_bin_path_value' => 'required']]
            ])
        </div>
    @endif

    @if(!exec_enabled())
        <div class="alert alert-warning">
            {{ trans('messages.please_enable_php_exec_for_cronjob_check') }}
        </div>
    @endif

    @if($valid || (isset($show_all) && !$errors->has('php_bin_path_invalid')))
        <div id="result_box">
            <hr>

            <label class="mb-4" onclick="copy">{!! trans('messages.cron_jobs_guide') !!} </label>
            <div class="d-flex">
                <pre id="cronString" class="py-2 px-2 mb-0 pr-4 bg-light" style="font-size: 16px;height:50px;">* * * * * <span class="current_path_value">{!! $php_bin_path_value !!}</span> -q {{ base_path() }}/artisan schedule:run 2&gt;&amp;1</pre>
                <button type="button" class="btn btn-info copy-button rounded-0 rounded-end text-nowrap"><span class="material-symbols-rounded">content_copy</span> {{ trans('messages.copy') }}</button>
            </div>
        </div>
    @endif
</div>

<script>
    $(document).ready(function() {
        // copy
        $('.copy-button').on('click', function() {
            copyToClipboard($('#cronString').text());

            notify({
                type: 'success',
                message: '{{ trans('messages.cron_string.copied') }}'
            })
        });

        // pickadate mask
        $(document).on('change', 'input[name="php_bin_path"]', function() {
            var value = $(this).val();
            var old = $('.current_path_value').attr('old');

            if(value !== 'manual') {
                $('.current_path_value').html(value);
                $('input[name="php_bin_path_value"]').val(value);
            }

            if(value === 'manual') {
                $('.php_bin_path_value_box').show();
                $('input[name="php_bin_path_value"]').trigger('change');
            } else {
                $('.php_bin_path_value_box').hide();
            }
        });
        $('input[name="php_bin_path"]:checked').trigger('change');

        // pickadate mask
        $(document).on('keyup change', 'input[name="php_bin_path_value"]', function() {
            var value = $(this).val();

            if(value !== '') {
                $('.current_path_value').html(value);
            } else {
                $('.current_path_value').html('{PHP_BIN_PATH}');
            }
        });
        $('input[name="php_bin_path_value"]').trigger('change');

        // pickadate mask
        @if(isset($show_all))
            $(document).on('change', 'input', function() {
                $('#result_box').hide();
            });
        @endif
    });
</script>
