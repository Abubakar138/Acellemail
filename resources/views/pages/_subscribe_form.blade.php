@foreach ($list->getFields as $field)
	@if ($field->visible || !isset($is_page))
		@if ($field->tag != 'EMAIL')
			@if ($field->type == "text")
				@include('helpers.form_control', ['type' => $field->type, 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "number")
				@include('helpers.form_control', ['type' => 'number', 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "textarea")
				@include('helpers.form_control', ['type' => 'textarea', 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "dropdown")
				@include('helpers.form_control', ['type' => 'select', 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "multiselect")
				@include('helpers.form_control', ['multiple' => true, 'type' => 'select', 'name' => $field->tag . "[]", 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "checkbox")
				@include('helpers.form_control', ['multiple' => true, 'type' => 'checkboxes', 'name' => $field->tag . "[]", 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "radio")
				@include('helpers.form_control', ['multiple' => true, 'type' => 'radio', 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "date")
				@include('helpers.form_control', ['multiple' => true, 'type' => 'date', 'name' => $field->tag . "[]", 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@elseif ($field->type == "datetime")
				@include('helpers.form_control', ['multiple' => true, 'type' => 'datetime', 'name' => $field->tag . "[]", 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'options' => $field->getSelectOptions(), 'rules' => $list->getFieldRules()])
			@endif
		@else
			@include('helpers.form_control', ['type' => $field->type, 'name' => $field->tag, 'label' => $field->label, 'value' => (isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value), 'rules' => $list->getFieldRules()])
		@endif
	@endif
@endforeach

@if ($list->getEmbeddedFormOption('enable_term') == 'yes')
	<div class="d-flex align-items-top mb-4 mt-4 pt-2" style="position: relative">
		<div class="mr-2">
			<input type="checkbox" name="acm_term" required />
		</div>
		<div>
			{!! $list->getEmbeddedFormOption('term') !!}
		</div>
	</div>
@endif

@if (\Acelle\Model\Setting::isListSignupCaptchaEnabled())
	<div>
		<!-- hCaptcha -->
		@if (\Acelle\Model\Setting::getCaptchaProvider() == 'hcaptcha')
			@php
				$hcaptcha = \Acelle\Hcaptcha\Client::initialize();
			@endphp
			{!! $hcaptcha->renderFormHtml($errors) !!}
		@else
			{!! \Acelle\Library\Tool::showReCaptcha($errors) !!}
		@endif
	</div>
@endif
