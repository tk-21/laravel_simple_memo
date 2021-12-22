<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //必要な条件で絞ってメモを取得
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())//ログインしているユーザーによって動的に変わるようにする
            ->whereNull('deleted_at')//deleted_atに値があるものは削除されたとみなす
            ->orderBy('updated_at', 'DESC')
            ->get();

        return view('create', compact('memos'));
    }

    public function store(Request $request)
    {
        $posts = $request->all();

        Memo::insert(['content' => $posts['content'], 'user_id' => \Auth::id()]);

        return redirect(route('home'));
    }

    public function edit($id)//URLのパラメータを引数として取得
    {
        //必要な条件で絞ってメモを取得
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())//ログインしているユーザーによって動的に変わるようにする
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC')
            ->get();

        $edit_memo = Memo::find($id);//引数でfindメソッドを使ってメモを一つ取ってくる

        return view('edit', compact('memos', 'edit_memo'));//取ってきたメモをviewに渡す
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);//updateをする際は必ずwhereで行を指定するような情報を入れる。そのためにtype hiddenで埋め込んだ

        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time())]);//deleted_atに削除された時間を追加することで論理削除を行う

        return redirect(route('home'));
    }

}
