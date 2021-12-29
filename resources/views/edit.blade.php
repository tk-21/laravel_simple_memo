@extends('layouts.app')

@section('javascript')
<script src="/js/confirm.js"></script>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        メモ編集
        <form class="card-body" id="delete-form" action="{{ route('destroy') }}" method="POST">
            @csrf
            <input type="hidden" name="memo_id" value="{{ $edit_memo[0]['id'] }}">
            <!-- ここでイベントを呼び出す -->
            <button type="submit" onclick="deleteHandle(event);">削除</button>
        </form>
    </div>
    <form class="card-body" action="{{ route('update') }}" method="POST">
        @csrf
        <!-- ↓どのidのメモを更新するのかを教えてあげるために必要な記述。今編集しているメモのidを埋め込んでコントローラーに教えてあげる -->
        <input type="hidden" name="memo_id" value="{{ $edit_memo[0]['id'] }}">
        <div class="form-group">
            <textarea class="form-control" name="content" rows="3" placeholder="ここにメモを入力">{{ $edit_memo[0]['content'] }}</textarea>
        </div>
        @error('content')
            <div class="alert alert-danger">メモ内容を入力してください！</div>
        @enderror

        @foreach($tags as $tag)
        <div class="form-check form-check-inline mb-3">
            <!-- もし$include_tagsにループで回っているタグのidが含まれれば、checkedを書く -->
            <input class="form-check-input" type="checkbox" name="tags[]" id="{{ $tag['id'] }}" value="{{ $tag['id'] }}" {{ in_array($tag['id'], $include_tags) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $tag['id'] }}">{{ $tag['name'] }}</label>
        </div>
        @endforeach
        <input type="text" class="form-control w-50 mb-3" name="new_tag" placeholder="新しいタグ">
        <button type="submit" class="btn btn-primary">更新</button>
    </form>
</div>

@endsection
