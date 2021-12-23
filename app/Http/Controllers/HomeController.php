<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

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
            ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
            ->whereNull('deleted_at') //deleted_atに値があるものは削除されたとみなす
            ->orderBy('updated_at', 'DESC')
            ->get();

        $tags = Tag::where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->get();

        return view('create', compact('memos', 'tags')); //viewに渡す
    }

    public function store(Request $request)
    {
        $posts = $request->all();

        //ここからトランザクション開始
        DB::transaction(function () use ($posts) { //クロージャー（独立した空間）
            //メモIDをインサートして取得
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]); //新しくインサートしたメモのIDを返す
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists(); //user_idとnameが同じであれば既に存在すると判断できる
            //新規タグが入力されているかチェック
            //新規タグが既にtagsテーブルに存在するのかチェック（タグの存在チェックをしないと同じ名前のタグが複数存在してしまう）
            if ((!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists) {
                //新規タグが既に存在しなければ、tagsテーブルにインサートし、IDを取得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsにインサートして、メモとタグを紐付ける
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
            //既存タグが紐付けられた場合→memo_tagsにインサート
            if (!empty($posts['tags'][0])) {
                foreach ($posts['tags'] as $tag) {
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }
        });
        //ここまでがトランザクションの範囲

        //成功すればhomeにリダイレクト
        return redirect(route('home'));
    }

    public function edit($id) //URLのパラメータを引数として取得
    {
        //必要な条件で絞ってメモを取得
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC')
            ->get();

        $edit_memo = Memo::find($id); //引数でfindメソッドを使ってメモを一つ取ってくる

        return view('edit', compact('memos', 'edit_memo')); //取ってきたメモをviewに渡す
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]); //updateをする際は必ずwhereで行を指定するような情報を入れる。そのためにtype hiddenで埋め込んだ

        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time())]); //deleted_atに削除された時間を追加することで論理削除を行う

        return redirect(route('home'));
    }
}
