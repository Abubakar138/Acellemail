<div class="mb-4">
    <input type="hidden" name="options[type]" value="{{ Acelle\Model\Automation2::TRIGGER_TAG_BASED }}" />

    @php
        $tags = $trigger->getOption('tags') ?? [];
    @endphp

    <div class="form-group">
        <select name="options[tags][]"
            class="select-tag select-search form-control" multiple required>
            @foreach($tags as $tag)
                <option
                    selected
                    value="{{ $tag }}"
                >{{ $tag }}</option>
            @endforeach
        </select>
    </div>
</div>