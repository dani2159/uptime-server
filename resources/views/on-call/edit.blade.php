@extends('layouts.app')
@section('title', 'Edit Jadwal On-Call')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('on-call.index') }}" class="text-sky-400 hover:text-sky-300"><i class="fa fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-white">Edit Jadwal: {{ $schedule->name }}</h1>
    </div>
    <form method="POST" action="{{ route('on-call.update', $schedule->id) }}">
        @csrf @method('PUT')
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 mb-4">
            @include('on-call._form')
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">Simpan</button>
            <a href="{{ route('on-call.index') }}" class="px-5 py-2 bg-slate-700 text-white rounded-lg text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
