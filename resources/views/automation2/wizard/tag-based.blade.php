<div class="mb-4">
    <input type="hidden" name="options[type]" value="{{ Acelle\Model\Automation2::TRIGGER_TAG_BASED }}" />

    <div class="form-group">
        <select name="options[tags][]"
            class="select-tag select-search form-control" multiple required>
        </select>
    </div>

    <div class="row">
        <div class="col-md-6 mt-2">
            @include('helpers.form_control', [
                'name' => 'mail_list_uid',
                'include_blank' => trans('messages.automation.choose_list'),
                'type' => 'select',
                'label' => trans('messages.list'),
                'value' => '',
                'options' => Auth::user()->customer->readCache('MailListSelectOptions', []),
            ])
        </div>
    </div>
        
</div>