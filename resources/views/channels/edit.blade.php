@extends('layouts.app')
@section('title', 'Edit Channel — ' . $channel->name)

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('channels.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-sky-50 text-sky-400 hover:text-sky-600 transition-colors border border-sky-100">
            ←
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Edit Channel</h1>
            <p class="text-xs text-sky-500">{{ $channel->name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('channels.update', $channel) }}"
          class="bg-white rounded-2xl border border-sky-100 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')
        @include('channels._form', ['channel' => $channel])

        <div>
            <label class="flex items-center gap-2.5 cursor-pointer hover:bg-sky-50 rounded-lg px-2 py-2 transition-colors w-fit">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ $channel->is_active ? 'checked' : '' }}
                       class="rounded border-sky-300 text-sky-500">
                <span class="text-sm text-gray-700 font-medium">Channel aktif</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-400
                           text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-all">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
