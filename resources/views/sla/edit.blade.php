@extends('layouts.app')
@section('title', 'Edit SLA Contract')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('sla.index') }}" class="text-sky-400 hover:text-sky-300"><i class="fa fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-white">Edit SLA Contract</h1>
    </div>
    <form method="POST" action="{{ route('sla.update', $contract->id) }}">
        @csrf @method('PUT')
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 mb-4">
            @include('sla._form')
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-sm font-medium">Simpan</button>
            <a href="{{ route('sla.index') }}" class="px-5 py-2 bg-slate-700 text-white rounded-lg text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
