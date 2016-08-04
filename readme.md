# Table List

Customizable data tables for Laravel with pagination, search and buttons.

**Under development. When we reach a more usable version we'll realease as a composer package.**

```php
Route::get('links', function(\Illuminate\Http\Request $request){
    $tableList = new \Softerize\TableList\Generator('\App\Models\Link', $request);
    return view('inicio.index', compact('tableList'));
});
```

```php
@extends('layouts.app')

@section('content')
{!! $tableList->generate() !!}
@endsection
```
