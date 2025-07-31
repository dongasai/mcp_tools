<x-dcatadmindemo::layouts.master>
    <h1>Demo Details</h1>

    <p>ID: {{ $id }}</p>
    
    <p>Module: {!! config('madmindemo.name') !!}</p>
    
    <a href="{{ route('madmindemo.index') }}">Back to Index</a>
</x-dcatadmindemo::layouts.master>