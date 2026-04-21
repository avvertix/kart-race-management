<x-input :id="$id" list="{{ $listId }}" type="text" :name="$name" :value="$value" {{ $attributes }} />
<datalist id="{{ $listId }}">
    @foreach ($priorityCountries as $country)
        <option value="{{ $country }}"></option>
    @endforeach
    @foreach ($otherCountries as $country)
        <option value="{{ $country }}"></option>
    @endforeach
</datalist>
